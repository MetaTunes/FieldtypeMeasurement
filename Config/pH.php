<?php

///////pH - single unit quantity as not readily convertible///////
/*

 */


namespace MetaTunes\MeasurementClasses {

	use function ProcessWire\__;

	return array(

		'notes' => __("The pH scale (pH) is a numeric scale which is used to define how acidic or basic an aqueous solution is. 
		It commonly ranges between 0 and 14, but can go beyond these values if sufficiently acidic/basic. 
		pH is logarithmically and inversely related to the concentration of hydrogen ions in a solution. 
		The pH to H+ formula that represents this relation is: pH = -log([H+])
		The solution is acidic if its pH is less than 7. If the pH is higher than that number, the solution is basic, as known as alkaline. Solutions with a pH equal to 7.
		pH is not generally convertible to other measures of acidity other than pure acids, bases and known buffers. 
		In fermentation usage there are too many unknowns, hence it is no included in the 'Acidity' quantity."),

		'base' => 'pH',
		'units' => array(
			"pH" => array(
				"shortLabel" => __("pH"),
				"position" => "prepend",
				"decimals" => 2,
				"conversion" => 1,
				"plural" => __("pH"),
			),
		)
	);

}

