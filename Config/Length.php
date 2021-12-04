<?php namespace MetaTunes\MeasurementClasses;
use function ProcessWire\__;

return array(
	///////Units Of Length///////
	"dimension" => new Dimension([Dimension::LENGTH => 1]),
	"notes" => "The base unit for Length is the metre, which is an SI unit defined as the length of the path travelled by light in a vacuum in 1/299792458 of a second.",
	"base" => 'metre',
	"units" => array(
		"metre" => array("shortLabel" => "m", "conversion" => 1), //metre - base unit for distance
		"kilometre" => array("shortLabel" => "km", "conversion" => 1000),
		"decimetre" => array("shortLabel" => "dm", "conversion" => 0.1),
		"centimetre" => array("shortLabel" => "cm", "conversion" => 0.01),
		"millimetre" => array("shortLabel" => "mm", "conversion" => 0.001),
		"micrometre" => array("shortLabel" => "µm", "conversion" => 0.000001),
		"nanometre" => array("shortLabel" => "nm", "conversion" => 0.000000001),
		"picometre" => array("shortLabel" => "pm", "conversion" => 0.000000000001),
		"inch" => array("shortLabel" => "in", "conversion" => 0.0254, "plural" => "inches"),
		"foot" => array("shortLabel" => "ft", "conversion" => 0.3048, "plural" => "feet"),
		"yard" => array("shortLabel" => "yd", "conversion" => 0.9144),
		"furlong" => array("shortLabel" => "Fur", "conversion" => 201.168),
		"mile" => array("shortLabel" => "mi", "conversion" => 1609.344),
		"hand" => array("shortLabel" => "h", "conversion" => 0.1016),
		"light-year" => array("shortLabel" => "ly", "conversion" => 9460730472580800),
		"astronomical unit" => array("shortLabel" => "au", "conversion" => 149597870700),
		"parsec" => array("shortLabel" => "pc", "conversion" => 3.08567782E16),
		"foot|inch" => array(            // pipe join is required
			"shortLabel" => "ft|in",
			"conversion" => function($val, $tofrom) {
				// value is an array for combi-type units
				if($tofrom) {
					// $val is the base unit magnitude - so return an array
					$convert = $val / .3048;
					$ft = intval($convert);
					$in = ($convert - $ft) * 12;
					return [$ft, $in];
				} else {
					// $val is an array [ft, in] - so return a single value for the base unit
					$ft = $val[0] + ($val[1] / 12);
					return $ft * 0.3048;
				}
			},
			"join" => [" "],
			"plural" => "feet|inches"
		)
	)
);