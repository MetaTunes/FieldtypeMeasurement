<?php namespace MetaTunes\MeasurementClasses;
use function ProcessWire\__;

return array(
///////Units Of Power///////
	"notes" => __("The base unit for energy is the watt. It is a derived unit of energy in the International System of Units.
	It is equal one joule per second. 
	It is named after the English engineer James Watt (1736-1819).
	Power is related to other quantities, for example the power involved in moving a ground vehicle is the product of the traction force on the wheels and the velocity of the vehicle. 
	The output power of a motor is the product of the torque that the motor generates and the angular velocity of its output shaft. 
	Likewise, the power dissipated in an electrical element of a circuit is the product of the current flowing through the element and of the voltage across the element."),
	"dimension" => new Dimension([Dimension::MASS => 1, Dimension::LENGTH => 2, Dimension::TIME => -3]),
	"base" => "watt",
	"units" => array(
		"watt" => array("shortLabel" => "W", "conversion" => 1),
		"horsepower [international]" => array("shortLabel" => "hp", "conversion" => 745.69987, "notes" => __("There are various definitions of horsepower varying from about 735 to 746 watts. 745.69987 is used here.")),
		"kilowatt" => array("shortLabel" => "kW", "conversion" => 1000),
		"megawatt" => array("shortLabel" => "MW", "conversion" => 1000000),
		"gigawatt" => array("shortLabel" => "GW", "conversion" => 1000000000),
		"BTU per hour" => array("shortLabel" => "BTU/h", "conversion" => 0.293071, "notes" => __("THe 'small calorie' is The amount of heat needed to raise the temperature of one gram of water by one degree Celsius (or one kelvin).")),

	)
);

