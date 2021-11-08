<?php namespace ProcessWire;

return array(
///////Units Of Pressure///////
	"dimension" => new MeasurementDimension(MeasurementDimension::MASS, MeasurementDimension::LENGTH * MeasurementDimension::TIME ** 2),
	'base' => 'pascal',
	'units' => array(
		"pascal" => array("shortLabel" => "Pa", "conversion" => 1),
		"hectopascal" => array("shortLabel" => "hPa", "conversion" => 100),
		"kilopascal" => array("shortLabel" => "kPa", "conversion" => 1000),
		"megapascal" => array("shortLabel" => "mPa", "conversion" =>  1000000),
		"bar" => array("shortLabel" => "bar", "conversion" => 100000),
		"millibar" => array("shortLabel" => "mbar", "conversion" => 100),
		"pound per square inch" => array("shortLabel" => "psi", "conversion" => 6894.75728, "plural" => "pounds per square inch"),
		"atmosphere [standard]" => array("shortLabel" => "atm", "conversion" =>  101325.01, "plural" => "atmospheres"),
		"inch of mercury [0C]" => array("shortLabel" => "inHg", "conversion" =>  3386.3886667, "plural" => "inches of mercury"),

	)
);

