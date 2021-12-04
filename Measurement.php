<?php namespace MetaTunes\MeasurementClasses;
use ProcessWire\{FieldtypeMeasurement, WireData};
use function ProcessWire\{wire, __};

require_once 'Exceptions/MeasurementDifferentTypeException.php';
require_once 'Exceptions/MeasurementException.php';
require_once 'Exceptions/MeasurementInvalidUnitException.php';
require_once 'Exceptions/FileNotFoundException.php';

require_once 'ConversionDefinition.php';
require_once 'ConversionRepository.php';

/**
 * @property string $quantity The physical quantity to be measured
 * @property array $units The units to be presented as options
 * @property string $unit The current selected unit
 * @property string $magnitude The magnitude (in terms of the selected unit)
 * @property string $baseUnit The base unit for this quantity
 * @property string $baseMagnitude The magnitude in terms of base units
 * @property Dimension $dimension The dimension of this measurement
 * @property array $format The format for rendering this measurement
 */

/**
 * #pw-summary BaseMeasurement objects are used for measurements that do not have associated quantities - typically for intermediate results in Measurement methods
 *
 * #pw-body =
 * BaseMeasurement objects only have two data items :
 * * dimension - The dimension of the measurement (held as a Dimension object)
 * * baseMagnitude - The value of the measurement
 * #pw-body
 */
class BaseMeasurement extends WireData{

	/**
	 * @param $baseMagnitude
	 * @param Dimension $dimension
	 */
	public function __construct($baseMagnitude, Dimension $dimension) {
		$this->set('baseMagnitude', $baseMagnitude);
		$this->set('dimension', $dimension);
		parent::__construct();
	}

	/**
	 * Multiply the magnitudes and dimensions to return a new BaseMeasurement
	 *
	 * @param $multiplier
	 * @return BaseMeasurement|Measurement
	 */
	public function multiplyBy($multiplier) { //No return type given as it differs from Measurement::multiplyBy()
		if(is_object($multiplier) && (get_class($multiplier) == __CLASS__ || get_class($multiplier) == 'MetaTunes\MeasurementClasses\Measurement')) {
			if(get_class($multiplier) == 'MetaTunes\MeasurementClasses\Measurement') {
				$factor = $multiplier->valueAsBase();
			} else {
				$factor = $multiplier->baseMagnitude;
			}
			$result = new BaseMeasurement($this->baseMagnitude * $factor, $this->dimension->multiplyBy($multiplier->dimension));
		} else {
			$multiplier = (float) $multiplier;
			$result = new BaseMeasurement($this->baseMagnitude * $multiplier, $this->dimension);
		}
		return $result;
	}


	/**
	 * Divide the magnitudes and dimensions to return a new BaseMeasurement
	 *
	 * @param $divisor
	 * @return BaseMeasurement|Measurement
	 */
	public function divideBy($divisor) { //No return type given as it differs from Measurement::divideBy()
		if(is_object($divisor) && (get_class($divisor) == __CLASS__ || get_class($divisor) == 'MetaTunes\MeasurementClasses\Measurement')) {
			if(get_class($divisor) == 'MetaTunes\MeasurementClasses\Measurement') {
				$factor = $divisor->valueAsBase();
			} else {
				$factor = $divisor->baseMagnitude;
			}
			$result = new BaseMeasurement($this->baseMagnitude / $factor, $this->dimension->divideBy($divisor->dimension));
		} else {
			$divisor = (float) $divisor;
			$result = new BaseMeasurement($this->baseMagnitude / $divisor, $this->dimension);
		}
		return $result;
	}

}

/**
 * #pw-summary Measurement objects are used within the FieldtypeMeasurement to store quantities, units and magnitudes
 *
 * #pw-body =
 * Measurement objects have the following data items (not all of which may be present):
 * * quantity - The quantity being measured
 * * dimension - The dimension of the quantity (held as a Dimension object)
 * * unit - The unit of measurement
 * * magnitude - The value of the measurement in the given magnitude (could be a combination magnitude - e.g. feet & inches -  stored as an array)
 * * baseUnit - The base unit for maeasuring this quantity
 * * baseMagnitude - The value of the measurement in the base units for this quantity
 * * various format items -  to be used for rendering
 *
 * They also have a property called 'units' which stores all the available unit definitions and conversions for this quantity (as a ConversionRepository object).
 * #pw-body
 */
class Measurement extends BaseMeasurement
{
    /** @var ConversionRepository */
    public $units; // stores all the conversion definitions for compatible units

	/**
	 * @param string|null $quantity
	 * @param string|null $unit
	 * @param null $magnitude
	 */
    public function __construct(?string $quantity = null, ?string $unit = null, $magnitude = null)
    {
		if(!is_array($magnitude)) $magnitude = explode('|', $magnitude);
		try {
			if($quantity) $this->loadUnits($quantity);
		} catch(FileNotFoundException $e) {
			$this->error($e->getMessage());
			return;
		}
		$magnitude = $this->sanitizer->magnitude($magnitude);
		$this->set('magnitude', $magnitude);
		$this->set('quantity', $quantity);
		if($quantity) {
			$base = $this->units->base;
			if(!$unit) $unit = $base;
			$this->set('unit', $unit);
			$this->set('baseUnit', $base);
			$dimension = $this->units->dimension;
			$this->set('dimension', $dimension);
			//bd($magnitude, 'MAGNITUDE');
			if((is_array($magnitude) && count($magnitude) > 0 && $magnitude[0]) || (!is_array($magnitude) && $magnitude)) {
				$baseMagnitude = ($unit) ? $this->valueAsBase() : (($magnitude) ? $magnitude[0] : null);
			} else {
				$baseMagnitude = null;
			}
			parent::__construct($baseMagnitude, $dimension);
		}
		if($this->unit and $this->quantity) {
			$units = $this->getUnits();
//			bd($units, 'units');
//			bd($unit, 'unit');
			foreach($units[$unit] as $key => $item) {
				if($key == 'conversion') continue;
//				bd([$key, $item]);
				$this->set($key, $item);
			}
		}
//		bd($this, 'constructed measurement object');
    }

    /**
	 * Get the details of the quantity and all its units
     * Returns file from templates folder (if it exists), rather than the module folder
	 *
     * @param ?string $path Load your own units file if you want.
     * @throws FileNotFoundException
     */
    public function loadUnits(string $path): void
    {
		$path = $this->configFile($path);
    	//bd($path, 'loading');
    	$this->units = $this->fromFile($path);
    }

	/**
	 * Get the data from the config file
	 *
	 * @throws MeasurementException
	 * @throws FileNotFoundException
	 */
	private function fromFile(string $path): ConversionRepository {
		if(!file_exists($path)) {
			throw new FileNotFoundException(sprintf(__('File could not be found. Given path=%s. 
            Either use the name of one of the pre defined configuration files or pass the complete path to the file.'), $path));
		}
		$quantity = basename($path, '.php');

		$data = include_once $path;
		//bd($data, 'DATA');
		if($data === true) {
			// using config here rather than a session var is it accepts anonymous functions
			//bd('GETTING DATA');
			$data = $this->config->get($quantity . '_data');
			//bd($data, 'got data');
		} else {
			//bd('SETTING DATA');
			$this->config->set($quantity . '_data', $data);
			//bd($this->config->get($quantity . '_data'), 'set data');
		}
		if(!is_array($data)) {
			throw new MeasurementException(__("The Unit definition must be an array."));
		}
		$base = $data['base'];
		$notes = (isset($data['notes'])) ? $data['notes'] : '';
		$dimension = (isset($data['dimension'])) ? $data['dimension'] : null;
		$units = ($this->session->get($quantity)) ?: [];  // in case any units have been added during the session via API
		$units = array_merge($data['units'], $units);
		$list = array_map(function($key, $definition) use ($base) {
			if(!isset($definition['shortLabel']) || !isset($definition['conversion'])) {
				throw new MeasurementException(__("A conversion definition must have a shortLabel and conversion property."));
			}
			return new ConversionDefinition($key, $base, $definition);
		}, array_keys($units), $units);
		//bd($list, 'list');
		return new ConversionRepository($quantity, $dimension, $notes, $base, ...$list);
	}

    /**
     * Set the measurement from the given value & unit.
	 * If $value is a number: sets the magnitude to the value, converting from the specified compatible unit (if given) to the current unit of the measurement object.
	 * If $value is a Measurement object: converts the $value measurement to the units of the current object.
	 * This method updates the current object.
     *
     * @param  float|Measurement  $value -  a numeric value to base conversions on
     * @param  ?string $unit (optional) - the unit symbol for the start value
     * @return Measurement
     * @throws MeasurementException - general errors
     * @throws MeasurementInvalidUnitException - specific invalid unit exception
	 * @throws MeasurementDifferentTypeException
     */
    public function convertFrom($value, ?string $unit = null): Measurement
    {
		if(is_a($value, __CLASS__)) {
			/* @var $value Measurement */
			$unit = $value->unit;
			$value = $value->magnitude;

		}
        if (!$unit || $unit == $this->unit) {
            $this->magnitude = $value;
            return $this;
        }
        //Convert single-valued arrays to float (only want combi units to be arrays for conversion)
        if(is_array($value) and count($value) == 1) $value = $value[0];
        if (!$this->units->unitExists($unit)) {
            throw new MeasurementInvalidUnitException(sprintf($this->_('Conversion from Unit=%s not possible - unit does not exist.'), $unit));
        }
		$conversionFrom = $this->units->getConversion($unit);
		$this->baseUnit = $conversionFrom->getBaseUnit();
		$this->magnitude = $conversionFrom->convertToBase($value);
		if(!$this->unit) $this->set('unit', $this->baseUnit);
		$toUnit = $this->unit;
		$conversionTo = $this->units->getConversion($toUnit);
		if($this->baseUnit !== $conversionTo->getBaseUnit()) {
			throw new MeasurementDifferentTypeException($this->_("Cannot Convert Between Units of Different Types"));
		}
		$this->baseMagnitude = $this->magnitude;
		$this->magnitude = $conversionTo->convertFromBase($this->magnitude);
		return $this;
    }

	/**
	 * Set the magnitude of the current measurement object from a base unit magnitude
	 *
	 * @param $value
	 * @return $this
	 */
    public function convertFromBase($value): Measurement {
		try {
			$baseUnit = $this->units->getConversion($this->unit)->getBaseUnit();
		} catch(MeasurementInvalidUnitException $e) {
			$this->error($e->getMessage());
			return $this;
		}
    	try {
			$result = $this->convertFrom($value, $baseUnit);
			$result->set('baseMagnitude', $value);
			return $result;
		} catch(MeasurementInvalidUnitException|MeasurementDifferentTypeException|MeasurementException $e) {
			$this->error($e->getMessage());
			return $this;
		}
	}

	/**
	 * Converts the object to one with the specified unit, carrying out the relevant conversion of the magnitude.
	 * Note that if the specified unit is not in the selectable options list, then blank will be displayed as an option; changing the field setup details to include the relevant option will cause it to display.
	 * This method updates the current object.
	 *
	 * @param string $unit
	 * @param int|null $decimals optionally limit the number of decimals
	 * @param bool $round optionally round, if decimals != null
	 * @return $this
	 * @throws \ProcessWire\WireException
	 */
    public function convertTo(string $unit, ?int $decimals = null, bool $round = true): Measurement {
		try {
			$this->magnitude = $this->valueAs($unit, $decimals, $round);
		} catch(MeasurementException|MeasurementInvalidUnitException $e) {
			$this->error($e->getMessage());
		}
		//bd($this->magnitude, 'magnitude1');
		$this->unit = $unit;
		$units = $this->getUnits();
		// unset optional elements
		//bd($this->formatOptions(), 'formatoptions');
		foreach($this->formatOptions() as $key => $option) {
			//bd($key, 'remove key');
			$this->remove($key);
		}
		// then set the ones in the config file
		if(isset($units[$unit])) foreach($units[$unit] as $key => $item) {
			if($key == 'conversion') continue;
			$this->set($key, $item);
		}
		//bd($this->magnitude, 'magnitude2');
		//bd($this, 'this');
		return $this;
	}

	/**
	 * Convert this measurement to base units
	 * This method updates the current object.
	 *
	 * @param int|null $decimals optionally limit the number of decimals
	 * @param bool|null $round optionally round, if decimals != null
	 * @return $this
	 * @throws \ProcessWire\WireException
	 */
	public function convertToBase(?int $decimals = null, ?bool $round = true): Measurement {
		try {
			$baseUnit = $this->units->getConversion($this->unit)->getBaseUnit();
			$this->convertTo($baseUnit, $decimals, $round);
		} catch(MeasurementInvalidUnitException $e) {
			$this->error($e->getMessage());
		}
		return $this;
	}

	/**
	 * Add $measurement to this measurement.
	 * The result is in the units of this measurement unless $unit is specified ($measurement will be converted as appropriate).
	 * Returns a new Measurement object.
	 *
	 * @param Measurement $measurement
	 * @param string|null $unit
	 * @return Measurement
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementException
	 */
	public function add(Measurement $measurement, ?string  $unit = null): Measurement {
		return self::sumMeasurements([$this, $measurement], $unit);
	}

	/**
	 * Sum the measurements
	 * Updates the current object which must be of the same quantity as the measurements to be summed.
	 * Typically set ````$m = new Measurement($quantity);```` and then ````$m->sumOf(...);````
	 *
	 * @param ...$measurements
	 * @return $this
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementException
	 */
	public function sumOf(...$measurements): Measurement {
		$unit = $this->get('unit');
		$result = self::sumMeasurements($measurements, $unit);
		if(!$this->get('quantity')) {
			$this->__construct($result->get('quantity'), $result->get('unit'), $result->get('magnitude'));
		} else {
			if($this->quantity != $result->quantity) {
				throw new MeasurementDifferentTypeException(sprintf($this->_('Measurements are not of the same quantity. Object is %1$s, measurements are %2$s.'), $this->quantity, $result->quantity));
			}
			$this->magnitude = $result->magnitude;
			$this->baseMagnitude = $result->baseMagnitude;
		}
		return $this;
}

	/**
	 * Returns a Measurement object being the product (or a BaseMeasurement object if there is no matching quantity for the resulting dimension).
	 * If $multiplier is a number then the measurement will simply be scaled.
	 * If $multiplier is a Measurement object then the result will be computed using dimensional analysis
	 * 	(both the current object and $measurement must be of quantities that have dimensions defined).
	 * If $quantity and $unit are not defined then they will be inferred as far as possible,
	 * 	otherwise they will be checked for consistency and the result will be returned as specified.
	 *
	 * @param $multiplier
	 * @param string|null $quantity
	 * @param string|null $unit
	 * @return $this|BaseMeasurement|Measurement
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementException
	 */
	public function multiplyBy($multiplier, ?string $quantity = null, ?string $unit = null) {
		if(!is_a($multiplier, __CLASS__) && !is_a($multiplier, get_parent_class($this))) {
			$multiplier = (float) $multiplier;
			if($quantity && $quantity != $this->get('quantity')) {
				throw new MeasurementDifferentTypeException(sprintf($this->_('Incompatible quantities - argument of %1$s but object is %2$s'), $quantity, $this->get('quantity')));
			}
			if (!$quantity) $quantity = $this->get('quantity');
			$baseMagnitude = $this->valueAsBase();
			$this->set('baseMagnitude', $baseMagnitude);
			$baseMagnitude	*= $multiplier;
			$baseUnit = $this->units->base;
			$result = new Measurement($quantity, $baseUnit, $baseMagnitude);
			if(!$unit) $unit = $this->get('unit');
			return $result->convertTo($unit);
		}
		return self::multiplyMeasurements([$this, $multiplier], $quantity, $unit);
	}

	/**
	 * Return the negative magnitude
	 * (e.g. to use in a sumOf(...))
	 *
	 * @return $this|BaseMeasurement|Measurement
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementException
	 */
	public function negate() {
		return $this->multiplyBy(-1);
	}

	/**
	 * Raise the measurement to the given power (any real number).
	 * If the result is has a dimension not matching any quantity, it returns a BaseMeasurement (dimensionless of magnitude 1 if $exp = 0).
	 *
	 * @param float $exp
	 * @param string|null $quantity
	 * @param string|null $unit
	 * @return BaseMeasurement|Measurement
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementInvalidUnitException
	 */
	public function power(float $exp, ?string $quantity = null, ?string $unit = null) {
		$newMagnitude = $this->baseMagnitude ** $exp;
		$newDimension = $this->dimension->power($exp);
		$result = self::inferResult($newDimension, $quantity, $unit, $newMagnitude);
		return $result;
	}

	/**
	 *  Multiplies the measurements using dimensional analysis (see *multiplyBy()* ).
	 * This updates the current object, which must have a quantity, dimension and units consistent with the intended product.
	 *
	 * @param ...$measurements
	 * @return $this|Measurement|BaseMeasurement
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementException
	 */
	public function productOf(...$measurements) {
		$quantity = $this->get('quantity');
		$unit = $this->get('unit');
		$result = self::multiplyMeasurements($measurements, $quantity, $unit);
		if(!$this->get('quantity')) {
			$this->__construct($result->get('quantity'), $result->get('unit'), $result->get('magnitude'));
		} else {
				if($this->get('quantity') != $result->get('quantity')) {
				throw new MeasurementDifferentTypeException(sprintf($this->_('Measurements are not of the same quantity. Object is %1$s, product is %2$s.'), $this->get('quantity'), $result->get('quantity')));
			}
			$this->magnitude = $result->magnitude;
			$this->baseMagnitude = $result->baseMagnitude;
		}
		return $this;
	}

	/**
	 * Subtract $measurement from this measurement.
	 * The result is in the units of this measurement unless $unit is specified ($measurement will be converted as appropriate).
	 * Returns a new Measurement object.
	 *
	 * @param Measurement $measurement
	 * @param string|null $unit
	 * @return Measurement
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementException
	 */
	public function subtract(Measurement $measurement, ?string $unit = null): Measurement {
		return $this->add($measurement->negate(), $unit);
	}

	/**
	 * Returns a Measurement object being the quotient (or a BaseMeasurement object if there is no matching quantity for the resulting dimension).
	 * If $divisor is a number then the measurement will simply be scaled.
	 * If $divisor is a Measurement object then the result will be computed using dimensional analysis
	 * 	(both the current object and $measurement must be of quantities that have dimensions defined).
	 * If $quantity and $unit are not defined then they will be inferred as far as possible, otherwise they will be checked for consistency and the result will be returned as specified.
	 *
	 * @param $divisor
	 * @param string|null $quantity
	 * @param string|null $unit
	 * @return $this|BaseMeasurement|Measurement
	 * @throws MeasurementDifferentTypeException
	 */
	public function divideBy($divisor, ?string $quantity = null, ?string $unit = null) {
		if(!is_a($divisor, __CLASS__) && !is_a($divisor, get_parent_class($this))) {
			$multiplier = 1 / $divisor;
		} else {
			/* @var $divisor Measurement */
			$multiplier = $divisor->invert();
		}
		return $this->multiplyBy($multiplier);
	}

	/**
	 * Raise to the power -1. See power().
	 *
	 * @param string|null $quantity
	 * @param string|null $unit
	 * @return BaseMeasurement|Measurement
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementInvalidUnitException
	 */
	public function invert(?string $quantity = null, ?string $unit = null) {
		return $this->power(-1, $quantity, $unit);
	}


	/**
	 * Convert from value to new unit value
	 * Returns the magnitude converted to the specified unit (or an error if the specified unit does not exist or is not compatible).
	 *
	 * @param string $unit -  the unit symbol (or array of symbols) for the conversion unit
	 * @param  ?int $decimals (optional, default-null) - the decimal precision of the conversion result
	 * @param boolean $round (optional, default-true) - round or floor the conversion result
	 * @return array|float|int|null
	 * @throws MeasurementException
	 */
    public function valueAs(string $unit, ?int $decimals = null, bool $round = true)
    {
		try {
//			bd($this, 'this valueAs');
//			bd($unit, 'unit in valueAs');
			if(is_null($this->get('magnitude'))) {
				throw new MeasurementException($this->_("From Value Not Set."));
			}
			if(is_array($unit)) {
				return $this->valueAsMany($unit, $decimals, $round);
			}
			$magnitude = $this->get('magnitude');
			if(is_array($magnitude) && (count($magnitude) == 1)) $this->set('magnitude', $magnitude[0]);
			if(!$this->units->unitExists($unit)) {
				throw new MeasurementInvalidUnitException(sprintf($this->_('Conversion from Unit=%s not possible - unit does not exist.'), $unit));
			}
			$conversionFrom = $this->units->getConversion($this->get('unit'));
			$conversionTo = $this->units->getConversion($unit);
//			bd($conversionTo, 'conversionTo');
//			bd($conversionFrom, 'conversionFrom');
			if(!$this->baseUnit) {
				$this->baseUnit = $conversionFrom->getBaseUnit();
			}
			if($conversionTo->getBaseUnit() !== $this->baseUnit) {
				throw new MeasurementDifferentTypeException($this->_("Cannot Convert Between Units of Different Types"));
			}
			$baseMeasurement = $conversionFrom->convertToBase($this->magnitude);
			$result = $conversionTo->convertFromBase($baseMeasurement);
			if(!is_null($decimals)) {
				return $this->round($result, $decimals, $round);
			}
			//bd($result, 'result');
			return $result;
		} catch (MeasurementDifferentTypeException|MeasurementInvalidUnitException|MeasurementException $e) {
			$this->error($e->getMessage());
			return null;
		}
    }

	/**
	 * As for valueAs() with $unit = the base unit.
	 *
	 * @param int|null $decimals
	 * @param bool $round
	 * @return array|float|int|null
	 * @throws MeasurementException
	 */
    public function valueAsBase(?int $decimals = null, bool $round = true) {
		$baseUnit = $this->units->base;
		return $this->valueAs($baseUnit, $decimals, $round);
	}

	/**
	 * Given a base unit magnitude $value, return the magnitude in the given $unit
	 * If $unit is a combination unit, the result will be an array
	 *
	 * @param $value
	 * @param $unit
	 * @return float|int
	 */
	public function valueFromBase($value, $unit) {
		try {
			$conversionTo = $this->units->getConversion($unit);
		} catch(MeasurementInvalidUnitException $e) {
			//bd(debug_backtrace(), 'BACKTRACE');
			$this->error($e->getMessage());
			return 0;
		}
		try {
			return $conversionTo->convertFromBase($value);
		} catch(MeasurementException|\TypeError $e) {
			$this->error($e);
			return 0;
		}
//		$baseUnit = $this->units->getConversion($this->get('unit'))->getBaseUnit();
	}

    /**
	 * Returns an array of all conversion values for units in the specified array.
	 *
     * @param string[] $units
     * @param ?int     $decimals
     * @param bool     $round
     * @return array
     */
    public function valueAsMany($units = [], ?int $decimals = null, $round = true): array {
		try {
			return array_map(function($unit) use ($decimals, $round) {
				return $this->valueAs($unit, $decimals, $round);
			}, $units);
		} catch(MeasurementException $e) {
			$this->error($e->getMessage());
			return array_map(function($unit) {
				return null;
			}, $units);
		}
    }

    /**
     * Returns an array of all conversion values for compatible units.
     *
     * @param int|null $decimals
     * @param bool     $round
     * @return array
     * @throws MeasurementException
     */
    public function valueAsAll(?int $decimals = null, bool $round = true)
    {
		try {
			$conversionFrom = $this->units->getConversion($this->get('unit'));
			if(!$this->baseUnit) {
				$this->baseUnit = $conversionFrom->getBaseUnit();
			}
			if(is_null($this->magnitude)) {
				throw new MeasurementException($this->_("From Value Not Set"));
			}

			if(is_null($this->baseUnit)) {
				throw new MeasurementException($this->_("No From Unit Set"));
			}

			return $this->valueAsMany($this->getConversions($this->baseUnit), $decimals, $round);
		} catch(MeasurementInvalidUnitException|MeasurementException $e) {
			$this->error($e->getMessage());
			return [];
		}
    }

	/**
	 * Add a new unit (compatible with the current one - i.e. measuring the same quantity) and conversion in memory.
	 * $params should be an array in the same format as the definition of a unit in the config file (but do NOT use anonymous functions).
	 * If you supply a field name (of FieldtypeMeasurement) as $selectableIn, then the new unit will be a selectable unit in that field
	 * 	(and if a template name is supplied in $template then the selectable unit will only be included in that template context).
	 * If the unit already exists, then the existing parameters will be removed and replaced by the specified conversion and options.
	 * To amend a unit without specifying all parameters, use amendUnit().
	 * Returns true/false.
	 *
	 * @param string $unit
	 * @param array $params
	 * @param string|null $selectableIn
	 * @param string|null $template
	 * @return bool
	 * @throws MeasurementException
	 * @throws MeasurementInvalidUnitException
	 * @throws \ProcessWire\WireException
	 */
    public function addUnit(string $unit, array $params, ?string $selectableIn = null, ?string $template = null)
    {
		$quantity = $this->get('quantity');
		if(isset($params['conversion'])) {
			$conversion = $params['conversion'];
		} else {
			throw new MeasurementException($this->_("Conversion not specified in addUnit parameters."));
		}
		$definition = ($this->session->get($quantity)) ?: [];
		$definition[$unit] = $params;
		if($selectableIn) {
			FieldtypeMeasurement::addSelectableUnit($selectableIn, $unit, $template);
			//ToDo - check that it is OK to delete the following
//			$units = ($this->get('units')) ?: [];
//			if(!in_array($unit, $units)) {
//				$units[] = $unit;
//				$this->set('units', $units);
//			}
		}
		// replace any existing conversion
		if(array_key_exists($unit, $this->getUnits())) {
			//bd('REMOVING');
			$this->units->removeConversion($unit);
		}
		$this->session->set($quantity, $definition);
		//bd($definition);
		$baseUnit = $this->units->base;
		$conversion = new ConversionDefinition($unit, $baseUnit, $definition[$unit]);

		$this->units->addConversion($conversion);
		//bd($this->units);
		//bd($this->session->get($quantity), 'session units');
		return true;

	}

	/**
	 * Amend a unit definition in memory (which was added using addUnit() ).
	 * $conversion can be just a multiplier number or a callable function - see config files below for more details $options is the same format as in format().
	 * Note that the amendment creates a temporary new unit which overrides the existing one - it is not added to the related file.
	 * To revert this amendment, you need to use removeUnit().
	 * Returns true/false.
	 *
	 * @param string $unit
	 * @param $conversion
	 * @param array $options
	 * @return bool
	 * @throws MeasurementException
	 * @throws MeasurementInvalidUnitException
	 * @throws \ProcessWire\WireException
	 */
	public function amendUnit(string $unit, $conversion, array $options = []): bool {
		$quantity = $this->get('quantity');
		$params = array_merge(['conversion' => $conversion], $options);

		$definitions = $this->getUnits();

		if($definitions && isset($definitions[$unit])) {
			$definition = $definitions[$unit];
			//bd(['def' => $definition, 'params' => $params]);
			$newParams = array_merge($definition, $params);
			$this->addUnit($unit, $newParams);
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Remove a temporary unit - which had been added using addUnit() - from the session.
	 *
	 * @param string $unit
	 * @return bool
	 * @throws MeasurementInvalidUnitException
	 */
    public function removeUnit(string $unit): bool
    {
        $this->units->removeConversion($unit);
		$quantity = $this->get('quantity');
		$sessionUnits = $this->session->get($quantity);
		unset($sessionUnits[$unit]);
		$this->session->set($quantity, $sessionUnits);
        return true;
    }

	/**
	 * List all available conversion units for given unit.
	 * Get all the compatible units for $unit - i.e. those which it can be converted to/from.
	 * If $unit is null, this is all the compatible units for the current unit of the measurement object.
	 * Returns an array ```['unit name1' => 'unit name1', 'unit name2' => 'unit name2', etc...]```.
	 *
	 * @param string|null $unit
	 * @return string[]
	 * @throws MeasurementInvalidUnitException
	 */
    public function getConversions(?string $unit = null): array
    {
		if(!$unit) $unit = $this->get('unit');
		if(!$unit) return [];
        return $this->units->getAvailableConversions($unit);
    }

	/**
	 * @param float $value
	 * @param int $decimals
	 * @param bool $round
	 * @return float
	 */
    private function round(float $value, int $decimals, bool $round): float
    {
        $mode = $round ? PHP_ROUND_HALF_UP : PHP_ROUND_HALF_DOWN;
        return round($value, $decimals, $mode);
    }

	/**
	 * Render the measurement using default or specified options.
	 * $options are as for format() and will temporarily over-ride any previous setting by format().
	 *
	 * @param array|null $options
	 * @return string
	 */
	public function ___render(?array $options = []): string {
    	if(!$options) {
			$options = ($this->format) ?: $this->formatOptions();
		} else {
    		$options = $this->formatOptions($options);
		}
		//bd($options, 'options in render');
		$magnitudes = (is_array($this->magnitude)) ? $this->magnitude : [$this->magnitude];
		$units = explode('|', $this->get('unit'));
		$labels = explode('|', $this->get('shortLabel'));
		$plurals = ($this->get('plural')) ? explode('|', $this->get('plural')) : [];
		//bd($plurals, 'plurals');
		$aliases = ($this->get('alias')) ? explode('|', $this->get('alias')) : explode('|', $this->get('unit'));
		//bd($aliases, 'aliases');
		$out = '';
		$count = count($magnitudes);
		foreach($magnitudes as $index => $magnitude) {
			$unit = (isset($units[$index])) ? $units[$index] : '';
			$shortLabel = (isset($labels[$index])) ? $labels[$index] : '';
			$plural = (isset($plurals[$index])) ? $plurals[$index] : null;
			$join = (isset($options['join'][$index])) ? $options['join'][$index] : '';
			$usePlural = ($magnitude != 1);
			$alias = (isset($aliases[$index])) ? $aliases[$index] : $unit;
			switch($options['label']) {
				case 'long' :
					if($usePlural) {
						//bd($plural, 'plural');
						$label = ($plural) ? ' ' . $plural : ' ' . $alias . 's';
					} else {
						$label = ' ' . $alias;
					}
					break;
				case 'none' :
					$label = '';
					break;
				case 'shortPadded' :
					if($options['position'] == 'prepend') {
						$label = $shortLabel . ' ';
					} else {
						$label = ' ' . $shortLabel;
					}
					break;
				case 'short' :
				default :
					$label =  $shortLabel;
					break;
			}
			if($options['decimals'] !== null) {
				$magnitude = ($options['round']) ? round($magnitude, $options['decimals']) : intval($magnitude);
			}
			$join = ($index < $count - 1) ? $join : '';
			if($options['position'] == 'prepend') {
				$out .= ($magnitude == 0 && $index < $count - 1 && $options['skipNil']) ? '' : $label . $magnitude . $join;
			} else {
				$out .= ($magnitude == 0 && $index < $count - 1 && $options['skipNil']) ? '' : $magnitude . $label . $join;
			}
		}
		return $out;
	}

	/**
	 * Change the formatting used in subsequent rendering.
	 * The default options are:
	 * ````
	$defaultOptions = [
	'label' => 'short', // 'short', 'shortPadded' (with space to separate from magnitude), 'long', 'none'
	'position' => 'append', // 'append' - after the magnitude, 'prepend' - before the magnitude (only applies to shortLabels.
	'decimals' => null,
	'round' => true, // otherwise value will be truncated
	'join' => [' '], // an array for joining characters for combi units (one less element than the number of units in the combi) - e.g. [' and ']
	'skipNil' => true,
	'alias' => $unit,
	'notes' => null,
	'plural' => null
	];
	````
	 * 'label =>'short' provides the abbreviations; for the long names (pluralised where appropriate), use 'long'.
	 * 'position' determines the location of the shortLabel (before or after the magnitude).
	 * Long names will always be after the magnitude and preceded by a space. Use 'label' => 'none' to omit labels.
	 * If 'round' is false then the value will be truncated.
	 * 'join' and 'skipNil' are only relevant for combination units - see below (note that 'join' is an array).
	 *
	 * For plurals which are not achieved by adding an 's', the plural is given in the config file. Other options may be specified in the config file, in which case they will override the general defaults (but will in turn be overridden by any format($options)).
	 * *Combination units*: The options above operate on each magnitude component successively. The 'join' elements are used to append each magnitude/label group. E.g.
	````
	$page->length->format(['label' => 'long', 'decimals' => 1, 'join' => [' and ']]);
	````
	results in something like: '1 foot and 3.4 inches'.
	 * Note that the number of elements in the join array is one less than the number of elements in the combination unit
	 * 	- i.e. there is no 'join' string after the last element (any excess elements will be ignored and a shortfall will just result in concatenation).
	 * The 'skipNil' option, if true, will cause any leading elements to be suppressed - so '1 inch' not '0 feet 1 inch'. The last element will always be displayed.
	 *
	 * @param array $options
	 */
	public function format(array $options = []) {
		$options = $this->formatOptions($options);
		$this->format = $options;
	}

	/**
	 * @param array $options
	 * @return array
	 */
	protected function formatOptions(array $options = []): array {
		$units = $this->units->definitions;
		$unit = $this->unit;

    	// Overall defaults
		$defaultOptions = [
			'label' => 'short', // 'short', 'shortPadded' (with space to separate from magnitude), 'long', 'none'
			'position' => 'append', // 'append' - after the magnitude, 'prepend' - before the magnitude (only applies to shortLabels.
			'decimals' => null,
			'round' => true, // otherwise value will be truncated
			'join' => [' '], // an array for joining characters for combi units (one less element than the number of units in the combi) - e.g. [' and ']
			'skipNil' => true,
			'alias' => $unit,
			'notes' => null,
			'plural' => null
		];

		// Over-ride by config file settings
		if (isset($units[$unit])) {
			foreach($units[$unit] as $key => $item) {
				if(!in_array($key, $defaultOptions)) continue;
				$defaultOptions[$key] = $item;
			}
		}

		// Over-ride by supplied options
		foreach($options as $key => $option) {
			$defaultOptions[$key] = $option;
		}
		//bd($defaultOptions, 'defaultoptions');
		return $defaultOptions;
	}


	/**
	 * Return an array of all quantities which have config files (in module or template folders)
	 *
	 * @return array
	 */
	public static function getQuantities(): array {
		$configPath =  __DIR__ . '/Config/';
		$configFiles = wire()->files->find($configPath);
		$userPath =  wire()->config->paths->templates . 'Measurement/';
		$userFiles = wire()->files->find($userPath);
		$allFiles = array_merge($configFiles, $userFiles);
		$quantities = [];
		foreach($allFiles as $file) {
			$quantities[] = basename($file, '.php');
		}
		return array_unique($quantities);
	}

	/**
	 * Returns the units which are compatible (from the config file) as an array.
	 *
	 * @return array|ConversionDefinition[]|null
	 * @throws \ProcessWire\WireException
	 */
	public function getUnits(): ?array {
		$units = ($this->wire()->session->get($this->get('quantity'))) ?: [];
		$units = array_merge($this->units->definitions, $units);
		return $units;
	}

	/**
	 * Get the file name of the config file to use (files in templates folder will override those in module folder).
	 *
	 * @param $quantity
	 * @return string
	 */
	private function configFile($quantity): string {
		$configFile = __DIR__ . '/Config/' . $quantity . '.php';
		$userFile = wire()->config->paths->templates . 'Measurement/' . $quantity . '.php';
		if(file_exists($userFile)) {
			$unitsFile = $userFile;
		} else {
			$unitsFile = $configFile;
		}
		return $unitsFile;
	}

	/**
	 * Sum all the given measurements (must be of same quantity).
	 *
	 * @param array $measurements
	 * @param string|null $unit
	 * @return Measurement
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementException
	 */
	private static function sumMeasurements(array $measurements, ?string $unit = null): Measurement {
		$baseMagnitude = 0;
		$mixedUnits = false;
		$quantity = null;
		$suppliedUnit = $unit;
		foreach($measurements as $key => $measurement) {
			if(!is_a($measurement, __CLASS__)) {
				throw new MeasurementException(sprintf(__('Array contents for measurement[%1$s] in sumMeasurements() must be a Measurement object'), $key));
			}
			$newBaseMagnitude = $measurement->valueAsBase();
			if($measurement->get('baseMagnitude') && $newBaseMagnitude != $measurement->get('baseMagnitude')) {
				wire()->warning(sprintf(__('Updating base magnitude for measurement %1$s from %2$s to %3$s.'), $key, $measurement->get('baseMagnitude'), $newBaseMagnitude));
			}
			$measurement->set('baseMagnitude', $newBaseMagnitude);
			if($key == 0) {
				$quantity = $measurement->get('quantity');
				$unitInit = $measurement->get('unit');
				if(!$unit) $unit = $unitInit;
			}
			if($measurement->get('quantity') != $quantity) {
				throw new MeasurementDifferentTypeException(sprintf(__('Measurement %1$s has different quantity type (%2$s) from previous measurements (%3$s)'), $key, $measurement->get('quantity'), $quantity));
			}
			if($measurement->get('unit') != $unit) {
				$mixedUnits = true;
			}
			$baseMagnitude += $newBaseMagnitude;
		}

		$result = new Measurement($quantity, null, $baseMagnitude);
		if($suppliedUnit || !$mixedUnits) {
			$result->convertTo($unit);
		} else {
			wire()->warning(__("Result of measurement addition is given in base units as inconsistent measurement units supplied and no unit argument was given"));
		}
		return $result;
	}

	/**
	 * Multiply all the given measurements, using dimensional analysis to infer resultant dimension (and quantity if it exists and is compatible).
	 *
	 * @param array $multipliers
	 * @param string|null $quantity
	 * @param string|null $unit
	 * @return BaseMeasurement|Measurement
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementException
	 * @throws MeasurementInvalidUnitException
	 */
	private static function multiplyMeasurements(array $multipliers, ?string $quantity = null, ?string $unit = null) {
		$tempDimension = new Dimension();
		$tempMagnitude = 1;
		foreach($multipliers as $key => $measurement) {
			if(!is_a($measurement, __CLASS__) && !is_a($measurement, BaseMeasurement::class)) {
				throw new MeasurementException(sprintf(__('Array contents for multiplier [%s] in multiplyMeasurements() must be a Measurement or BaseMeasurement object'), $key));
			}
			if(!$measurement->get('dimension')) {
				throw new MeasurementException(sprintf(__('Array contents for[%s] in multiplyMeasurements() must be a Measurement or BaseMeasurement object with a dimension.'), $key));
			}
			$newBaseMagnitude = (is_a($measurement, __CLASS__)) ? $measurement->valueAsBase() : $measurement->get('baseMagnitude');
			if($measurement->get('baseMagnitude') && $newBaseMagnitude != $measurement->get('baseMagnitude')) {
				wire()->warning(sprintf(__('Updating base magnitude for multiplier %1$s from %2$s to %3$s.'), $key, $measurement->get('baseMagnitude'), $newBaseMagnitude));
			}
			if(is_a($measurement, __CLASS__)) $measurement->set('baseMagnitude', $newBaseMagnitude);
			$tempMagnitude *= $newBaseMagnitude;
			$tempDimension = $tempDimension->multiplyBy($measurement->dimension);
		}
		$result = self::inferResult($tempDimension, $quantity, $unit, $tempMagnitude);

		return $result;
	}

	/**
	 * Infer results from dimensional analysis, for multiplyMeasurements() and power().
	 *
	 * @param Dimension $dimension
	 * @param string|null $quantity
	 * @param string|null $unit
	 * @param int $baseMagnitude
	 * @return BaseMeasurement|Measurement
	 * @throws MeasurementDifferentTypeException
	 * @throws MeasurementInvalidUnitException
	 * @throws \ProcessWire\WireException
	 */
	private static function inferResult(Dimension $dimension, ?string $quantity = null, ?string $unit = null, $baseMagnitude = 0) {
		$result = new BaseMeasurement($baseMagnitude, $dimension);
		$compatibleQuantities = $dimension->getQuantities();
		if($quantity) {
			$result = new Measurement($quantity, $unit);
			if(!in_array($quantity, $compatibleQuantities)) {
				throw new MeasurementDifferentTypeException(sprintf(__('Result is not compatible with chosen quantity - %s'), $quantity));
			} else if($unit) {
				if(!in_array($unit, array_keys($result->getUnits()))) {
					throw new MeasurementInvalidUnitException(sprintf(__('Unit %1$s is not compatible with chosen quantity %2$s'), $unit, $quantity));
				} else {
					$result->set('unit', $unit);
					$result->convertFromBase($baseMagnitude);
				}
			} else {
				wire()->warning(sprintf(__('Units not specified - returning a %s in base units'), $quantity));
			}
		} else {
			if(count($compatibleQuantities) == 0) {
				wire()->warning(__("Result is an unknown quantity. Returning  a result of class 'BaseMeasurement' - i.e a magnitude and dimension only"));
			} else {
				$quantity = $compatibleQuantities[0];
				if(count($compatibleQuantities) > 1) {
					wire()->warning(sprintf(__('Result is an ambiguous quantity. Returning as the first-listed quantity - %s, in base units'), $quantity));
				}
				$result = new Measurement($quantity, null, $baseMagnitude);
				wire()->warning(sprintf(__('Quantity not specified - returning a %s in base units'), $quantity));
			}
		}
		return $result;
	}

}
