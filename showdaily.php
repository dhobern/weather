<?php
/*
 * WEATHER SETUP PAGE - creates the table(s) to use with Waggies Weather.
 * Copyright for this package of code: https://waggies.net/ws/copyright.txt.
 */

include_once ('classes/db.php');

?>
<!DOCTYPE html>
<html>
<head>
<title>Daily measurements</title>
</head>
<body>

<?php

// get the DB connection
$db = new Db();
$rows = $db->fetchAll('weather_daily'); 

$items = array(
    "Date (to 09:00)" => "date",
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
    "Max irradiance (W/m<sup>2</sup>)" => "maxirradiance",
    "Max UV index" => "maxuv",
    "Sunshine hours" => "sunhours",
    "Mean wind speed (km/h)" => "meanwindspeed",
    "Mean wind direction" => "meanwinddir",
    "Max gust (km/h)" => "maxwindgust"
);

echo '<table>';
foreach ($items as $title => $column) {
    echo '<tr>';
    echo '<td>'.$title.'</td>';
    foreach ($rows as $row) {
        $value = $row[$column];
        if (!$value) { 
            $value = "-"; 
        } else if ($column == 'date') {
            $value = date('j M', strtotime( $value ));
        }
        echo '<td>'.$value.'</td>';
    }
    echo '</tr>';
}
echo '</table>';

?>
</body>
</html>