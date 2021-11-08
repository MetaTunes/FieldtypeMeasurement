<?php namespace ProcessWire;

return array(
///////Units Of Mass///////
	"dimension" => new MeasurementDimension(MeasurementDimension::MASS, 1),
	'base' => 'kilogram',
	'units' => array(
		"kilogram" => array("shortLabel" => "kg", "conversion" => 1), //kilogram - base unit for mass
		"gram" => array("shortLabel" => "g", "conversion" => 0.001),
		"milligram" => array("shortLabel" => "mg", "conversion" => 0.000001),
		"microgram" => array("shortLabel" => "μg", "conversion" => 0.000000001),
		"stone" => array("shortLabel" => "st", "conversion" => 6.35029),
		"pound" => array("shortLabel" => "lb", "conversion" => 0.453592),
		"ounce" => array("shortLabel" => "oz", "conversion" => 0.0283495),
		"tonne" => array("shortLabel" => "t", "conversion" => 1000),
		"long ton" => array("shortLabel" => "ton", "conversion" => 1016.047),
		"UK ton" => array("shortLabel" => "UK ton", "conversion" => 1016.047),
		"short ton" => array("shortLabel" => "sht ton", "conversion" => 907.1847),
		"US ton" => array("shortLabel" => "US ton", "conversion" => 907.1847),
		"grain" => array("shortLabel" => "gn", "conversion" => 0.00006479891),
		"stone|pound" => array(            // pipe join is required
			"shortLabel" => "st|lb",
			"conversion" => function($val, $tofrom) {
				// value is an array for combi-type units
				if($tofrom) {
					// $val is the base unit magnitude - so return an array
					$convert = $val / 6.35029;
					$st = intval($convert);
					$lb = ($convert - $st) * 14;
					return [$st, $lb];
				} else {
					// $val is an array [st, lb] - so return a single value for the base unit
					$st = $val[0] + ($val[1] / 14);
					return $st * 6.35029;
				}
			},
			"join" => [" "],
			"plural" => "feet|inches"
		)
	)
);