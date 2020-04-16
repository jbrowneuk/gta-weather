<?php

// Require the library
require "./gta-weather.php";

// Set the output timezone
date_default_timezone_set("UTC");

// Set the output type to utf-8 plain text and generate
@header("Content-Type: text/plain; charset=UTF-8");
print getForecast();
