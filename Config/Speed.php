<?php namespace ProcessWire;

return array(
///////Units Of Speed///////
	"dimension" => new MeasurementDimension(MeasurementDimension::LENGTH, MeasurementDimension::TIME),
	'base' => 'metre per second',
	'units' => array(
		"metre per second" => array("shortLabel" => "m/s", "conversion" => 1, "plural" => "metres per second"),
		"kilometre per hour" => array("shortLabel" => "km/h", "conversion" => 1/3.6, "plural" => "kilometres per hour"),
		"mile per hour" => array("shortLabel" => "mph", "conversion" => 1.60934*1/3.6, "plural" => "miles per hour"),
		"knot" => array("shortLabel" => "knot", "conversion" =>  0.51444444444),
		"mach" => array("shortLabel" => "mach", "conversion" => 340.29, "plural" => "mach", "position" => "prepend", "label" => "shortPadded"),
		"speed of light [vacuum]" => array("shortLabel" => "*c", "conversion" => 299792458, "plural" => "times the speed of light"),
		"foot per minute" => array("shortLabel" => "ft/min", "conversion" => 0.00508),
		"furlong per fortnight" => array("shortLabel" => "Fur/Fortn", "conversion" =>  0.0001663098545, "plural" => "furlongs per fortnight"),

	)
);
