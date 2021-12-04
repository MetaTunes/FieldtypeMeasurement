<?php

///////Acidity - Acid Reference Conversions relevant to fermentation///////
/*
 * Below are details of the alcohol content unit conversions.
 * All alcohol content values are converted to % alcohol by volume when they are entered, and are subjected to an upper limit of 100% and a lower limit of 0%.
 *
 * Thanks to FermCalc for these calculations: http://fermcalc.com/conversions/

 */


namespace MetaTunes\MeasurementClasses {
	use function ProcessWire\__;
	return array(

		'notes' => __("The base unit for alcohol content is % alcohol by volume, subjected to an upper limit of 100% and a lower limit of 0%. All measurements are at 20°C"),

		'base' => 'ABV',
		'units' => array(
			"ABV"  => array(
				"alias" => __("% alcohol by volume"),
				"shortLabel" => __("%ABV"),
				"conversion" => 1,
				"plural" => __("% alcohol by volume"),
			),
			"ABW" => array(
				"alias" => __("% alcohol by weight"),
				"shortLabel" => __("%ABW"),
				"conversion" => function($val, $toFrom) {
					if($toFrom) {
						$mixtureDensity = ($val < 7) ? (($val - 5) / (989.3 -991.9)) + 991.9 : (($val - 7) / (985.7 - 989.3)) + 989.3;
						return $val * (789.2 / $mixtureDensity);
					} else {
						$firstGuess = $val * (990 / 789.2);
						$mixtureDensity = ($firstGuess < 7) ? (($firstGuess - 5) / (989.3 -991.9)) + 991.9 : (($firstGuess - 7) / (985.7 - 989.3)) + 989.3;
						return $val * ($mixtureDensity / 789.2);
					}
				},
				"plural" => __("% alcohol by weight"),
				"notes" => "This conversion is approximate and assumes that the ABV is around 5% - 10%"
			),
			"proof [US]" => array("alias" => __("proof [US]"), "shortLabel" => __("proof [US]"), "conversion" => 0.5, "plural" => __("proof [US]")),
			"proof [GB]" => array("alias" => __("proof [GB]"), "shortLabel" => __("proof [GB]"), "conversion" =>  0.5715, "plural" => __("proof [GB]")),

			)
	);

} // end of Processwire namespace

