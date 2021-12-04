<?php namespace MetaTunes\MeasurementClasses;
use function ProcessWire\__;
return array(
///////Units Of Angle///////

	"dimension" => new Dimension(),
	'base' => 'radian',
	'units' => array(
		"radian" => array("shortLabel" => "rad", "conversion" => 1),
		"degree" => array("shortLabel" => "deg", "conversion" =>  0.01745329252),
		"minute" => array("shortLabel" => "min", "conversion" => 0.00029088820867),
		"second" => array("shortLabel" => "sec", "conversion" =>  0.0000048481368111),
		"degree|minute|second" => array(
			"shortLabel" => "deg|min|sec",
			"conversion" => function($val, $tofrom) {
				// value is an array for combi-type units
				if($tofrom) {
					// $val is the base unit magnitude - so return an array
					$convert = $val / 0.01745329252;
					$deg = intval($convert);
					$convert2 = ($convert - $deg) * 60;
					$min = intval($convert2);
					$sec = ($convert2 - $min) * 60;
					return [$deg, $min, $sec];
				} else {
					// $val is an array [deg, min, sec] - so return a single value for the base unit
					$deg = $val[0] + ($val[1] / 60) + ($val[2] / 3600);
					return $deg * 0.01745329252;
				}
			},
			"join" => [" ", " "],
		),

	)
);