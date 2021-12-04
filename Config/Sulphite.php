<?php

///////Sulphite - conversion between different forms of sulphite, for use in wine and cider-making///////
/*
 * The calculator determines the amount of potassium or sodium metabisulphite to add to 1 litre in order to achieve the desired concentration of sulphur dioxide (SO2)
 *
 * Thanks to FermCalc for these calculations: http://fermcalc.com/conversions/

 */


namespace MetaTunes\MeasurementClasses {
	use function ProcessWire\__;

	return array(

		'notes' => __("The calculations assume that all sulphite additions result in (an increase in) free sulphur dioxide. No account is taken of binding. 
		Therefore the calculations are useful for ensuring compliance with regulatory limits, but not for targetting actual free SO2 levels."),

		'base' => 'ppm SO2 [L]',
		'units' => array(
			"ppm SO2 [L]"  => array(
				"alias" => __("part per million of sulphur dioxide in 1 litre"),
				"shortLabel" => __("ppm SO2 [L]"),
				"conversion" => 1,
				"plural" => __("parts per million of sulphur dioxide in 1 litre"),

			),

			"ppm SO2 [UKGal]"  => array(
				"alias" => __("part per million of sulphur dioxide in 1 UK gallon"),
				"shortLabel" => __("ppm SO2 [UKG]"),
				"conversion" => 4.54609,
				"plural" => __("parts per million of sulphur dioxide in 1 UK gallon"),

			),

			"ml solution [5% KMS]" => array(
				"alias" => __("millilitre of 5% potassium metabisulphite solution"),
				"shortLabel" => __("ml [5% KMS]"),
				"conversion" => 1 / 0.02,
				"plural" => __("millilitre of 5% potassium metabisulphite solution"),
				"notes" => __("For 5% stock solution, dissolve 8-10gm metabisulphite in 100ml (80-100gm in 1L). 
				Amount to add depends on freshness of metabisulphite.
				Solution is assumed to be fresh (it will lose potency over time)")
			),

			"ml solution [5% NaMS]" => array(
				"alias" => __("millilitre of 5% sodium metabisulphite solution"),
				"shortLabel" => __("ml [5% NaMS]"),
				"conversion" => 1 / 0.02,
				"plural" => __("millilitre of 5% sodium metabisulphite solution"),
				"notes" => __("For 5% stock solution, dissolve 7-9gm metabisulphite in 100ml (70-90gm in 1L). 
				Amount to add depends on freshness of metabisulphite.
				Solution is assumed to be fresh (it will lose potency over time)")
			),

			"g KMS powder" => array(
				"alias" => __("gram of potassium metabisulphite powder"),
				"shortLabel" => __("g [KMS]"),
				"conversion" => 1 / 0.002,
				"plural" => __("gram of potassium metabisulphite powder"),
				"notes" => __("Assumes powder is fresh (it will lose potency over time)")
			),

			"g NaMS powder" => array(
				"alias" => __("gram of sodium metabisulphite powder"),
				"shortLabel" => __("g [NaMS]"),
				"conversion" => 1 / (0.002 * 190 / 222),
				"plural" => __("gram of sodium metabisulphite powder"),
				"notes" => __("Assumes powder is fresh (it will lose potency over time)")
			),

			"campden" => array(
				"alias" => __("Campden tablet"),
				"shortLabel" => __("Campden tab"),
				"label" => "shortPadded",
				"conversion" => 1 / 0.0044,
				"notes" => __("Tablets have binding matter as well as sulphite.)")
			),

			)
	);

} // end of Processwire namespace

