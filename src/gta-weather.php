<?php

/*
 * This is a PHP port of https://github.com/adam10603/GTAWeather
 *
 * It is a stripped-down version, designed to return information about the
 * in-game weather at the current time in GTA Online lobbies.
 */

$weatherPeriod     = 384; // Weather period in in-game hours
$gameHourLength    = 120; // 1 in-game hour in seconds
$sunriseTime       = 6;   // Time of sunset and sunrise, as in-game hour of day
$sunsetTime        = 21;

// =============================================================================
// Acts as an enumeration for all weather states
// =============================================================================
abstract class WeatherState {
  static $clear =          array("name" => "Clear",         "emoji" => "☀️");
  static $rain =           array("name" => "Raining",       "emoji" => "🌧");
  static $drizzle =        array("name" => "Drizzling",     "emoji" => "🌦");
  static $mist =           array("name" => "Misty",         "emoji" => "🌁");
  static $fog =            array("name" => "Foggy",         "emoji" => "🌫");
  static $haze =           array("name" => "Hazy",          "emoji" => "🌫");
  static $snow =           array("name" => "Snowy",         "emoji" => "❄️");
  static $cloudy =         array("name" => "Cloudy",        "emoji" => "☁️");
  static $mostlyCloudy =   array("name" => "Mostly cloudy", "emoji" => "🌥");
  static $partlyCloudy =   array("name" => "Partly cloudy", "emoji" => "⛅");
  static $mostlyClear =    array("name" => "Mostly clear",  "emoji" => "🌤");
};

// =============================================================================
// Weather lookup table
// =============================================================================
$weatherStateChanges = array(
  array(0,   WeatherState::$partlyCloudy),
  array(4,   WeatherState::$mist),
  array(7,   WeatherState::$mostlyCloudy),
  array(11,  WeatherState::$clear),
  array(14,  WeatherState::$mist),
  array(16,  WeatherState::$clear),
  array(28,  WeatherState::$mist),
  array(31,  WeatherState::$clear),
  array(41,  WeatherState::$haze),
  array(45,  WeatherState::$partlyCloudy),
  array(52,  WeatherState::$mist),
  array(55,  WeatherState::$cloudy),
  array(62,  WeatherState::$fog),
  array(66,  WeatherState::$cloudy),
  array(72,  WeatherState::$partlyCloudy),
  array(78,  WeatherState::$fog),
  array(82,  WeatherState::$cloudy),
  array(92,  WeatherState::$mostlyClear),
  array(104, WeatherState::$partlyCloudy),
  array(105, WeatherState::$drizzle),
  array(108, WeatherState::$partlyCloudy),
  array(125, WeatherState::$mist),
  array(128, WeatherState::$partlyCloudy),
  array(131, WeatherState::$rain),
  array(134, WeatherState::$drizzle),
  array(137, WeatherState::$cloudy),
  array(148, WeatherState::$mist),
  array(151, WeatherState::$mostlyCloudy),
  array(155, WeatherState::$fog),
  array(159, WeatherState::$clear),
  array(176, WeatherState::$mostlyClear),
  array(196, WeatherState::$fog),
  array(201, WeatherState::$partlyCloudy),
  array(220, WeatherState::$mist),
  array(222, WeatherState::$mostlyClear),
  array(244, WeatherState::$mist),
  array(246, WeatherState::$mostlyClear),
  array(247, WeatherState::$rain),
  array(250, WeatherState::$drizzle),
  array(252, WeatherState::$partlyCloudy),
  array(268, WeatherState::$mist),
  array(270, WeatherState::$partlyCloudy),
  array(272, WeatherState::$cloudy),
  array(277, WeatherState::$partlyCloudy),
  array(292, WeatherState::$mist),
  array(295, WeatherState::$partlyCloudy),
  array(300, WeatherState::$mostlyCloudy),
  array(306, WeatherState::$partlyCloudy),
  array(318, WeatherState::$mostlyCloudy),
  array(330, WeatherState::$partlyCloudy),
  array(337, WeatherState::$clear),
  array(367, WeatherState::$partlyCloudy),
  array(369, WeatherState::$rain),
  array(376, WeatherState::$drizzle),
  array(377, WeatherState::$partlyCloudy)
);

// =============================================================================
// Convenience function to test whether a weather state is raining
// =============================================================================
function isRaining($state) {
  return $state == WeatherState::$rain || $state == WeatherState::$drizzle;
}

// =============================================================================
// Calculates the ETA until it rains
// =============================================================================
function getRainEta($periodTime, $currentWeather) {
  global $weatherPeriod, $weatherStateChanges, $gameHourLength;

  if ($periodTime > $weatherPeriod || $periodTime < 0) {
    return null;
  }

  $raining = isRaining($currentWeather);
  $eta = null;

  $weatherStateLength = count($weatherStateChanges);
  for ($index = 0; $index < $weatherStateLength * 2; $index++) {
    $stateIndex = $index % $weatherStateLength;
    $offset = floor($index / $weatherStateLength) * $weatherPeriod;
    if($weatherStateChanges[$stateIndex][0] + $offset >= $periodTime) {
      if ($raining ^ isRaining($weatherStateChanges[$stateIndex][1])) {
        $eta = (($weatherStateChanges[$stateIndex][0] + $offset) - $periodTime) * $gameHourLength;
        break;
      }
    }
  }

  return array(
    "etaSec" => $eta,
    "isRaining" => $raining
  );
}

// =============================================================================
// Calculates the weather state for a time period
// =============================================================================
function getWeatherForPeriodTime($periodTime) {
  global $weatherPeriod, $weatherStateChanges;

  $ret = null;
  if ($periodTime > $weatherPeriod || $periodTime < 0) {
    return null;
  }

  for ($i = 0; $i < count($weatherStateChanges); $i++) {
      if ($weatherStateChanges[$i][0] > $periodTime) {
          $ret = $weatherStateChanges[$i - 1][1];
          break;
      }
  }
  
  if ($ret == null) {
    $ret = $weatherStateChanges[count($weatherStateChanges) - 1][1];
  }

  return $ret;
}

// =============================================================================
// Gets the in-game time for the current real-world time
// =============================================================================
function getGtaTime() {
  global $gameHourLength, $weatherPeriod;

  $timestamp           = time();
  $gtaHoursTotal       = $timestamp / $gameHourLength;
  $gtaHoursDay         = $gtaHoursTotal % 24.0;

  return array(
      "gameTimeHrs" =>       $gtaHoursDay,
      "weatherPeriodTime" => $gtaHoursTotal % $weatherPeriod
  );
}

// =============================================================================
// Formats a number of seconds into a human-readable string
// =============================================================================
function secToVerboseInterval($seconds) {
  if ($seconds < 60) {
    return "Less than a minute";
  }

  $sMod60  = $seconds % 60;
  $hours   = floor($seconds / 3600 + ($sMod60 / 3600));
  $minutes = floor(($seconds - ($hours * 3600)) / 60 + ($sMod60 / 60));

  $hoursFormatted   = "$hours hour" . ($hours == 1 ? "" : "s") . " ";
  $minutesFormatted = "$minutes minute" . ($minutes == 1 ? "" : "s");

  $ret = "";
  if ($hours > 0) {
    $ret .= $hoursFormatted;
  }

  if ($minutes > 0) {
    $ret .= $minutesFormatted;
  }

  return trim($ret);
}

// =============================================================================
// Main function. Call this to receive the formatted forecast
// =============================================================================
function getForecast() {
  $gtaTime = getGtaTime();

  $currentWeather = getWeatherForPeriodTime($gtaTime["weatherPeriodTime"]);
  if ($currentWeather == null) {
    print "Couldn’t calculate weather";
    return;
  }

  $rainEta = getRainEta($gtaTime["weatherPeriodTime"], $currentWeather);
  if ($rainEta == null) {
    print "Couldn’t calculate rain ETA";
    return;
  }

  $timeStr = date("j F Y H:i:s e");
  $rainEtaStr = secToVerboseInterval($rainEta["etaSec"]);
  return "Forecast for $timeStr {$currentWeather["emoji"]} {$currentWeather["name"]}. Rain in {$rainEtaStr}.";
}
