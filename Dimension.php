<?php

namespace MetaTunes\MeasurementClasses;
use ProcessWire\Wire;
use function ProcessWire\wire;

/**
 * @property $dimensionArray
 *
 */

/**
 * #pw-summary Dimension objects are used within Measurement and BaseMeasurement objects to store the dimension of the measurement object
 *
 * #pw-body =
 * If the relevant config file defines a dimension (see below) then a Measurement object will have a dimension attribute.
 * This is an object (of class Dimension) with one data item : dimensionArray.
 * Each SI base unit (of which there are seven) is associated with a text key as follows:
```
const TIME = 'time';
const LENGTH = 'length';
const MASS = 'mass';
const CURRENT = 'current';
const TEMPERATURE = 'temperature';
const SUBSTANCE_AMOUNT = 'substance_amount';
const LUMINOSITY = 'luminosity';
````
 * The dimension of any SI derived unit is an array where the values for each key is the exponent of the relevant base dimension.
 * So, for example, acceleration has a dimensionArray ``['length' => 1, 'time' => -2]``.
 * Each quantity that has an SI base unit or SI derived unit  as its base unit can therefore be associated with such an object.
 * This enables dimensional analysis to be carried out on such quantities when (for example) multiplying and dividing them.
 * However, quantities which do not have a base or derived SI unit as their base unit cannot be given a dimension.
 * The config files include some SI base quantities and some SI derived quantities, but not all of them.
 * It is therefore quite possible, for example, to construct a *Measurement::combineMeasurements()* which results in a SI derived quantity for which there is no config file (in which case a 'BaseMeasurement' object is returned).
 * It is (technically) possible for users to extend the dimensions by artificially adding new ones, providing they are represented by unique text keys, but the user is then responsible for the meaningfulness and consistency of the result.
 * #pw-body
 */
class Dimension extends Wire {

	/** @var string */
	public $dimensionArray;

	// Define standard dimensions
	const TIME = 'time';
	const LENGTH = 'length';
	const MASS = 'mass';
	const CURRENT = 'current';
	const TEMPERATURE = 'temperature';
	const SUBSTANCE_AMOUNT = 'substance_amount';
	const LUMINOSITY = 'luminosity';


	public function __construct(?array $dimension = []) {
		$this->dimensionArray = $dimension;
		parent::__construct();
	}

	/**
	 * Get quantities that are consistent with this dimension
	 *
	 * @return array
	 */
	public function getQuantities() {
		$allQuantities = Measurement::getQuantities();
		$consistentQuantities = [];
		foreach($allQuantities as $quantity) {
			$m = new Measurement($quantity);
			$quantityDimension = $m->dimension;
			/* @var $quantityDimension Dimension */
			if($quantityDimension && $this->dimensionArray == $quantityDimension->dimensionArray) $consistentQuantities[] = $quantity;
		}
		return $consistentQuantities;
	}

	/**
	 * Multiply by another dimension
	 * The result is simply the product of the dimensions.
	 * So ````['length' => 1]```` multiplied by ````['length' => 2, 'time' => -1]```` is ````['length' => 3, 'time' => -1]````
	 *
	 * @param Dimension $dim
	 * @param bool $divide
	 * @return $this
	 */
	public function multiplyBy(Dimension $dim, bool $divide = false): Dimension {
		$dimArray1 = $this->dimensionArray;
		$dimArray2 = $dim->dimensionArray;
		$newDimensionArray = [];
		foreach($dimArray1 as $key => $value) {
			if(array_key_exists($key, $dimArray2)) {
				if($divide) {
					$newDimensionArray[$key] = $value - $dimArray2[$key];
				} else {
					$newDimensionArray[$key] = $value + $dimArray2[$key];
				}
			} else {
				$newDimensionArray[$key] = $value;
			}
		}
		$newItems = array_diff_key($dimArray2, $dimArray1);
		foreach($newItems as $key => $newItem) {
			if($divide) {
				$newDimensionArray[$key] = - $newItem;
			} else {
				$newDimensionArray[$key] = $newItem;
			}
		}
		$result = new Dimension($newDimensionArray);
		return $result->simplify();
	}


	/**
	 * Divide by another dimension (analogous to multiplyBy())
	 *
	 * @see MetaTunes\MeasurementClasses\Dimension::multiplyBy
	 * @param Dimension $dim
	 * @return $this|Dimension
	 */
	public function divideBy(Dimension $dim) {
		return $this->multiplyBy($dim, true);
	}

	/**
	 * Raise dimension to a power
	 * Value of dimension is multiplied by exponent.
	 * E.g ````['length' => 2]```` raised to power 3 is ````['length' => 6]````
	 *
	 * @param float $exponent
	 * @return $this
	 */
	public function power(float $exponent) {
		$newDimensionArray = [];
		foreach($this->dimensionArray as $key => $value) {
			$newDimensionArray[$key] = $value * $exponent;
		}
		$result = new Dimension($newDimensionArray);
		return $result->simplify();
	}

	/**
	 * Removes zero dimensions
	 *
	 * @return $this
	 */
	private function simplify() {
		foreach($this->dimensionArray as $key => $value) {
			if($value == 0) unset($this->dimensionArray[$key]);
		}
		return $this;
	}


}

