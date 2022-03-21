<?php


/// ///////percentage - scalar quantity///////
/*

 */


namespace MetaTunes\MeasurementClasses {

	use function ProcessWire\__;

	return array(

		'notes' => __("To allow conversion between proportions and percentages"),

		'base' => 'proportion',
		"dimension" => new Dimension([]), // scalar
		'units' => array(
			"proportion" => array(
				"alias" => __("proportion"),
				"shortLabel" => __(""),
				"position" => "prepend",
				"decimals" => 4,
				"conversion" => 1,
			),
			"percent" => array(
				"alias" => __("percent"),
				"shortLabel" => __("%"),
				"position" => "append",
				"decimals" => 2,
				"conversion" => 1/100,
			),
		)
	);

}

