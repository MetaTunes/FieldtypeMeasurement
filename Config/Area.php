<?php namespace MetaTunes\MeasurementClasses;
use function ProcessWire\__;
return array(
///////Units Of Area///////
	"dimension" => new Dimension([Dimension::LENGTH => 2]),
	'base' => 'square metre',
	'units' => array(
		"square metre" => array("shortLabel" => "m^2", "conversion" => 1), //square metre - base unit for area
		"square meter" => array("shortLabel" => "m^2", "conversion" => 1),
		"square kilometre" => array("shortLabel" => "km^2", "conversion" => 1000000),
		"square kilometer" => array("shortLabel" => "km^2", "conversion" => 1000000),
		"square centimetre" => array("shortLabel" => "cm^2", "conversion" => 0.0001),
		"square centimeter" => array("shortLabel" => "cm^2", "conversion" => 0.0001),
		"square millimetre" => array("shortLabel" => "mm^2", "conversion" => 0.000001),
		"square millimeter" => array("shortLabel" => "mm^2", "conversion" => 0.000001),
		"square foot" => array("shortLabel" => "ft^2", "conversion" => 0.092903, 'plural' => 'square feet'),
		"square mile" => array("shortLabel" => "mi^2", "conversion" => 2589988.11),
		"acre" => array("shortLabel" => "ac", "conversion" => 4046.86),
		"hectare" => array("shortLabel" => "ha", "conversion" => 10000),
	)
);
