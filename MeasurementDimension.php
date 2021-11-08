<?php

namespace ProcessWire;


/**
 * @property $numerator
 * @property $denominator
 */
class MeasurementDimension {

	// Define dimensions using prime numbers
	const TIME = 2;
	const LENGTH = 3;
	const MASS = 5;
	const CURRENT = 7;
	const TEMPERATURE = 11;
	const SUBSTANCE_AMOUNT = 13;
	const LUMINOSITY = 17;

	public function __construct(int $numerator, int $denominator = 1) {
		$this->numerator = $numerator;
		$this->denominator = $denominator;
	}

	public function getQuantities() {
		$allQuantities = Measurement::getQuantities();
		$consistentQuantities = [];
		foreach($allQuantities as $quantity) {
			$quantityDimension = Measurement::getDimension($quantity);
			/* @var $quantityDimension MeasurementDimension */
			if($quantityDimension && $quantityDimension->simplify()->numerator == $this->simplify()->numerator &&
				$quantityDimension->simplify()->denominator == $this->simplify()->denominator) $consistentQuantities[] = $quantity;
		}
		return $consistentQuantities;
	}

	public function simplify() {
		$g = $this->gcd($this->numerator, $this->denominator);
		$this->numerator = $this->numerator / $g;
		$this->denominator = $this->denominator / $g;
		return $this;
	}

	public function multiplyBy(MeasurementDimension $dim) {
		$result = new MeasurementDimension($this->numerator * $dim->numerator, $this->denominator * $dim->denominator);
		return $result->simplify();
	}

	public function divideBy(MeasurementDimension $dim) {
		$result = new MeasurementDimension($this->numerator * $dim->denominator, $this->denominator * $dim->numerator);
		return $result->simplify();
	}

	public function power(int $exponent) {
		$result = new MeasurementDimension(1, 1);
		if($exponent > 0) {
			for($i = 1; $i <= $exponent; $i++) {
				$result = $result->multiplyBy($this);
			}
			return $result->simplify();
		} else if($exponent < 0) {
			for($i = -1; $i >= $exponent; $i--) {
				$result = $result->divideBy($this);
			}
			return $result->simplify();
		} else {
			return $result;
		}
	}

	public function gcd($a, $b) {
		$a = abs($a);
		$b = abs($b);
		if($a < $b) list($b, $a) = array($a, $b);
		if($b == 0) return $a;
		$r = $a % $b;
		while($r > 0) {
			$a = $b;
			$b = $r;
			$r = $a % $b;
		}
		return $b;
	}
}

