<!DOCTYPE html>
<html>
    <head>
        <title>Methods for daily weather summaries</title>
        <meta charset="UTF-8">
        <meta name="description" content="Methods for daily weather summaries">
        <meta name="keywords" content="Aranda, ACT, weather, daily, measurements, temperature, humidity, pressure, soil moisture, rainfall, wind speed, wind direction">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/css/style.css?version=0.11">
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
        
        <div class="overview">
        <h2>Methods for daily weather summaries</h2>
        <p>This site was established to manage weather readings from an Ecowitt GW1002 weather station to provide basic environmental data to associate with insect recording activities within the <a href="https://stangeia.hobern.net/araba-bioscan-project/">Araba Bioscan Project</a>.</p>
        <p>The weather station receives readings from indoor and outdoor temperature and humidity sensors, an indoor pressure sensor, a soil moisture sensor, a UV and light sensor, anemometer and rain gauge.</p>
        <p>Data from the sensors is transmitted to <a href="https://www.ecowitt.net/home/index?id=30847">ecowitt.net</a> (login required). Readings are also collected by this site every five minutes.</p>
        <p>The following measurements for each time interval are stored here:</p>
        <ul>
            <li>UTC date/time</li>
            <li>Outdoor temperature in Centigrade</li>
            <li>Outdoor humidity as a percentage</li>
            <li>Relative pressure (adjusted for an altitude of 660 m) in hectopascals</li>
            <li>Absolute pressure in hectopascals</li>
            <li>Wind direction in degrees</li>
            <li>Wind speed in kilometers per hour</li>
            <li>Wind gust in kilometers per hour</li>
            <li>Rain rate in millimeters</li>
            <li>Rain total daily in millimeters (resets at 00:00)</li>
            <li>Solar radiation in watts per square meter</li>
            <li>UV index</li>
            <li>Soil moisture as a percentage</li>
        </ul>
        <p>A daily summary record is generated at 09:00 a.m. Canberra time. This summary includes the following calculated values for the 24-hour period:</p>
        <ul>
            <li>Local date</li>
            <li>Maximum recorded outdoor temperature in Centigrade</li>
            <li>Minimum recorded outdoor temperature in Centigrade</li>
            <li>Mean recorded outdoor temperature in Centigrade (see Note 1 below)</li>
            <li>Maximum recorded outdoor humidity as a percentage</li>
            <li>Minimum recorded outdoor humidity as a percentage</li>
            <li>Mean recorded outdoor humidity as a percentage (see Note 1 below)</li>
            <li>Maximum recorded soil moisture as a percentage</li>
            <li>Minimum recorded soil moisture as a percentage</li>
            <li>Mean recorded soil moisture as a percentage (see Note 1 below)</li>
            <li>Maximum recorded relative pressure in hectopascals</li>
            <li>Minimum recorded relative pressure in hectopascals</li>
            <li>Mean recorded relative pressure in hectopascals (see Note 1 below)</li>
            <li>Maximum recorded absolute pressure in hectopascals</li>
            <li>Minimum recorded absolute pressure in hectopascals</li>
            <li>Mean recorded absolute pressure in hectopascals (see Note 1 below)</li>
            <li>Mean wind direction in degrees (see Notes 1 and 2 below)</li>
            <li>Mean wind speed in kilometers per hour (see Note 1 below)</li>
            <li>Maximum wind gust in kilometers per hour</li>
            <li>Maximum solar radiation in watts per square meter</li>
            <li>Maximum UV index</li>
            <li>Sun hours (see Note 3 below)</li>
            <li>Rain total daily in millimeters (see Note 4 below)</li>
        </ul>
        <h3>Notes</h3>
        <p>Sunrise and sunset times are included from the <a href="https://sunrise-sunset.org/api" target="_blank">Sunrise-Sunset api</a>. These relate to the listed date.</p>
        <p><strong>Note 1 - calculation of means:</strong> All mean values are calculated using a separate weighting for each included measurement proportional to the time interval since the previous measurement. This is to compensate for the possibility of series of missed readings.</p>
        <p><strong>Note 2 - calculation of wind direction:</strong> Wind direction is calculated using weightings for each time interval and the associated wind speed and direction to derive a series of x and y offsets that accumulate through the day. The final wind direction is calculated using the arctangent of the ratio of the total x and y distances.</p>
        <p><strong>Note 3 - calculation of sun hours:</strong> The sun hour measurement represents the total length of all intervals for which the associated solar radiation measurement was greater than 120 m/W<sup>2</sup>.</p>
        <p><strong>Note 4 - calculation of rain totals:</strong> The rain total represents the rainfall recorded up to midnight, plus the rainfall recorded up to 09:00 on the day in question, minus any rainfall up to 09:00 the previous day.</p>
        <h3>Source code</h3>
        <p>Source code (in PHP) is available from a GitHub repository: <a href="https://github.com/dhobern/weather">dhobern/weather</a>.</p>
        </div>
        
        <div class="footer">
            <div class="cc-logo"><a rel="license" href="http://creativecommons.org/licenses/by/4.0/"><img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by/4.0/88x31.png" /></a>&nbsp;All content offered under <a rel="license" href="http://creativecommons.org/licenses/by/4.0/">Creative Commons Attribution 4.0 International License</a>, Donald Hobern, <?php echo date("Y"); ?>.</div>
        </div>
    </body>
</html>