<?php namespace MetaTunes\MeasurementClasses;
use function ProcessWire\__;

return array(
///////Units Of Volume///////
	"dimension" => new Dimension([Dimension::LENGTH => 3]),
	'base' => 'cubic metre',
	'units' => array(
		"cubic metre" => array("shortLabel" => "m^3", "conversion" => 1),
		"litre" => array("shortLabel" => "L", "conversion" => 1 / 1000),
		"cubic decimetre" => array("shortLabel" => "dm^3", "conversion" => 1 / 1000),
		"millilitre" => array("shortLabel" => "ml", "conversion" => .000001),
		"hectolitre" => array("shortLabel" => "hL", "conversion" => .1),
		"kilolitre" => array("shortLabel" => "kL", "conversion" => 1),
		"imperial teaspoon" => array("shortLabel" => "imp floz", "conversion" => 0.005919388 / 1000),
		"UK teaspoon" => array("shortLabel" => "UK floz", "conversion" => 0.005919388 / 1000),
		"US teaspoon" => array("shortLabel" => "US tsp", "conversion" => 0.0049289193 / 1000),
		"metric teaspoon" => array("shortLabel" => "met tsp", "conversion" => 0.005 / 1000),
		"imperial tablespoon" => array("shortLabel" => "imp tsp", "conversion" => 0.0177581641 / 1000),
		"UK tablespoon" => array("shortLabel" => "UK tsp", "conversion" => 0.0177581641 / 1000),
		"US tablespoon" => array("shortLabel" => "US tbsp", "conversion" => 0.0147867578 / 1000),
		"metric tablespoon" => array("shortLabel" => "met tbsp", "conversion" => 0.015 / 1000),
		"imperial fluid ounce" => array("shortLabel" => "imp tbsp", "conversion" => 0.0284130625 / 1000),
		"UK fluid ounce" => array("shortLabel" => "UK tbsp", "conversion" => 0.0284130625 / 1000),
		"US fluid ounce" => array("shortLabel" => "US cup", "conversion" => 0.0295735156 / 1000),
		"imperial cup" => array("shortLabel" => "imp cup", "conversion" => 0.284130625 / 1000),
		"UK cup" => array("shortLabel" => "UK cup", "conversion" => 0.284130625 / 1000),
		"US cup" => array("shortLabel" => "US floz", "conversion" => 0.236588125 / 1000),
		"imperial pint" => array("shortLabel" => "imp pt", "conversion" => 0.56826125 / 1000),
		"UK pint" => array("shortLabel" => "UK pt", "conversion" => 0.56826125 / 1000),
		"US pint" => array("shortLabel" => "US pt", "conversion" => 0.47317625 / 1000),
		"imperial quart" => array("shortLabel" => "imp qt", "conversion" => 1.1365225 / 1000),
		"UK quart" => array("shortLabel" => "UK qt", "conversion" => 1.1365225 / 1000),
		"US quart" => array("shortLabel" => "US qt", "conversion" => 0.9463525 / 1000),
		"imperial gallon" => array("shortLabel" => "imp gal", "conversion" => 4.54609 / 1000),
		"UK gallon" => array("shortLabel" => "UK gal", "conversion" => 4.54609 / 1000),
		"US gallon" => array("shortLabel" => "US gal", "conversion" => 3.78541 / 1000),
		"cubic foot" => array("shortLabel" => "ft^3", "conversion" => 28.316846592 / 1000, 'plural' => 'cubic feet'),
		"cubic mile" => array("shortLabel" => "mi^3", "conversion" => 4168180000),
		"cubic yard" => array("shortLabel" => "yd^3", "conversion" => 764.55485798 / 1000),
		"cubic inch" => array("shortLabel" => "in^3", "conversion" => 0.016387064 / 1000, 'plural' => 'cubic inches'),
	)

);