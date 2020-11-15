<!DOCTYPE html>
<?php

/** 
 * Display page for weather readings
 * 
 * By default, displays record for current date from weather_daily, 
 * along with the set of records from weather_readings that 
 * contributed to it. If date is provided as a GET parameter,
 * displays equivalent for that date, if available.
 * 
 * @author  Donald Hobern
 * @version $Revision: 1.0 $
 * @access public 
 */

include_once ('classes/db.php');
?>
<html>
    <head>
        <title>Daily Weather Summary for Araba Street, Aranda, ACT</title>
        <meta charset="UTF-8">
        <meta name="description" content="Daily Weather Summary for Araba Street, Aranda, ACT">
        <meta name="keywords" content="Aranda, ACT, weather, daily, measurements, temperature, humidity, pressure, soil moisture, rainfall, wind speed, wind direction">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/css/style.css?version=0.11        ">
    </head>
    <body>
        <header>
            <nav id="navbar">
                <div class="site-name">
                    <a href="/"><img src="/img/Chondropyga_dorsalis.png">Aranda Daily Weather</a>
                </div>
                <div class="site-menu">
                    <div class="site-menu-item">
                        <a href="https://stangeia.hobern.net/">Biodiversity and informatics<img src="/img/Drepanacra_binocula.png"></a>
                    </div>
                    <div class="site-menu-item">
                        <a href="https://stangeia.hobern.net/araba-bioscan-project/">Araba Bioscan Project<img src="/img/Phellus_glaucus.png"></a>
                    </div>
                    <div class="site-menu-item">
                        <a href="https://www.hobern.net/">Home<img src="/img/Kiwaia_jeanae.png"></a>
                    </div>
                </div>
            </nav>
        </header>
        
        <div class="daily-summary">

            <?php

            $timezone = new DateTimeZone('Australia/Canberra');

            $focusdate = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
            $use_get = true;

            if (!$focusdate) {
                $focusdate = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
                if ($focusdate) {
                    $use_get = false;
                }
            }

            $focus = null;

            if ($focusdate) {
                $focus = DateTime::createFromFormat('Y-m-d', $focusdate, $timezone);
            } else {
                $focus = new DateTime();
                $focus->setTimestamp(time());
                $focus->setTimezone($timezone);
                $focusdate = $focus->format('Y-m-d');
            }

            $prev = clone $focus;
            $prev->sub(new DateInterval('P1D'));
            $prevdate = $prev->format('Y-m-d');

            $next = clone $focus;
            $next->add(new DateInterval('P1D'));
            $nextdate = $next->format('Y-m-d');

            echo "<h2>Daily Summary for ".$focus->format("j F Y")."</h2>".PHP_EOL;
            echo "<p><a href=\"index.php?date=$prevdate\">&#9668;&nbsp;".$prev->format("j F Y")."</a>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"index.php?date=$nextdate\">".$next->format("j F Y")."&nbsp;&#9658;</a></p>".PHP_EOL;

            // get the DB connection
            $db = new Db();
            $rows = $db->fetchAll('weather_daily', "date >= '".$prevdate."' and date <= '".$nextdate."'");

            $focusrow = null;
            $prevrow = null;
            $nextrow = null;
            foreach ($rows as $row) {
                if ($row['date'] == $focusdate) {
                    $focusrow = $row;
                } else if ($row['date'] == $prevdate) {
                    $prevrow = $row;
                } else if ($row['date'] == $nextdate) {
                    $nextrow = $row;
                }
            }

            $items = array(
				"Date" => "date",
				"Sunrise" => "sunrise",
				"Sunset" => "sunset",
				"Mean temperature (&deg;C)" => "meantemp",
				"Max temperature (&deg;C)" => "maxtemp",
				"Min temperature (&deg;C)" => "mintemp",
				"Mean humidity (%)" => "meanhumid",
				"Max humidity (%)" => "maxhumid",
				"Min humidity (%)" => "minhumid",
				"Mean soil moisture (%)" => "meansoilmoist",
				"Max soil moisture (%)" => "maxsoilmoist",
				"Min soil moisture (%)" => "minsoilmoist",
				"Mean relative pressure (hPa)" => "meanrelpress",
				"Max relative pressure (hPa)" => "maxrelpress",
				"Min relative pressure (hPa)" => "minrelpress",
				"Mean absolute pressure (hPa)" => "meanabspress",
				"Max absolute pressure (hPa)" => "maxabspress",
				"Min absolute pressure (hPa)" => "minabspress",
				"Daily rainfall (mm)" => "rainfall",
				"Sunshine hours" => "maxtemp",
				"Max solar radiation (W/m<sup>2</sup>)" => "maxsolrad",
				"Max UV index" => "maxuv",
				"Sunshine hours" => "sunhours",
				"Mean wind speed (km/h)" => "meanwindspeed",
				"Mean wind direction" => "meanwinddir",
				"Max gust (km/h)" => "maxwindgust"
			);

            echo '<table style="max-width:800px;margin-top:20px;">';
            foreach ($items as $title => $column) {
				echo '<tr>';
                echo '<td><strong>'.$title.'</strong></td>';
                $value = "-";
                $align = "center"; 
                if ($column == 'date') {
                    $value = $focus->format('j F Y');
                    $align = "center"; 
                } else if ($focusrow && $focusrow[$column]) {
					$value = $focusrow[$column] ;
					$align = "right";
                }
                echo '<td style="text-align:'.$align.'">'.$value.'</td>';
				echo '</tr>';
			}
			echo '</table>';

            echo '<br><p>Sunrise and sunset sourced from <a href="https://sunrise-sunset.org/api" '
                    .'target="_blank">Sunrise-Sunset</a> and relate to the listed date.</p>'.PHP_EOL;
            echo '<p>All other measurements are calculated for the 24-hour period to 09:00.</p>'.PHP_EOL;
            echo '<br><p>See the <strong><a href="/methods.php">Methods</a></strong> page for more details.</p>'.PHP_EOL;
        ?>

        </div>

        <?php

        $nine_am_focus = DateTime::createFromFormat('Y-m-d H:i:s', $focusdate.' 09:00:00', $timezone);
        $nine_am_focus->setTimezone(new DateTimeZone('UTC'));

        $nine_am_prev = clone $nine_am_focus;
        $nine_am_prev->sub(new DateInterval('P1D'));
        $where = 'dateutc > \''.$nine_am_prev->format('Y-m-d H:i:s').'\' and dateutc <= \''.$nine_am_focus->format('Y-m-d H:i:s').'\'';

        $rows = $db->fetchAll('weather_readings', $where);

        if ($rows) {
            echo '<div class="readings">'.PHP_EOL;
            echo '<h2>Instrument readings</h2>'.PHP_EOL;
            echo '<table style="font-size:75%;max-width:100%;margin-top:20px;">'.PHP_EOL;
            echo '<tr>'.PHP_EOL;

            foreach ($rows[0] as $k => $v) {
                $label = ucfirst($k);
                switch ($k) {
                    case 'dateutc': $label = "UTC date/time"; break;
                    case 'relativepressure': $label = "Relative pressure"; break;
                    case 'absolutepressure': $label = "Absolute pressure"; break;
                    case 'winddir': $label = "Wind direction"; break;
                    case 'windspeed': $label = "Wind speed"; break;
                    case 'windgust': $label = "Wind gust"; break;
                    case 'rainrate': $label = "Rain rate"; break;
                    case 'raindaily': $label = "Rain daily"; break;
                    case 'solarradiation': $label = "Solar radiation"; break;
                    case 'uv': $label = "UV"; break;
                    case 'soil moisture': $label = "Soil moisture"; break;
                }
                echo "<td style=\"text-align:center\"><strong>$label</strong></td>";
                if ($k == 'dateutc') {
                    echo "<td style=\"text-align:center\"><strong>Local date/time</strong></td>";
                }
            }
            echo '</tr>'.PHP_EOL;
            $utc_timezone = new DateTimeZone('UTC');
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $k => $v) {
                    $align = null;
                    if (is_null($v)) { 
                        $v = "-";
                        $align = "center"; 
                    } else if ($column == 'dateutc') {
                        $align = "center"; 
                    } else {
                        $align = "right";
                    }
                    echo '<td style="text-align:'.$align.'">'.$v.'</td>';
                    if ($k == 'dateutc') {
                        $local = "-";
                        if ($v != '-') {
                            $recorddate = DateTime::createFromFormat('Y-m-d H:i:s', $v, $utc_timezone);
                            $recorddate->setTimeZone($timezone);
                            $local = $recorddate->format("Y-m-d H:i:s");
                        }
                        echo '<td style="text-align:center">'.$local.'</td>';
                    }
                }
                echo '</tr>'.PHP_EOL;
            }
            echo '</table>'.PHP_EOL;
            echo '</div>'.PHP_EOL;
        }
        ?>
        
        <div class="footer">
            <div class="cc-logo"><a rel="license" href="http://creativecommons.org/licenses/by/4.0/"><img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by/4.0/88x31.png" /></a>&nbsp;All content offered under <a rel="license" href="http://creativecommons.org/licenses/by/4.0/">Creative Commons Attribution 4.0 International License</a>, Donald Hobern, <?php echo date("Y"); ?>.</div>
        </div>

        <script> 
        document.body.addEventListener('keydown', function(event) { 
            const key = event.key; 
            switch (key) { 
                case "ArrowLeft": 
                    window.location.href = "https://weather.hobern.net/index.php?date=<?php echo $prevdate; ?>"; 
                    break; 
                case "ArrowRight": 
                    window.location.href = "https://weather.hobern.net/index.php?date=<?php echo $nextdate; ?>"; 
                    break; 
            } 
        }); 
    </script> 
    </body>
</html>
