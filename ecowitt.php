<?php

include_once ('classes/db.php');

	$data = array();

	$timezone = new DateTimeZone('Australia/Canberra');

	$datetime = DateTime::createFromFormat('Y-m-d H:i:s', $_POST['dateutc']);

	$data['dateutc'] = '\''.$datetime->format('Y-m-d H:i:s').'\'';
	$data['temperature'] = (($_POST['tempf'] - 32) * 5) / 9;
	$data['humidity'] = $_POST['humidity'];
	$data['relativepressure'] = $_POST['baromrelin'] / 0.02953;
	$data['absolutepressure'] = $_POST['baromabsin'] / 0.02953;
	$data['winddir'] = $_POST['winddir'];
	$data['windspeed'] = $_POST['windspeedmph'] * 1.60934;
	$data['windgust'] = $_POST['windgustmph'] * 1.60934;
	$data['rainrate'] = $_POST['rainratein'] * 2.54;
	$data['raindaily'] = $_POST['dailyrainin'] * 2.54;
	$data['solarradiation'] = $_POST['solarradiation'];
	$data['uv'] = $_POST['uv'];
	$data['soilmoisture'] = $_POST['soilmoisture1'];

	$db = new Db();
	$success = $db->insert('weather_readings', $data); 
	$now = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'));
	$now->setTimezone($timezone);
	$todaydate = $now->format('Y-m-d');
	$nineamtoday = DateTime::createFromFormat('Y-m-d H:i:s', $todaydate.' 09:00:00', $timezone);
	$nineamtoday->setTimezone(new DateTimeZone('UTC'));
	$timediff = $datetime->getTimestamp() - $nineamtoday->getTimestamp();
	// file_put_contents('post.log', 'Nine am today: '.$nineamtoday->format('Y-m-d H:i:s')."\r\n", FILE_APPEND);
	// file_put_contents('post.log', 'Time difference: '.$timediff."\r\n", FILE_APPEND);

	if ($timediff >= 0 && $timediff < 3600) {
		$rows = $db->fetchLastN('weather_daily');
		if ($rows && count($rows) == 1) {
			$lastdate = $rows[0]['date'];
			if ($lastdate != $todaydate) {
				$nineamyesterday = clone $nineamtoday;
				$nineamyesterday->sub(new DateInterval('P1D'));
				$where = 'dateutc > \''.$nineamyesterday->format('Y-m-d H:i:s').'\' and dateutc <= \''.$nineamtoday->format('Y-m-d H:i:s').'\'';

				$rows = $db->fetchAll('weather_readings', $where);

				$daily = array();
				$daily['date'] = '\''.$todaydate.'\'';
				$firstTime = true;

				$lastdateutc = $nineamyesterday;
				$totalseconds = 0;
				$sunseconds = 0;
				$rainbeforestart;
				$rainbeforemidnight = 0;
				$totalrainfall = 0;
				$totaltemp = 0;
				$totalhumid = 0;
				$totalsoilmoist = 0;
				$totalrelpres = 0;
				$totalabspress = 0;
				$totalwindspeed = 0;
				$windx = 0;
				$windy = 0;

				foreach ($rows as $row) {
					$dateutc = DateTime::createFromFormat('Y-m-d H:i:s', $row['dateutc'], new DateTimeZone('UTC'));
					$interval = $dateutc->getTimestamp() - $lastdateutc->getTimestamp(); 
					$totalseconds += $interval;

					if ($firstTime) {
						$rainbeforestart = $row['raindaily'];
						$daily['mintemp'] = $daily['maxtemp'] = $row['temperature'];
						$daily['minhumid'] = $daily['maxhumid'] = $row['humidity'];
						$daily['minsoilmoist'] = $daily['maxsoilmoist'] = $row['soilmoisture'];
						$daily['minrelpress'] = $daily['maxrelpress'] = $row['relativepressure'];
						$daily['minabspress'] = $daily['maxabspress'] = $row['absolutepressure'];
						$daily['maxsolrad'] = $row['solarradiation'];
						$daily['maxuv'] = $row['uv'];
						$daily['maxwindgust'] = $row['windgust'];
						$firstTime = false;
					} else {
						if ($row['temperature'] < $daily['mintemp']) $daily['mintemp'] = $row['temperature'];
						if ($row['humidity'] < $daily['minhumid']) $daily['minhumid'] = $row['humidity'];
						if ($row['soilmoisture'] < $daily['minsoilmoist']) $daily['minsoilmoist'] = $row['soilmoisture'];
						if ($row['relativepressure'] < $daily['minrelpress']) $daily['minrelpress'] = $row['relativepressure'];
						if ($row['absolutepressure'] < $daily['minabspress']) $daily['minabspress'] = $row['absolutepressure'];
						if ($row['temperature'] > $daily['maxtemp']) $daily['maxtemp'] = $row['temperature'];
						if ($row['humidity'] > $daily['maxhumid']) $daily['maxhumid'] = $row['humidity'];
						if ($row['soilmoisture'] > $daily['maxsoilmoist']) $daily['maxsoilmoist'] = $row['soilmoisture'];
						if ($row['relativepressure'] > $daily['maxrelpress']) $daily['maxrelpress'] = $row['relativepressure'];
						if ($row['absolutepressure'] > $daily['maxabspress']) $daily['maxabspress'] = $row['absolutepressure'];
						if ($row['solarradiation'] > $daily['maxsolrad']) $daily['maxsolrad'] = $row['solarradiation'];
						if ($row['uv'] > $daily['maxuv']) $daily['maxuv'] = $row['uv'];
						if ($row['windgust'] > $daily['maxwindgust']) $daily['maxwindgust'] = $row['windgust'];
					}
					$totaltemp += $row['temperature'] * $interval;
					$totalhumid += $row['humidity'] * $interval;
					$totalsoilmoist += $row['soilmoisture'] * $interval;
					$totalrelpress += $row['relativepressure'] * $interval;
					$totalabspress += $row['absolutepressure'] * $interval;
					$totalwindspeed += $row['windspeed'] * $interval;
					// See https://www.sciencedirect.com/science/article/abs/pii/0038092X9390075Y ...
					if ($row['solarradiation'] > 120) {
						$sunseconds += $interval;
					}
					if ($totalrainfall > 0 && $row['raindaily'] < $totalrainfall) {
						$rainbeforemidnight = $totalrainfall;
						$totalrainfall = $row['raindaily'];
					} else if ($row['raindaily'] > $totalrainfall) {
						$totalrainfall = $row['raindaily'];
					}
					$xvector = sin(deg2rad($row['winddir']));
					$yvector = cos(deg2rad($row['winddir']));
					$windx += $xvector * $row['windspeed'] * $interval;
					$windy += $yvector * $row['windspeed'] * $interval;

					$lastdateutc = $dateutc;
				}
				$daily['meantemp'] = $totaltemp / $totalseconds;
				$daily['meanhumid'] = $totalhumid / $totalseconds;
				$daily['meansoilmoist'] = $totalsoilmoist / $totalseconds;
				$daily['meanrelpress'] = $totalrelpress / $totalseconds;
				$daily['meanabspress'] = $totalabspress / $totalseconds;
				$daily['meanwindspeed'] = $totalwindspeed / $totalseconds;
				$daily['sunhours'] = $sunseconds / 3600;
				$daily['rainfall'] = $totalrainfall - $rainbeforestart + $rainbeforemidnight;
				$daily['meanwinddir'] = rad2deg(atan2($windy, $windx));
				if ($daily['meanwinddir'] < 0) {
					$daily['meanwinddir'] += 360;
				}

				$db->insert('weather_daily', $daily);
			}
		}
	}

	http_response_code($success ? 200 : 500);

?>