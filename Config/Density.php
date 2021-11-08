<?php namespace ProcessWire;


return array(
///////Units Of Density///////
	"dimension" => new MeasurementDimension(MeasurementDimension::MASS, MeasurementDimension::LENGTH ** 3),
	"notes" => __("The base unit for density is the kilogram per cubic metre, which is the same as 'gram per litre' - which is more useful in many practical circumstances."),
	'base' => 'kilogram per cubic metre',
	'units' => array(
		"kilogram per cubic metre" => array("shortLabel" => "kg/m\u{2073}", "conversion" => 1, "plural" => __("kilograms per cubic metre")),
		"gram per litre" => array("shortLabel" => "g/L", "conversion" => 1, "plural" => "grams per litre"),
		"pound per cubic foot" => array("shortLabel" => "lb/ft\u{2073}", "conversion" => 16.018463306, "plural" => __("pounds per cubic foot")),
		"pound per gallon [UK]" => array("shortLabel" => "lb/gal [UK]", "conversion" => 99.776372663, "plural" => __("pounds per gallon [UK]")),
		"pound per gallon [US]" => array("shortLabel" => "lb/gal [US]", "conversion" => 119.82642681, "plural" => __("pounds per gallon [US]")),
		"water density [0°C solid]" => array("shortLabel" => "* water [0]", "label" => "shortPadded", "conversion" => 915),
		"water density [20°C]" => array("shortLabel" => "* water [20]", "label" => "shortPadded", "conversion" => 998.2),
		"water density [4°C]" => array("shortLabel" => "* water [4]", "label" => "shortPadded", "conversion" => 1000),
	)
);