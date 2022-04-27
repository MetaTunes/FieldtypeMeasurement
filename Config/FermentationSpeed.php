<?php

///////pH - single unit quantity as not readily convertible///////
/*

 */


namespace MetaTunes\MeasurementClasses {

	use function ProcessWire\__;

	return array(
		"dimension" => new Dimension([Dimension::SUBSTANCE_AMOUNT => 1, Dimension::TIME => -1]),
		'notes' => __("The speed of fermentation is useful in determining the stage of the fermentation, appropriate racking times and the possibility of a stuck fermentation. 
		The principal measurement used here is the FSU (Fermentation Speed Unit) devised by Claude Jolicoeur, being defined as follows:
		1 FSU is the speed of fermentation that corresponds to a drop in SG of 0.001 in 100 days. However, the base unit is pts/sec to be consistent with other dimensions, even if practically useless."),

		'base' => 'pts/sec',
		'units' => array(
			"pts/sec" => array(
				"shortLabel" => __("pts/sec"),
				"conversion" => 1,
			),
			"FSU" => array(
				"shortLabel" => __("pts/100days"),
				"decimals" => 2,
				"conversion" => 1 / (100 * 24 * 60 * 60),
			),
		)
	);

}

