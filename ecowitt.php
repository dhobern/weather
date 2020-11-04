<?php

include_once ('classes/db.php');

function mapKey($keybase) {
	switch($keybase) {
		case 'temperature': $keybase = 'temp'; break;
		case 'humidity': $keybase = 'humid'; break;
		case 'soilmoisture': $keybase = 'soilmoist'; break;
		case 'absolutepressure': $keybase = 'abspress'; break;
		case 'relativepressure': $keybase = 'relpress'; break;
	}
	return $keybase;
}

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

				$lastdateutc = $nineamyesterday;

				// file_put_contents('include.log', "Included readings:\r\n");

				foreach ($rows as &$row) {
					$row['dateutcobj'] = DateTime::createFromFormat('Y-m-d H:i:s', $row['dateutc'], new DateTimeZone('UTC'));
					$row['interval'] = $row['dateutcobj']->getTimestamp() - $lastdateutc->getTimestamp();
					$lastdateutc = $row['dateutcobj'];
					// file_put_contents('include.log', "  ".$row['dateutc']." => ".$row['dateutcobj']->format('Y-m-d H:i:s')."\r\n", FILE_APPEND);
				}

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

				$daily['raindaily'] = 0;
				$prevraindaily = 0;
				$sunseconds = 0;
				$windx = 0;
				$windy = 0;
				$counts = null;

				foreach ($tocount as $counting => $operation) {
					$numreadings = 0;
					$totalvalue = 0;
					$totalseconds = 0;
					$interval = 0;
					foreach ($rows as $row) {
						$interval += $row['interval'];
						$rowvalue = $row[$counting];
						if (!is_null($rowvalue)) {
							$numreadings++;
							switch ($operation) {

								case 'range+mean':
									$totalvalue += $rowvalue * $interval;
									updateRange($daily, $rowvalue, $counting);
									break;

								case 'mean':
									$totalvalue += $rowvalue * $interval;
									break;
								
								case 'max':
									updateRange($daily, $rowvalue, $counting, true);
									break;

								case 'raindaily':
									if ($value < $prevraindaily) {
										$daily['raindaily'] = $prevraindaily;
									}
									$prevraindaily = $value;
									break;
	
								case 'max+sunhours':
									updateRange($daily, $rowvalue, $counting, true);
									if ($rowvalue >= 120) {
										$sunseconds += interval;
									}
									break;
	
								case 'winddirection':
									$xvector = sin(deg2rad($row['winddir']));
									$yvector = cos(deg2rad($row['winddir']));
									$windx += $xvector * $row['windspeed'] * $interval;
									$windy += $yvector * $row['windspeed'] * $interval;
									break;
							}
							$totalseconds += $interval;
							$interval = 0;
						}
					}
					switch ($operation) {
						case 'range+mean':
						case 'mean':
							$daily['mean'.mapKey($counting)] = $totalvalue / $totalseconds;
							break;
							 
						case 'raindaily':
							$daily['raindaily'] += $prevraindaily;
							break;

						case 'max+sunhours':
							$daily['sunhours'] = $sunseconds / 3600;
							break;

						case 'winddirection':
							$daily['meanwinddir'] = rad2deg(atan2($windy, $windx));
							if ($daily['meanwinddir'] < 0) {
								$daily['meanwinddir'] += 360;
							}
							break;
					}
					$counts .= (is_null($counts) ? '' : ", ").ucfirst($counting).': '.$numreadings;
				}
				$daily['counts'] = $counts;

				/*
				file_put_contents('post.log', "Daily values:\r\n");
				foreach ($daily as $k => $v) {
					file_put_contents('post.log', "  $k => $v\r\n", FILE_APPEND);
				}
				*/

				$db->insert('weather_daily', $daily);
			}
		}
	}

	http_response_code($success ? 200 : 500);

?>