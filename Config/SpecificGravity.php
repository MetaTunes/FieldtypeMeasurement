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
 *
 * SG->ABV conversion is from Duncan/Acton Progressive winemaking, with minor tweak ($adj) by Claude Jolicoeur
 */

/*
 * First we define some reusable functions
 * These functions are conditional to avoid being declared more than once
 */

namespace MeasurementSpecificGravity {
	// gravity points is the base unit but SG is used for the conversion functions, so the GP<->SG conversion is included here as well
	function gpToSg($gp) {
		return ($gp / 1000) + 1;
	}

	function sgToGp($sg) {
		return ($sg - 1) * 1000;
	}


// Calculate SG from Brix using method attributed to  J. Hackbarth (2011), which is based on the AOAC Brix tables (Horwitz and Latimer, 2005)
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

	function sgToBrix($sg) {
		// Estimation function taken from
		// http://en.wikipedia.org/wiki/Brix#Tables
		if($sg > 0) {
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
		} else {
			return 0;
		}
	}

	function sgToSugar($sg, $sfdeCorrection = 0) {
		return ($sg * sgToBrix($sg) * 0.9982) * (1 - $sfdeCorrection);
	}

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

	function sgToAbv($sg, $sfdeCorrection = 0) {
		$adj = ($sg - 1) * $sfdeCorrection; // $adj is the correction for non-sugar solutes (0.009 equiv to 82% $sfdeCorrection at SG 1.050) and is a source of inaccuracy
		return (1000 * ($sg - 1) / (7.75 - 0.24 * ($sg - $adj - 1))); // Duncan and Acton has 3.75, not 0.24
	}

	function abvToSg($abv, $sfdeCorrection = 0) {
		$sg1 = ($abv / 100) / 0.06 + 0.007;
		$sg2 = ($abv / 100 + 0.00001) / 0.06 + 0.007;
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

} // end of MeasurementSpecificGravity namespace

namespace MetaTunes\MeasurementClasses {

	use function ProcessWire\__;
	use function MeasurementSpecificGravity\{brixToSg,
		gpToSg,
		sgToBrix,
		sgToGp,
		sugarToSg,
		sgToSugar,
		abvToSg,
		sgToAbv
	};

	return array(

		'notes' => __("The specific gravity (SG) conversions are intended to convert between different hydrometer reading scales. 
	The conversions to Brix, Oechsle etc,  g/L sugar, and potential alcohol are only valid prior to fermentation.
	After fermentation begins these readings will be obscured by alcohol, and therefore reflect the apparent hydrometer readings for these quantities.
	All of these conversions assume a reference temperature of 20°C for SG.
	\n No conversion is given for estimated alcohol post fermentation. Just take the difference in SG and multiply by approx 128.
	\n Note that the base unit is gravity points which can be added and subtracted and effectively have a dimension of 'substance amount'. Not all the units in this conversion have this dimension. 
	The specific gravity index (e.g. 1.050) is effectively dimensionless and 'kilogram per litre' has a density dimension (mass / length cubed)."),
		"dimension" => new Dimension([Dimension::SUBSTANCE_AMOUNT => 1]),
		'base' => 'gravity point',
		'units' => array(
			"gravity point" => array("shortLabel" => "°SG", "conversion" => 1),
			"SG" => array("alias" => "specific gravity", "shortLabel" => "SG",
				"conversion" => function($val, $toFrom) {
					return ($toFrom) ? gpToSg($val) : sgToGp($val);
				},
				"plural" => "specific gravity", "position" => "prepend", "label" => "shortPadded"),
			"Brix" => array(
				"shortLabel" => "°Bx",
				"conversion" => function($val, $toFrom) {
					return ($toFrom) ? sgToBrix(gpToSg($val)) : sgToGp(brixToSg($val));
				},
				"plural" => "Brix"),
			"Plato" => array(
				"shortLabel" => "°P",
				"conversion" => function($val, $toFrom) {
					return ($toFrom) ? sgToBrix(gpToSg($val)) : sgToGp(brixToSg($val));
				},
				"plural" => "Plato"),
			"Balling" => array(
				"shortLabel" => "°Balling",
				"conversion" => function($val, $toFrom) {
					return ($toFrom) ? sgToBrix(gpToSg($val)) : sgToGp(brixToSg($val));
				},
				"plural" => "Balling"),
			/* Oechsle [old] is same as gravity points (°SG). The new Oechsle scale is based on refractive index, and is the current official Oechsle scale in Germany.
			 * The old Oechsle scale is still used in Luxembourg and Switzerland.
			 */
			"Oechsle [old]" => array("shortLabel" => "°Oe", "conversion" => 1, "plural" => "Oechsle"),
			/*
			 * There are two Baumé scales: one for liquids heavier than water, and one for liquids lighter than water.
			 * For liquids that are heavier than water, 0°Bé corresponds to the reading for pure water, and 15°Bé corresponds to the reading of a solution of 15% NaCl by mass.
			 * For liquids that are lighter than water, 10°Bé marks the level for pure water and 0°Bé corresponds to a solution that is 10% NaCl by mass.
			 * Note that the heavy and light scales go in opposite directions.
			 * Only the scale for liquids heavier than water is included here because this is the only one used in winemaking.
			 */
			"Baumé [heavy]" => array("shortLabel" => "°Bé", "conversion" => function($val, $toFrom) {
				return ($toFrom) ? 145 - 145 / gpToSg($val) : sgToGp(145 / (145 - $val));
				},
				"plural" => "Baumé"),
			"gram per litre" => array("shortLabel" => "g/L", "conversion" => function($val, $toFrom) {
				return ($toFrom) ? gpToSg($val) / 0.0009982 : sgToGp(0.0009982 * $val);
				},
				"plural" => "grams per litre", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
			"kilogram per litre" => array("shortLabel" => "kg/L", "conversion" => function($val, $toFrom) {
				return ($toFrom) ? gpToSg($val) / 0.9982 : sgToGp(0.9982 * $val);
				},
				"plural" => "kilograms per litre", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
			"kilogram per cubic metre" => array("shortLabel" => "kg/m^3", "conversion" => function($val, $toFrom) {
				return ($toFrom) ? gpToSg($val) / 0.0009982 : sgToGp(0.0009982 * $val);
				},
				"plural" => "grams per cubic metre", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
			"pound per US gallon" => array("shortLabel" => "lb/USGal", "conversion" => function($val, $toFrom) {
				return ($toFrom) ? gpToSg($val) / .083303827592 : sgToGp(.083303827592 * $val);
				},
				"plural" => "pounds per US gallon", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
			"pound per Imp gallon" => array("shortLabel" => "lb/ImpGal", "conversion" => function($val, $toFrom) {
				return ($toFrom) ? gpToSg($val) / 0.10004372512 : sgToGp(0.10004372512 * $val);
				},
				"plural" => "pounds per Imp gallon", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
			"pound per cubic foot" => array("shortLabel" => "lb/ft^3", "conversion" => function($val, $toFrom) {
				return ($toFrom) ? gpToSg($val) * 62.315590511 : sgToGp($val / 62.315590511);
				},
				"plural" => "pounds per cubic foot", "notes" => "This is the density of the liquid (at 20°), NOT the sugar content"),
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
						return sgToSugar(gpToSg($val), 0);
					} else {
						return sgToGp(sugarToSg($val, 0));
					}
				},
				"plural" => "sugar grams per litre [sugar solution]",
				"notes" => __("This is for a pure sugar solution - i.e. no sugar-free dry extract.")),
			"sugar gram per litre [apple juice]" => array(
				"shortLabel" => "g/L sugar",
				"conversion" => function($val, $toFrom) {
					if($toFrom) {
						return sgToSugar(gpToSg($val), 0.18);
					} else {
						return sgToGp(sugarToSg($val, 0.18));
					}
				},
				"plural" => "grams sugar per litre",
				"notes" => __("This is for a typical apple juice - i.e. sugar-free dry extract of 18% of the sugar level.")),
			"%ABV potential [sugar solution]" => array(
				"alias" => "potential alcohol % by volume [sugar solution]",
				"shortLabel" => "%ABV potential",
				"conversion" => function($val, $toFrom) {
					if($toFrom) {
						return sgToAbv(gpToSg($val), 0);
					} else {
						return sgToGp(abvToSg($val, 0));
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
						return sgToAbv(gpToSg($val), 0.18);
					} else {
						return sgToGp(abvToSg($val, 0.18));
					}
				},
				"plural" => __("potential alcohol % by volume [cider]"),
				"label" => "shortPadded",
				"notes" => __("This is the calculation for cider assuming all sugar is fermented. It is adjusted for sugar-free dry extract.")),
		)
	);

} // end of MeasurementClasses namespace

