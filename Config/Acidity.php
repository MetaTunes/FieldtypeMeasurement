<?php

///////Acidity - Acid Reference Conversions relevant to fermentation///////
/*
 * When we titrate a must or wine for acidity, all we really determine is the number of available hydrogen (H+) ions in the wine and not the types of acid present.
 * For this reason we must choose an acid as a reference in order to express the acidity as a concentration.
 * Different winemaking texts use different acid references when referring to titratable acidity levels.
 * Most use tartaric acid (the main acid in grapes) as the reference, with units of either percent or grams/litre (parts per thousand, or ppt).
 * However, other texts use different acids as the reference, with sulfuric acid being a popular alternative to tartaric acid.
 * For cider-making, malic acid (the main acid in apples) is often used.
 *
 * milli-equivalent is used as the base in the these calculations as it does not depend on the acid type.
 * The conversion is the molecular weight divided by (number of h+ ions + 1000)
 *
 * Thanks to FermCalc for these calculations: http://fermcalc.com/conversions/

 */


namespace MetaTunes\MeasurementClasses {
	use function ProcessWire\__;
	return array(

		'notes' => __("Most winemaking texts use tartaric acid (the main acid in grapes) as the reference, with units of either percent or grams/litre (parts per thousand, or ppt).
However, other texts use different acids as the reference, with sulphuric acid being a popular alternative to tartaric acid.
For cider-making, malic acid (the main acid in apples) is often used.
\n milli-equivalent is used as the base in the these calculations as it does not depend on the acid type"),

		'base' => 'milli-equivalent',
		'units' => array(
			"milli-equivalent"  => array(
				"alias" => __("milli-equivalent"),
				"shortLabel" => __("meq/L"),
				"conversion" => 1,
				"plural" => __("milli-equivalent"),
				"notes" => __("A milli-equivalent (mEq, meq or mequiv) is the amount of a substance that will react with or supply one-thousandth of a mole of hydrogen ions (H+) in an acid-base reaction.")
			),

			"g/L sulphuric" => array("alias" => __("gram per litre [sulphuric]"), "shortLabel" => __("g/L [s]"), "conversion" => 2000/98.08, "plural" => __("grams per litre [sulphuric]")),
			"g/L tartaric" => array("alias" => __("gram per litre [tartaric]"), "shortLabel" => __("g/L [t]"), "conversion" => 2000/150.09, "plural" => __("grams per litre [tartaric]")),
			"g/L malic" => array("alias" => __("gram per litre [malic]"), "shortLabel" => __("g/L [m]"), "conversion" => 2000/134.09, "plural" => __("grams per litre [malic]")),
			// ppt is the same as g/L - just a different way of describing it
			"ppt sulphuric" => array("alias" => __("part per thousand [sulphuric]"), "shortLabel" => __("ppt [s]"), "conversion" => 2000/98.08, "plural" => __("parts per thousand [sulphuric]")),
			"ppt tartaric" => array("alias" => __("part per thousand [tartaric]"), "shortLabel" => __("ppt [t]"), "conversion" => 2000/150.09, "plural" => __("parts per thousand [tartaric]")),
			"ppt malic" => array("alias" => __("part per thousand [malic]"), "shortLabel" => __("ppt [m]"), "conversion" => 2000/134.09, "plural" => __("parts per thousand [malic]")),
			)
	);

}

