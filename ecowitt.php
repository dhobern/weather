<?php

/** 
 * Receive readings from Ecowitt weather station
 * 
 * Expected Ecowitt record includes readings for all instruments included
 * in an ECOWITT GW1002 Wi-Fi Weather Station with Solar Powered Wireless 
 * Anemometer, UV & Light Sensor, Self-Emptying Rain Collector, indoor
 * and outdoor Temperature and Humidity Sensors and an indoor Pressure
 * Sensor.
 * 
 * Adds a new record based on the Ecowitt values to the weather_readings
 * table and once a day adds a summary record to the weather_daily table.
 * 
 * @author  Donald Hobern
 * @version $Revision: 1.0 $
 * @access public 
 */

include_once ('classes/db.php');

/**
 * Map long name for property (from readings) to short name used in
 * daily summary records in combination with max/min/mean.
 * 
 * @param string $keybase long name for property
 * @return string short name
 */
function mapKey($keybase) {
	switch($keybase) {
		case 'temperature': $keybase = 'temp'; break;
		case 'humidity': $keybase = 'humid'; break;
		case 'soilmoisture': $keybase = 'soilmoist'; break;
		case 'absolutepressure': $keybase = 'abspress'; break;
		case 'relativepressure': $keybase = 'relpress'; break;
		case 'solarradiation': $keybase = 'solrad'; break;
	}
	return $keybase;
}

/**
 * Update max and min values in daily summary array based on value
 * in a given reading.
 * 
 * Updare $map if the supplied value for the long name property 
 * exceeds the current summary value for the key formed as 
 * 'max'.<short_name> or is lower than the corresponding 
 * 'min'.<short_name> value.
 * 
 * $maxonly allows for properties which have no useful lower 
 * limit (e.g. solar radiation) and are not recorded as minima in
 * the daily record. In these cases, no 'minxxx' value is added
 * to $map since this would then be added to the MySQL insert.
 * 
 * @param array &$map array serving as map for daily record
 * @param string $value sensor measurement
 * @param string $keybase long name for measurement from reading
 * @param bool $maxonly if true, do not update minimum value
 */
function updateRange(&$map, $value, $keybase, $maxonly = false) {
	$key = mapKey($keybase);
	$max = $map['max'.$key];
	if(is_null($max) || $value > $max) {
		$map['max'.$key] = $value;
	} 
	if (!$maxonly) {
		$min = $map['min'.$key];
		if(is_null($min) || $value < $min) {
			$map['min'.$key] = $value;
		} 
	}
}

/**
 * Include sunrise and sunset times from sunrise-sunset.org
 * 
 * @param array &@map array serving as map for daily record
 * @param string $date date for which to add values in Y-m-d format
 * @param DateTimeZone $timezone timezone for sunrise and sunset times
 */
function addSunRiseAndSet(&$map, $date, $timezone) {
    $response = file_get_contents('https://api.sunrise-sunset.org/json?lat=-35.26419&lng=149.08338&formatted=0&date='.$date);
    $sunvalues = json_decode($response, true);

    $datetime = DateTime::createFromFormat('Y-m-d\TH:i:sP', $sunvalues['results']['sunrise']);
    $datetime->setTimezone($timezone);
    $map['sunrise'] = '\''.$datetime->format("H:i:s").'\'';

    $datetime = DateTime::createFromFormat('Y-m-d\TH:i:sP', $sunvalues['results']['sunset']);
    $datetime->setTimezone($timezone);
    $map['sunset'] = '\''.$datetime->format("H:i:s").'\'';
}


/************************************************************************
 * Create weather_reading from Ecowitt record
 ***********************************************************************/

// Local timezone
$timezone = new DateTimeZone('Australia/Canberra');

// UTC DateTime from Ecowitt reading
$datetime = DateTime::createFromFormat('Y-m-d H:i:s', filter_input(INPUT_POST, 'dateutc', FILTER_SANITIZE_STRING));

// Create array with all readings mapped into preferred units
// Temperature: F -> C, Pressure: inHg -> hPa, Speed: mph -> kph
// Rain: inch -> mm
$data = array();
$data['dateutc'] = '\''.$datetime->format('Y-m-d H:i:s').'\'';
$data['temperature'] = ((filter_input(INPUT_POST, 'tempf', FILTER_SANITIZE_STRING) - 32) * 5) / 9;
$data['humidity'] = filter_input(INPUT_POST, 'humidity', FILTER_SANITIZE_STRING);
$data['relativepressure'] = filter_input(INPUT_POST, 'baromrelin', FILTER_SANITIZE_STRING) / 0.02953;
$data['absolutepressure'] = filter_input(INPUT_POST, 'baromabsin', FILTER_SANITIZE_STRING) / 0.02953;
$data['winddir'] = filter_input(INPUT_POST, 'winddir', FILTER_SANITIZE_STRING);
$data['windspeed'] = filter_input(INPUT_POST, 'windspeedmph', FILTER_SANITIZE_STRING) * 1.60934;
$data['windgust'] = filter_input(INPUT_POST, 'windgustmph', FILTER_SANITIZE_STRING) * 1.60934;
$data['rainrate'] = filter_input(INPUT_POST, 'rainratein', FILTER_SANITIZE_STRING) * 25.4;
$data['raindaily'] = filter_input(INPUT_POST, 'dailyrainin', FILTER_SANITIZE_STRING) * 25.4;
$data['solarradiation'] = filter_input(INPUT_POST, 'solarradiation', FILTER_SANITIZE_STRING);
$data['uv'] = filter_input(INPUT_POST, 'uv', FILTER_SANITIZE_STRING);
$data['soilmoisture'] = filter_input(INPUT_POST, 'soilmoisture1', FILTER_SANITIZE_STRING);

// Insert record into weather_readings
$db = new Db();
$success = $db->insert('weather_readings', $data); 

/************************************************************************
 * Once every 24 hours, create summary weather_daily record
 ***********************************************************************/

// Find current time in local timezone and measure difference from 09:00
$now = new DateTime();
$now->setTimestamp(time());
$now->setTimezone($timezone);
$todaydate = $now->format('Y-m-d');
$nineamtoday = DateTime::createFromFormat('Y-m-d H:i:s', $todaydate.' 09:00:00', $timezone);
$nineamtoday->setTimezone(new DateTimeZone('UTC'));
$timediff = $now->getTimestamp() - $nineamtoday->getTimestamp();

// Only attempt this in the hour after 09:00 - avoid unnecessary computation
// whenever processing Ecowitt records
if ($timediff >= 0 && $timediff < 3600) {

	// Check whether the daily record has already been created (i.e. whether
	// there is already a record with today's date)
	$rows = $db->fetchLastN('weather_daily');
	if ($rows && count($rows) == 1) {
		$lastdate = $rows[0]['date'];
		if ($lastdate != $todaydate) {

			// Retrieve records from weather_readings for use in daily summary.
			// This will include all records from after 09:00 yesterday up to
			// 09:00 today.
			$nineamyesterday = clone $nineamtoday;
			$nineamyesterday->sub(new DateInterval('P1D'));
			$where = 'dateutc > \''.$nineamyesterday->format('Y-m-d H:i:s').'\' and dateutc <= \''.$nineamtoday->format('Y-m-d H:i:s').'\'';

			$rows = $db->fetchAll('weather_readings', $where);

			// $daily will contain all properties for inclusion in the weather_daily
			// record.
			$daily = array();
			$daily['date'] = '\''.$todaydate.'\'';

			// $lastdateutc should always represent the baseline for calculating a
			// time interval between two reading records. After the first record, it
			// will always be the datetime for the previous record. On the first 
			// iteration, use 09:00 as the baseline.
			$lastdateutc = $nineamyesterday;

			// Add the intervals to each reading record. These intervals allow each 
			// reading to be weighted proportionately for calculating mean values.
			foreach ($rows as &$row) {
				$row['dateutcobj'] = DateTime::createFromFormat('Y-m-d H:i:s', $row['dateutc'], new DateTimeZone('UTC'));
				$row['interval'] = $row['dateutcobj']->getTimestamp() - $lastdateutc->getTimestamp();
				$lastdateutc = $row['dateutcobj'];
			}

			// List the properties to be processed from the reading record
			$tocount = [ 
				'temperature' => 'range+mean', 
				'humidity' => 'range+mean', 
				'relativepressure' => 'range+mean', 
				'absolutepressure' => 'range+mean', 
				'soilmoisture' => 'range+mean', 
				'winddir' => 'winddirection', 
				'windspeed' => 'mean',
				'windgust' => 'max', 
				'rainrate' => 'count', 
				'raindaily' => 'raindaily', 
				'solarradiation' => 'max+sunhours', 
				'uv' => 'max'
			];

			// Rainfall is calculated by checking the value of raindaily in the reading record
			// This resets to zero at midnight. When a drop in raindaily is detected, the most 
			// recent value will be moved from $daily['rainfall'] into $prevdaily, so this can
			// be added to the total to 09:00.
			$daily['rainfall'] = 0;
			$prevraindaily = 0;
			
			// Tracks the number of seconds for which solar radiation exceeds a threshold
			$sunseconds = 0;

			// Mean wind direction is calculated by tracking x and y offsets through the day
			$windx = 0;
			$windy = 0;

			// Determine how many readings occur through the day. This will be a string 
			// reporting the number of readings received for each measurement.
			$counts = null;

			// Go through all reading records once for each measured property
			foreach ($tocount as $counting => $operation) {

				// $numreadings will contribute to the counts string. 
				// $totalvalue aggregates weighted measurements for each time interval 
				// to allow means to be calculated. 
				// $totalseconds adds up all intervals and is used to scale the final
				// calculated total value back.
				// $interval tracks the interval between processed  records. It is normally
				// just the interval for the current record, but accumulates if a reading
				// is null.
				$numreadings = 0;
				$totalvalue = 0;
				$totalseconds = 0;
				$interval = 0;

				foreach ($rows as $row) {

					$interval += $row['interval'];

					// Lookup the value we want to process
					$rowvalue = $row[$counting];

					// Skip if value is null
					if (!is_null($rowvalue)) {
						$numreadings++;

						// Perform processing specified for each measurement in the 
						// $tocount array.
						switch ($operation) {

							// Calculate min, max and accumulate weighted total to 
							// calculate mean later. The $totalvalue is the current
							// measurement multiplied by the interval.
							case 'range+mean':
								$totalvalue += $rowvalue * $interval;
								updateRange($daily, $rowvalue, $counting);
								break;

							// Only prepare for calculating the mean
							case 'mean':
								$totalvalue += $rowvalue * $interval;
								break;
							
							// Calculate max but not min
							case 'max':
								updateRange($daily, $rowvalue, $counting, true);
								break;

							// Track highest measurement for daily rainfall - if it
							// drops between records, we have passed midnight, so 
							// save the before-midnight total.
							case 'raindaily':
								if ($rowvalue < $prevraindaily) {
									$daily['rainfall'] = $prevraindaily;
								}
								$prevraindaily = $rowvalue;
								break;

							// Track intervals in seconds where the solar radiation
							// exceeds 120 W/m2
							case 'max+sunhours':
								updateRange($daily, $rowvalue, $counting, true);
								if ($rowvalue >= 120) {
									$sunseconds += $interval;
								}
								break;

							// Based on wind direction, speed and interval, accumulate
							// x and y offsets over the 24-hour period.
							case 'winddirection':
								$xvector = sin(deg2rad($row['winddir']));
								$yvector = cos(deg2rad($row['winddir']));
								$windx += $xvector * $row['windspeed'] * $interval;
								$windy += $yvector * $row['windspeed'] * $interval;
								break;
						}

						// Keep track of the total for all intervals. 
						$totalseconds += $interval;

						// This interval has been processed.
						$interval = 0;
					}
				}

				// Calculate the remaining values that depended on totals.
				switch ($operation) {

					// Calculate the mean value by scaling the total according to the
					// total interval in seconds.
					case 'range+mean':
					case 'mean':
						$daily['mean'.mapKey($counting)] = $totalvalue / $totalseconds;
						break;
							
					// Rainfall is rain since midnight plus rain before midnight minus
					// rain that fell before 09:00 the previous day.
					case 'raindaily':
						$daily['rainfall'] += $prevraindaily - $rows[0]['raindaily'];
						break;

					// Sun seconds to hours
					case 'max+sunhours':
						$daily['sunhours'] = $sunseconds / 3600;
						break;

					// Reverse engineer the mean wind direction from the x and y offsets
					// for the day.
					case 'winddirection':
						$daily['meanwinddir'] = rad2deg(atan2($windx, $windy));
						if ($daily['meanwinddir'] < 0) {
							$daily['meanwinddir'] += 360;
						}
						break;
				}

				// Add the number of readings to the counts string.
				$counts .= (is_null($counts) ? '' : ", ").$counting.': '.$numreadings;
			}

			// Add the counts string to the values for the database record.
			$daily['counts'] = "'".$counts."'";

			// Insert sunrise and sunset.
			addSunRiseAndSet($daily, $todaydate, $timezone);

			/*
			file_put_contents('post.log', "Daily values:\r\n");
			foreach ($daily as $k => $v) {
				file_put_contents('post.log', "  $k => $v\r\n", FILE_APPEND);
			}
			*/

			// Insert into the database.
			$db->insert('weather_daily', $daily);
		}
	}
}

// All good so long as we inserted the reading record.
http_response_code($success ? 200 : 500);

?>