<?php

///////Sulphite - concentration

/*
 * The cpncentration of sulphur dioxide in the wine /cider/ solution
 */


namespace MetaTunes\MeasurementClasses {
	use function ProcessWire\__;

	return array(

		'notes' => __("This is the sulphur dioxide concentration, not the amount added. It is used to measure the cumulative effect of additions to the must."),

		"dimension" => new Dimension([Dimension::SUBSTANCE_AMOUNT => 1, Dimension::LENGTH => -3]),
		'base' => 'mol per cu m',
		'units' => array(

			"mol per cu m"  => array(
				"alias" => __("moles per cubic metre"),
				"shortLabel" => __("mol/m\u{00B3}"),
				"conversion" => 1,
				"plural" => __("mol/m\u{00B3}"),
				"decimals" => 1,
			),

			"ppm"  => array(
				"alias" => __("part per million"),
				"shortLabel" => __("ppm"),
				"conversion" => 1 / 64.1,
				"plural" => __("parts per million"),
				"decimals" => 0,
			),

			)
	);

}

