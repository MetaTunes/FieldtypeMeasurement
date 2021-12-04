<?php namespace MetaTunes\MeasurementClasses;
use function ProcessWire\__;

return array(
///////Units Of Energy///////
	"notes" => __("The base unit for energy is the joule. It is a derived SI.
	It is equal to the energy transferred to (or work done on) an object when a force of one newton acts on that object in the direction of the force's motion through a distance of one metre (1 newton-metre or N⋅m). 
	It is also the energy dissipated as heat when an electric current of one ampere passes through a resistance of one ohm for one second. 
	It is named after the English physicist James Prescott Joule (1818–1889)."),
	"dimension" => new Dimension([Dimension::MASS => 1, Dimension::LENGTH => 2, Dimension::TIME => -2]),
	"base" => "joule",
	"units" => array(
		"joule" => array("shortLabel" => "J", "conversion" => 1),
		"kilojoule" => array("shortLabel" => "kJ", "conversion" => 1000),
		"megajoule" => array("shortLabel" => "mJ", "conversion" => 1000000),
		"calorie" => array("shortLabel" => "cal", "conversion" => 4.184, "notes" => __("THe 'small calorie' is The amount of heat needed to raise the temperature of one gram of water by one degree Celsius (or one kelvin).")),
		"kilocalorie" => array("alias" => "food Calorie", "shortLabel" => "Cal", "conversion" => 4184, "notes" => __("The 'large' or 'food calorie' is the amount of heat needed to raise the temperature of one kilogram of water by one degree Celsius (or one kelvin).")),
		"watt-second" => array("shortLabel" => "Ws", "conversion" => 1),
		"watt-hour" => array("shortLabel" => "Wh", "conversion" => 3600),
		"kilowatt-hour" => array("shortLabel" => "kWh", "conversion" => 3600000),
		"megawatt-hour" => array("shortLabel" => "MWh", "conversion" => 3600000000),
		"BTU" => array("alias" => "Bitish thermal unit", "shortLabel" => "BTU", "conversion" => 1055, "notes" => "There are various definitions in the range 1054-1060J. 1055 is use here."),
		"electronvolt" => array("shortLabel" => "eV", "conversion" => 1.602176634e-19),
		"foot pound" => array("shortLabel" => "ftlb", "conversion" => 1.35582, "notes" => __("The foot pound has the same dimension as a joule but is typically a measure of torque, not energy.")),
		"newton metre" => array("shortLabel" => "Nm", "conversion" => 1, "notes" => __("The newton metre has equivalent units to a joule but is typically a measure of torque, not energy.")),

	)
);

