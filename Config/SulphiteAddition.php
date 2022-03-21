<?php

///////Sulphite Addition - conversion between different forms of sulphite, for use in wine and cider-making///////
/*
 * The calculator determines the amount of potassium or sodium metabisulphite to add to 1 litre in order to achieve the desired concentration of sulphur dioxide (SO2)
 *
 * Thanks to FermCalc for these calculations: http://fermcalc.com/conversions/

 */


namespace MetaTunes\MeasurementClasses {
	use function ProcessWire\__;

	return array(

		'notes' => __("The calculations assume that all sulphite additions result in (an increase in) free sulphur dioxide. No account is taken of binding. 
		Therefore the calculations are useful for ensuring compliance with regulatory limits, but not for targetting actual free SO2 levels. Molar mass of SO2 is 64.0638 g/mol"),

		"dimension" => new Dimension([Dimension::SUBSTANCE_AMOUNT => 1]),
		'base' => 'mol',
		'units' => array(
			"mol"  => array(
				"alias" => __("mole"),
				"shortLabel" => __("mol"),
				"conversion" => 1,
				"plural" => __("mol"),

			),

			"ppm SO2 [in 1 cu m]"  => array(                   // can't have unicode m\u{00B3} in key
				"alias" => __("part per million of sulphur dioxide when added to 1 cubic metre"),
				"shortLabel" => __("ppm SO2 [in 1m\u{00B3}]"),
				"conversion" => 1 / 64.0638,
				"plural" => __("parts per million of sulphur dioxide when added to 1 cubic metre"),

			),

			"ppm SO2 [L]"  => array(
				"alias" => __("part per million of sulphur dioxide when added to 1 litre"),
				"shortLabel" => __("ppm SO2 [in 1L]"),
				"conversion" => 1/(1000 * 64.0638),
				"plural" => __("parts per million of sulphur dioxide when added to 1 litre"),

			),

			"ppm SO2 [UKGal]"  => array(
				"alias" => __("part per million of sulphur dioxide when added to 1 UK gallon"),
				"shortLabel" => __("ppm SO2 [in 1UKG]"),
				"conversion" => 4.54609 / (1000 * 64.0638),
				"plural" => __("parts per million of sulphur dioxide when added to 1 UK gallon"),

			),

			"ml solution [5% KMS]" => array(
				"alias" => __("millilitre of 5% potassium metabisulphite solution"),
				"shortLabel" => __("ml [5% KMS]"),
				"conversion" => 1 /(.02 * 1000 * 64.0638),
				"plural" => __("millilitre of 5% potassium metabisulphite solution"),
				"notes" => __("For 5% stock solution, dissolve 8-10gm metabisulphite in 100ml (80-100gm in 1L). 
				Amount to add depends on freshness of metabisulphite.
				Solution is assumed to be fresh (it will lose potency over time)")
			),

			"ml solution [5% NaMS]" => array(
				"alias" => __("millilitre of 5% sodium metabisulphite solution"),
				"shortLabel" => __("ml [5% NaMS]"),
				"conversion" => 1 / (.02 * 1000 * 64.0638),
				"plural" => __("millilitre of 5% sodium metabisulphite solution"),
				"notes" => __("For 5% stock solution, dissolve 7-9gm metabisulphite in 100ml (70-90gm in 1L). 
				Amount to add depends on freshness of metabisulphite.
				Solution is assumed to be fresh (it will lose potency over time)")
			),

			"g KMS powder" => array(
				"alias" => __("gram of potassium metabisulphite powder"),
				"shortLabel" => __("g [KMS]"),
				"conversion" => 1 / (.002 * 1000 * 64.0638),
				"plural" => __("gram of potassium metabisulphite powder"),
				"notes" => __("Assumes powder is fresh (it will lose potency over time)")
			),

			"g NaMS powder" => array(
				"alias" => __("gram of sodium metabisulphite powder"),
				"shortLabel" => __("g [NaMS]"),
				"conversion" => 1 / ((.002 * 190 / 222) * 1000 * 64.0638),
				"plural" => __("gram of sodium metabisulphite powder"),
				"notes" => __("Assumes powder is fresh (it will lose potency over time)")
			),

			"campden" => array(
				"alias" => __("Campden tablet"),
				"shortLabel" => __("Campden tab"),
				"label" => "shortPadded",
				"conversion" => 1/(.0044 * 1000 * 64.0638),
				"notes" => __("Tablets have binding matter as well as sulphite.)")
			),

			)
	);

}

