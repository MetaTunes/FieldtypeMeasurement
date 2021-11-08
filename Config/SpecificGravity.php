<?php

///////Specific Gravity - Units Of Density relevant to fermentation///////
/*
 * The specific gravity (SG) conversions are intended to convert between different hydrometer reading scales.
 * The conversions to Brix, Oechsle etc,  g/L sugar, and potential alcohol are only valid prior to fermentation.
 * After fermentation begins these readings will be obscured by alcohol, and therefore reflect the apparent hydrometer readings for these quantities.
 * All of these conversions assume a reference temperature of 20°C for SG.
 *
 * Thanks to FermCalc for these calculations: http://fermcalc.com/conversions/
 * but note that they omit to show the 5th coefficient in the Brix->SG calculation
 * (Correct formulae are at https://frinklang.org/fsp/colorize.fsp?f=Brix.frink)
 */

/*
 * First we define some reusable functions
 * These functions are conditional to avoid being declared more than once
 */
namespace MeasurementSpecificGravity {
// Calculate SG from Brix using method attributed to  J. Hackbarth (2011), which is based on the AOAC Brix tables (Horwitz and Latimer, 2005)
	if(!function_exists(__NAMESPACE__ . '\brixToSg')) {
		function brixToSg($brix) {
			$coefficients = [+0.3875135555, +0.09702881653, +0.3883357480,
				-1.782845295, +5.591472292, -11.00667976, +13.62230734,
				-10.33082001, +4.387787019, -0.7995558730];
			$polynomial = 0;
			foreach($coefficients as $index => $coefficient) {
				$calc = $coefficient * (($brix / 100) ** ($index + 1));
				$polynomial += $calc;
			}
			return 1 + $polynomial;
		}
	}

	if(!function_exists(__NAMESPACE__ . '\sgToBrix')) {
		function sgToBrix($sg) {
			// Estimation function taken from
			// http://en.wikipedia.org/wiki/Brix#Tables
			$brix1 = 261.3 * (1 - 1 / $sg);
			$brix2 = 261.3 * (1 - 1 / ($sg + 0.00001));
			$sg1 = brixToSg($brix1);
			$sg2 = brixToSg($brix2);
			$blowOut = 0;
			while(true) {
				if(($sg2 - $sg1) == 0) return $brix1;
				$invSlope = ($brix2 - $brix1) / ($sg2 - $sg1);
				$bnew = $brix1 + ($sg - $sg1) * $invSlope;
				$sgnew = brixToSg($bnew);

				if(abs($sgnew - $sg) < (float)'1e-8') {
					return $bnew;
				}

				$sg2 = $sg1;
				$sg1 = $sgnew;
				$brix2 = $brix1;
				$brix1 = $bnew;
				$blowOut++;
				if($blowOut > 200) return $bnew;
			}
		}
	}

	if(!function_exists(__NAMESPACE__ . '\sgToSugar')) {
		function sgToSugar($sg, $sfdeCorrection = 0) {
			return (10 * $sg * sgToBrix($sg) * 0.9982) * (1 - $sfdeCorrection);
		}
	}

	if(!function_exists(__NAMESPACE__ . '\sugarToSg')) {
		function sugarToSg($sugar, $sfdeCorrection = 0) {
			$sg1 = $sugar / (2.613 * 0.9982 * 1000) + 1;  //
			$sg2 = ($sugar + 0.00001) / (2.613 * 0.9982 * 1000) + 1;
			$sugar1 = sgToSugar($sg1, $sfdeCorrection);
			$sugar2 = sgToSugar($sg2, $sfdeCorrection);
			$blowOut = 0;
			$sgArray = [];
			while(true) {
				if(($sugar2 - $sugar1) == 0) {
					return $sg1;
				}
				$invSlope = ($sg2 - $sg1) / ($sugar2 - $sugar1);
				$sgnew = $sg1 + ($sugar - $sugar1) * $invSlope;
				$sugarnew = sgToSugar($sgnew, $sfdeCorrection);

				if(abs($sugarnew - $sugar) < (float)'1e-8') {
					return $sgnew;
				}

				$sugar2 = $sugar1;
				$sugar1 = $sugarnew;
				$sg2 = $sg1;
				$sg1 = $sgnew;
				$blowOut++;
				$sgArray[] = $sgnew;
				if($blowOut > 200) {
					return $sgnew;
				}
			}
		}
	}

	if(!function_exists(__NAMESPACE__ . '\sgToAbv')) {
		function sgToAbv($sg, $sfdeCorrection = 0) {
			$adj = ($sg - 1) * $sfdeCorrection; // $adj is the correction for non-sugar solutes (0.009 equiv to 82% $sfdeCorrection at SG 1.050) and is a source of inaccuracy
			return (1000 * ($sg - 1)) / (7.75 - 3.75 * ($sg - 1 - $adj));
		}
	}

	if(!function_exists(__NAMESPACE__ . '\abvToSg')) {
		function abvToSg($abv, $sfdeCorrection = 0) {
			$sg1 = ($abv/100) / 0.06 + 0.007;
			$sg2 = ($abv/100 + 0.00001)/0.06 + 0.007;
			$abv1 = sgToAbv($sg1, $sfdeCorrection);
			$abv2 = sgToAbv($sg2, $sfdeCorrection);
			$blowOut = 0;
			$sgArray = [];
			while(true) {
				if(($abv2 - $abv1) == 0) {
					return $sg1;
				}
				$invSlope = ($sg2 - $sg1) / ($abv2 - $abv1);
				$sgnew = $sg1 + ($abv - $abv1) * $invSlope;
				$abvnew = sgToAbv($sgnew, $sfdeCorrection);

				if(abs($abvnew - $abv) < (float)'1e-8') {
					return $sgnew;
				}

				$abv2 = $abv1;
				$abv1 = $abvnew;
				$sg2 = $sg1;
				$sg1 = $sgnew;
				$blowOut++;
				$sgArray[] = $sgnew;
				if($blowOut > 200) {
					return $sgnew;
				}
			}
		}
	}

} // end of end of Processwire\MeasurementConfig namespace

namespace ProcessWire {

	return array(

		'notes' => __("The specific gravity (SG) conversions are intended to convert between different hydrometer reading scales. 
	The conversions to Brix, Oechsle etc,  g/L sugar, and potential alcohol are only valid prior to fermentation.
	After fermentation begins these readings will be obscured by alcohol, and therefore reflect the apparent hydrometer readings for these quantities.
	All of these conversions assume a reference temperature of 20°C for SG.
	\n No conversion is given for estimated alcohol post fermentation. Just take the difference in SG and multiply by approx 128."),

		'base' => 'sg',
		'units' => array(
			"SG" => array("alias" => "specific gravity", "shortLabel" => "SG", "conversion" => 1, "plural" => "specific gravity", "position" => "prepend", "label" => "shortPadded"),
			"gravity point" => array("shortLabel" => "°SG", "conversion" => function($val, $toFrom) {
				return ($toFrom) ? ($val - 1) * 1000 : ($val / 1000) + 1;
			}),
			"Brix" => array(
				"shortLabel" => "°Bx",
				"conversion" => function($val, $toFrom) {
					return ($toFrom) ? \MeasurementSpecificGravity\sgToBrix($val) : \MeasurementSpecificGravity\brixToSg($val);
				},
				"plural" => "Brix"),
			"Plato" => array(
				"shortLabel" => "°P",
				"conversion" => function($val, $toFrom) {
					return ($toFrom) ? \MeasurementSpecificGravity\sgToBrix($val) : \MeasurementSpecificGravity\brixToSg($val);
				},
				"plural" => "Plato"),
			"Balling" => array(
				"shortLabel" => "°Balling",
				"conversion" => function($val, $toFrom) {
					return ($toFrom) ? \MeasurementSpecificGravity\sgToBrix($val) : \MeasurementSpecificGravity\brixToSg($val);
				},
				"plural" => "Balling"),
			/* Oechsle [old] is same as °SG. The new Oechsle scale is based on refractive index, and is the current official Oechsle scale in Germany.
			 * The old Oechsle scale is still used in Luxembourg and Switzerland.
			 */
			"Oechsle [old]" => array("shortLabel" => "°Oe", "conversion" => function($val, $toFrom) {
				return ($toFrom) ? ($val - 1) * 1000 : ($val / 1000) + 1;
			}, "plural" => "Oechsle"),
			/*
			 * There are two Baumé scales: one for liquids heavier than water, and one for liquids lighter than water.
			 * For liquids that are heavier than water, 0°Bé corresponds to the reading for pure water, and 15°Bé corresponds to the reading of a solution of 15% NaCl by mass.
			 * For liquids that are lighter than water, 10°Bé marks the level for pure water and 0°Bé corresponds to a solution that is 10% NaCl by mass.
			 * Note that the heavy and light scales go in opposite directions.
			 * Only the scale for liquids heavier than water is included here because this is the only one used in winemaking.
			 */
			"Baumé [heavy]" => array("shortLabel" => "°Bé", "conversion" => function($val, $toFrom) {
				return ($toFrom) ? 145 - 145 / $val : (145 - $val) / 145;
			}, "plural" => "Baumé"),
			"gram per litre" => array("shortLabel" => "g/L", "conversion" => 1 / (1000 * 0.9982), "plural" => "grams per litre", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
			"kilogram per litre" => array("shortLabel" => "kg/L", "conversion" => 1 / 0.9982, "plural" => "kilograms per litre", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
		"kilogram per cubic metre" => array("shortLabel" => "kg/m^3", "conversion" => 1 / (1000000 * 0.9982), "plural" => "grams per cubic metre", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
			"pound per US gallon" => array("shortLabel" => "lb/USGal", "conversion" => 1 / 8.3303827592, "plural" => "pounds per US gallon", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
			"pound per Imp gallon" => array("shortLabel" => "lb/ImpGal", "conversion" => 1 / 10.004372512, "plural" => "pounds per Imp gallon", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
			"pound per cubic foot" => array("shortLabel" => "lb/ft^3", "conversion" => 1 / 62.315590511, "plural" => "pounds per cubic foot", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
			/*
			 * Sugar content in grams/liter (g/L) is not really an SG unit, but it’s included here because it’s a useful quantity for a number of calculations.
			 * It’s important to note here that this conversion is only strictly valid for pure aqueous sucrose solutions, so it cannot be used for any liquids that contain alcohol.
			 * Also, it should not be confused with the density units of grams/litre.
			 */
			"sugar solution g/L" => array(
				"alias" => "sugar gram per litre [sugar solution]",
				"shortLabel" => "g/L sugar",
				"conversion" => function($val, $toFrom) {
					if($toFrom) {
						return \MeasurementSpecificGravity\sgToSugar($val, 0);
					} else {
						return \MeasurementSpecificGravity\sugarToSg($val, 0);
					}
				},
				"plural" => "sugar grams per litre [sugar solution]",
				"notes" => __("This is for a pure sugar solution - i.e. no sugar-free dry extract.")),
			"sugar gram per litre [apple juice]" => array(
				"shortLabel" => "g/L sugar",
				"conversion" => function($val, $toFrom) {
					if($toFrom) {
						return \MeasurementSpecificGravity\sgToSugar($val, 0.18);
					} else {
						return \MeasurementSpecificGravity\sugarToSg($val, 0.18);
					}
				},
				"plural" => "grams sugar per litre",
				"notes" => __("This is for a typical apple juice - i.e. sugar-free dry extract of 18% of the sugar level.")),
			"%ABV potential [sugar solution]" => array(
				"alias" => "potential alcohol % by volume [sugar solution]",
				"shortLabel" => "%ABV potential",
				"conversion" => function($val, $toFrom) {
					if($toFrom) {
						return \MeasurementSpecificGravity\sgToAbv($val, 0);
					} else {
						return \MeasurementSpecificGravity\abvToSg($val, 0);
					}
				},
				"plural" => "potential alcohol % by volume [sugar solution]",
				"label" => "shortPadded",
				"notes" => __("This is the calculation for a pure sugar solution - i.e. no sugar-free dry extract.")),
			"%ABV potential [cider]" => array(
				"alias" => __("potential alcohol % by volume [cider]"),
				"shortLabel" => "%ABV potential",
				"conversion" => function($val, $toFrom) {
					if($toFrom) {
						return \MeasurementSpecificGravity\sgToAbv($val, 0.18);
					} else {
						return \MeasurementSpecificGravity\abvToSg($val, 0.18);
					}
				},
				"plural" => __("potential alcohol % by volume [cider]"),
				"label" => "shortPadded",
				"notes" => __("This is the calculation for cider assuming all sugar is fermented. It is adjusted for sugar-free dry extract.")),
		)
	);

} // end of Processwire namespace

