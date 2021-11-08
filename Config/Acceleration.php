<?php namespace ProcessWire;

return array(
///////Units Of Acceleration///////
 	"dimension" => new MeasurementDimension(MeasurementDimension::LENGTH, MeasurementDimension::TIME ** 2),
	'base' => 'metre per second squared',
	'units' => array(
		"metre per second squared" => array("shortLabel" => "m/s\u{00B2}", "conversion" => 1, "plural" => __("metres per second squared")),
		"G-unit" => array("shortLabel" => "G", "conversion" => 9.80665),
		"mile per hour per second" => array("shortLabel" => "mi/h.s", "conversion" => 0.44704, "plural" => __("miles per hour per second")),
	)
);