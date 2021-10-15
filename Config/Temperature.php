<?php namespace ProcessWIre;

return array(
///////Units Of Temperature///////
	'base' => 'Kelvin',
	'units' => array(
		"Kelvin" => array("shortLabel" => "K", "conversion" => 1, "plural" => "Kelvin"), //Kelvin - base unit for temperature
		"Celsius" => array("shortLabel" => "degC", "conversion" => function ($val, $tofrom) {
			return $tofrom ? $val - 273.15 : $val + 273.15;
		}, "plural" => "Celsius"),
		"Fahrenheit" => array("shortLabel" => "degF", "conversion" => function ($val, $tofrom) {
			return $tofrom ? ($val * 9 / 5 - 459.67) : (($val + 459.67) * 5 / 9);
		}, "plural" => "Fahrenheit"),
	)
);
