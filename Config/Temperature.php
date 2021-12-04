<?php namespace MetaTunes\MeasurementClasses;
use function ProcessWire\__;

return array(
///////Units Of Temperature///////
	"dimension" => new Dimension([Dimension::TEMPERATURE => 1]),
	'base' => 'Kelvin',
	'units' => array(
		"Kelvin" => array("shortLabel" => "K", "conversion" => 1, "plural" => "Kelvin"), //Kelvin - base unit for temperature
		"Celsius" => array("shortLabel" => "degC", "conversion" => function ($val, $tofrom) {
			try {
			return $tofrom ? $val - 273.15 : $val + 273.15;
			} catch (\Exception $e) {
				d($e->getMessage());
				return 0;
			}
		}, "plural" => "Celsius"),
		"Fahrenheit" => array("shortLabel" => "degF", "conversion" => function ($val, $tofrom) {
			try {
				return $tofrom ? ($val * 9 / 5 - 459.67) : (($val + 459.67) * 5 / 9);
			} catch (\Exception $e) {
				d($e->getMessage());
				return 0;
			}
		}, "plural" => "Fahrenheit"),
	)
);
