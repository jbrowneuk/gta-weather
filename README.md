# gta-weather
A stripped-down PHP port of [adam10603/GTAWeather](https://github.com/adam10603/GTAWeather), designed to return textual information about the in-game weather at the *current time* in GTA Online lobbies.

## Usage
Require the `gta-weather.php` and call its `getForecast()` function to return a string containing the current weather forecast.

```php
<?php

// Require the library
require "./gta-weather.php";

// Optionally set the output timezone
date_default_timezone_set("UTC");

$forecast = getForecast();
```

An example usage is provided as usage.php in the `src` folder.
