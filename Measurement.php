<?php namespace ProcessWire;

/*
 * This file is based on Measurement by Oliver Folkerd <oliver.folkerd@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once 'Exceptions/MeasurementDifferentTypeException.php';
require_once 'Exceptions/MeasurementException.php';
require_once 'Exceptions/MeasurementInvalidUnitException.php';
require_once 'Exceptions/FileNotFoundException.php';

require_once 'ConversionDefinition.php';
require_once 'ConversionRepository.php';

class BaseMeasurement extends WireData{

	public function __construct($baseMagnitude, MeasurementDimension $dimension) {
		$this->set('baseMagnitude', $baseMagnitude);
		$this->set('dimension', $dimension);
	}

	public function multiplyBy($multiplier) {
		if(is_object($multiplier) && (get_class($multiplier) == 'ProcessWire\BaseMeasurement' || get_class($multiplier) == 'ProcessWire\Measurement')) {
			if(get_class($multiplier) == 'ProcessWire\Measurement') {
				$factor = $multiplier->valueAsBase();
			} else {
				$factor = $multiplier->get('baseMagnitude');
			}
			$result = new BaseMeasurement($this->get('baseMagnitude') * $factor, $this->get('dimension')->multiplyBy($multiplier->get('dimension')));
		} else {
			$multiplier = (float) $multiplier;
			$result = new BaseMeasurement($this->get('baseMagnitude') * $multiplier, $this->get('dimension'));
		}
		return $result;
	}

	public function divideBy($divisor) {
		if(is_object($divisor) && (get_class($divisor) == 'ProcessWire\BaseMeasurement' || get_class($divisor) == 'ProcessWire\Measurement')) {
			if(get_class($divisor) == 'ProcessWire\Measurement') {
				$factor = $divisor->valueAsBase();
			} else {
				$factor = $divisor->get('baseMagnitude');
			}
			$result = new BaseMeasurement($this->get('baseMagnitude') / $factor, $this->get('dimension')->divideBy($divisor->get('dimension')));
		} else {
			$divisor = (float) $divisor;
			$result = new BaseMeasurement($this->get('baseMagnitude') / $divisor, $this->get('dimension'));
		}
		return $result;
	}

}

class Measurement extends BaseMeasurement
{
    /** @var ?float */
    private $value;

    /** @var ?string */
    public $quantity;

    /** @var ConversionRepository */
    private $units;

    public function __construct(?string $quantity = null, ?string $unit = null, $magnitude = null)
    {
		if(!is_array($magnitude)) $magnitude = explode('|', $magnitude);
		try {
			if($quantity) $this->loadUnits($quantity);
		} catch(FileNotFoundException $e) {
			$this->error($e->getMessage());
			return;
		}
		$this->set('unit', $unit);
		$this->set('magnitude', $magnitude);
		$this->set('quantity', $quantity);
		$quantity = $this->get('quantity');
		if($quantity) {
			$dimension = (self::getDimension($quantity)) ?: new MeasurementDimension(1,1); //if no dimension, then assume scalar
			$this->set('dimension', $dimension);
			//bd($magnitude, 'MAGNITUDE');
			if((is_array($magnitude) && count($magnitude) > 0 && $magnitude[0]) || (!is_array($magnitude) && $magnitude)) {
				$baseMagnitude = ($unit) ? $this->valueAsBase() : (($magnitude) ? $magnitude[0] : null);
			} else {
				$baseMagnitude = null;
			}
			parent::__construct($baseMagnitude, $dimension);
		}
		//bd($this, 'In Construct - 3 set');
		if($this->get('unit') and $this->get('quantity')) {
			$units = self::getUnits($this->get('quantity'));
			foreach($units[$unit] as $key => $item) {
				if($key == 'conversion') continue;
				$this->set($key, $item);
			}
		}
//        if($this->unit and !is_null($this->magnitude)) {
//            try {
//				$this->convertFrom($this->magnitude, $this->unit);
//			} catch(MeasurementInvalidUnitException|MeasurementException|MeasurementDifferentTypeException $e) {
//				$this->error($e->getMessage());
//			}
//        }
		//bd($this, 'constructed measurement object');
    }

    /**
     * Allow switching between different unit definition files. Defaults to src/Config/Units.php
     * @param ?string $path Load your own units file if you want.
     * @throws FileNotFoundException
     */
    private function loadUnits(string $path): void
    {
		$path = self::configFile($path);
    	//bd($path, 'loading');
    	$this->units = ConversionRepository::fromFile($path);
    }

    /**
     * Set from conversion value / unit
     *
     * @param  float  $value -  a numeric value to base conversions on
     * @param  string $unit (optional) - the unit symbol for the start value
     * @return Measurement
     * @throws MeasurementException - general errors
     * @throws MeasurementInvalidUnitException - specific invalid unit exception
	 * @throws MeasurementDifferentTypeException
     */
    public function convertFrom($value, ?string $unit = null): Measurement
    {
    	//bd($this, 'this in from');
        if (!$unit) {
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
		if(!$this->get('unit')) $this->set('unit', $this->baseUnit);
		$toUnit = $this->get('unit');
		$conversionTo = $this->units->getConversion($toUnit);
		if($this->baseUnit !== $conversionTo->getBaseUnit()) {
			throw new MeasurementDifferentTypeException($this->_("Cannot Convert Between Units of Different Types"));
		}
		$this->magnitude = $conversionTo->convertFromBase($this->magnitude);
		return $this;
    }

    public function convertFromBase($value) {
		try {
			$baseUnit = $this->units->getConversion($this->get('unit'))->getBaseUnit();
		} catch(MeasurementInvalidUnitException $e) {
			$this->error($e->getMessage());
			return $this;
		}
    	try {
			return $this->convertFrom($value, $baseUnit);
		} catch(MeasurementInvalidUnitException|MeasurementDifferentTypeException|MeasurementException $e) {
			$this->error($e->getMessage());
			return $this;
		}
	}

    public function convertTo(string $unit, ?int $decimals = null, bool $round = true) {
		try {
			$this->magnitude = $this->valueAs($unit, $decimals, $round);
		} catch(MeasurementException | MeasurementInvalidUnitException $e) {
			$this->error($e->getMessage());
		}
		//bd($this->magnitude, 'magnitude1');
		$this->unit = $unit;
		$units = self::getUnits($this->get('quantity'));
		// unset optional elements
		foreach($this->formatOptions() as $key => $option) {
			$this->remove($key);
		}
		// then set the ones in the config file
		foreach($units[$unit] as $key => $item) {
			if($key == 'conversion') continue;
			$this->set($key, $item);
		}
		//bd($this->magnitude, 'magnitude2');
		return $this;
	}

	public function convertToBase(?int $decimals = null, ?bool $round = true) {
		try {
			$baseUnit = $this->units->getConversion($this->get('unit'))->getBaseUnit();
			$this->convertTo($baseUnit, $decimals, $round);
		} catch(MeasurementInvalidUnitException $e) {
			$this->error($e->getMessage());
		}
		return $this;
	}

	public function add(Measurement $measurement, ?string  $unit = null) {
		return self::sumMeasurements([$this, $measurement], $unit);
	}

	public function sumOf(...$measurements) {
		$unit = $this->get('unit');
		$result = self::sumMeasurements($measurements, $unit);
		if($this->quantity != $result->quantity) {
			throw new MeasurementDifferentTypeException(sprintf($this->_('Measurements are not of the same quantity. Object is %1$s, measurements are %2$s.'), $this->quantity, $result->quantity));
		}
		$this->magnitude = $result->magnitude;
		$this->baseMagnitude = $result->baseMagnitude;
		return $this;
}

	/**
	 * @throws MeasurementDifferentTypeException
	 */
	public function multiplyBy($multiplier, ?string $quantity = null, ?string $unit = null) {
		if(!is_a($multiplier, 'ProcessWire\Measurement')) {
			$multiplier = (float) $multiplier;
			if($quantity && $quantity != $this->get('quantity')) {
				throw new MeasurementDifferentTypeException(sprintf($this->_('Incompatible quantities - argument of %1$s but object is %2$s'), $quantity, $this->get('quantity')));
			}
			if (!$quantity) $quantity = $this->get('quantity');
			$baseMagnitude = $this->valueAsBase();
			$this->set('baseMagnitude', $baseMagnitude);
			$baseMagnitude	*= $multiplier;
			$baseUnit = self::getBaseUnit($quantity);
			$result = new Measurement($quantity, $baseUnit, $baseMagnitude);
			if(!$unit) $unit = $this->get('unit');
			return $result->convertTo($unit);
		}
		return self::combineMeasurements([$this, $multiplier], [], $quantity, $unit);
	}

	public function negate() {
		return $this->multiplyBy(-1);
	}

	public function power(int $exp, ?string $quantity = null, ?string $unit = null) {
		if($exp > 1) {
			$result = $this;
			for($i = 1; $i <= $exp - 1 ; $i++) {
				if($i == $exp - 1) {
					$result = $result->multiplyBy($this, $quantity, $unit); // apply quantity and unit on final step only
				} else {
					$result = $result->multiplyBy($this);
				}
			}
		} else if($exp < -1) {
			$result = $this->invert();
			for($i = -1; $i >= $exp + 1; $i--) {
				if($i == $exp + 1) {
					$result = $result->divideBy($this, $quantity, $unit);
				} else {
					$result = $result->divideBy($this);
				}
			}
		} else {
			$result = new BaseMeasurement(1, new MeasurementDimension(1,1));
		}
		return $result;
	}

	public function productOf(...$measurements) {
		$quantity = $this->get('quantity');
		$unit = $this->get('unit');
		$result = self::combineMeasurements($measurements, [], $quantity, $unit);
		$this->magnitude = $result->magnitude;
		$this->baseMagnitude = $result->baseMagnitude;
		return $this;
	}

	public function subtract(Measurement $measurement, ?string $unit = null) {
		return $this->add($measurement->negate(), $unit);
	}

	public function divideBy($divisor, ?string $quantity = null, ?string $unit = null) {
		if(!is_a($divisor, 'ProcessWire\Measurement')) {
			$divisor = (float) $divisor;
			if($quantity && $quantity != $this->get('quantity')) {
				throw new MeasurementDifferentTypeException(sprintf($this->_('Incompatible quantities - argument of %1$s but object is %2$s'), $quantity, $this->get('quantity')));
			}
			if (!$quantity) $quantity = $this->get('quantity');
			$baseMagnitude = $this->valueAsBase();
			$this->set('baseMagnitude', $baseMagnitude);
			if($divisor != 0) {
				$baseMagnitude /= $divisor;
				$baseUnit = self::getBaseUnit($quantity);
				$result = new Measurement($quantity, $baseUnit, $baseMagnitude);
				if(!$unit) $unit = $this->get('unit');
				return $result->convertTo($unit);
			} else {
				throw new MeasurementException($this->_("Divisor argument in divideBy method is zero"));
			}
		}
		return self::combineMeasurements([$this], [$divisor], $quantity, $unit);
	}

	public function invert(?string $quantity = null, ?string $unit = null) {
		return self::combineMeasurements([], [$this], $quantity, $unit);
	}


	/**
	 * Convert from value to new unit value
	 *
	 * @param string $unit -  the unit symbol (or array of symbols) for the conversion unit
	 * @param    ?int $decimals (optional, default-null) - the decimal precision of the conversion result
	 * @param boolean $round (optional, default-true) - round or floor the conversion result
	 * @return   float|array
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

    public function valueAsBase(?int $decimals = null, bool $round = true) {
		$baseUnit = self::getBaseUnit($this->get('quantity'));
		return $this->valueAs($baseUnit, $decimals, $round);
	}

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
     * @param string[] $units
     * @param ?int     $decimals
     * @param bool     $round
     * @return array
     */
    public function valueAsMany($units = [], ?int $decimals = null, $round = true)
    {
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
     * Convert from value to all compatible units.
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

//ToDo - check that it is OK to delete the following
	/**
	 * Convert from value to all selectable units.
	 *
//	 * @param int|null $decimals
//	 * @param bool     $round
//	 * @return array
//	 * @throws MeasurementException
	 */
//	public function valueAsSelectable(?int $decimals = null, bool $round = true)
//	{
//		try {
//			$conversionFrom = $this->units->getConversion($this->get('unit'));
//			if(!$this->baseUnit) {
//				$this->baseUnit = $conversionFrom->getBaseUnit();
//			}
//			if(is_null($this->magnitude)) {
//				throw new MeasurementException($this->_("From Value Not Set"));
//			}
//
//			if(is_null($this->baseUnit)) {
//				throw new MeasurementException($this->_("No From Unit Set"));
//			}
//			$units = array_combine($this->get('units'), $this->get('units'));
//			return $this->valueAsMany($units, $decimals, $round);
//		} catch(MeasurementInvalidUnitException | MeasurementException $e) {
//			$this->error($e->getMessage());
//			return [];
//		}
//	}

	/**
	 * @param string $unit
	 * @param string $base
	 * @param float|Callable $conversion
	 * @return bool
	 * @throws MeasurementException
	 * @throws MeasurementInvalidUnitException
	 */
    public function addUnit(string $unit, array $params, ?string $selectableIn = null)
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
			self::addSelectableUnit($selectableIn, $unit);
			//ToDo - check that it is OK to delete the following
//			$units = ($this->get('units')) ?: [];
//			if(!in_array($unit, $units)) {
//				$units[] = $unit;
//				$this->set('units', $units);
//			}
		}

		$this->session->set($quantity, $definition);

		$baseUnit = self::getBaseUnit($this->get('quantity'));
		$conversion = new ConversionDefinition($unit, $baseUnit, $conversion);
		//bd($this->units);
		// replace any existing conversion
		//bd(['unit' => $unit, 'available' => $this->units->getAvailableConversions($unit)], 'Exists?');
		if(in_array($unit, $this->units->getAvailableConversions($unit))) {
			//bd('REMOVING');
			$this->units->removeConversion($unit);
		}
		$this->units->addConversion($conversion);
		return true;

	}

	/**
	 * @throws MeasurementException
	 * @throws MeasurementInvalidUnitException
	 */
	public function amendUnit(string $unit, $conversion, array $options = []) {
		$quantity = $this->get('quantity');
		$params = array_merge(['conversion' => $conversion], $options);

		$definitions = self::getUnits($quantity);

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
     * @param string $unit
     * @return bool
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
	 *
	 * @param string $unit
	 * @return string[]
	 * @throws MeasurementInvalidUnitException
	 */
    public function getConversions(?string $unit = null): array
    {
		if(!$unit) $unit = $this->get('unit');
		if(!$unit) return [];
        return $this->units->getAvailableConversions($unit);
    }

    private function round(float $value, int $decimals, bool $round): float
    {
        $mode = $round ? PHP_ROUND_HALF_UP : PHP_ROUND_HALF_DOWN;
        return round($value, $decimals, $mode);
    }

	public function render(?array $options = []) {
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

	public function format($options = []) {
		$options = $this->formatOptions($options);
		$this->format = $options;
	}

	protected function formatOptions($options = []) {
		$units = self::getUnits($this->get('quantity'));
		$unit = $this->get('unit');

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
		foreach($units[$unit] as $key => $item) {
			if(!in_array($key, $defaultOptions)) continue;
			$defaultOptions[$key] = $item;
		}

		// Over-ride by supplied options
		foreach($options as $key => $option) {
			$defaultOptions[$key] = $option;
		}
		//bd($defaultOptions, 'defaultoptions');
		return $defaultOptions;
	}


	/**
	 * @throws WireException
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

	public static function getQuantityNotes($quantity) {
		//bd(debug_backtrace(), 'getUnits' . $quantity);
		$unitsFile = self::configFile($quantity);
		if(!file_exists($unitsFile)) return null;
		$conversions = include $unitsFile;
		$notes = (isset($conversions['notes'])) ? $conversions['notes'] : null;
		return $notes;
	}

	public static function getDimension($quantity) {
		$unitsFile = self::configFile($quantity);
		if(!file_exists($unitsFile)) return null;
		$conversions = include $unitsFile;
		$dim = (isset($conversions['dimension'])) ? $conversions['dimension'] : null;
		return $dim;
	}

	public static function getUnits($quantity) {
		//bd(debug_backtrace(), 'getUnits' . $quantity);
		$unitsFile = self::configFile($quantity);
		if(!file_exists($unitsFile)) return null;
		$conversions = include $unitsFile;
		$units = (wire()->session->get($quantity)) ?: [];
		$units = array_merge($conversions['units'], $units);
		return $units;
	}

	public static function getBaseUnit($quantity) {
		//bd(debug_backtrace(), 'getUnits' . $quantity);
		$unitsFile = self::configFile($quantity);
		if(!file_exists($unitsFile)) return;
		$conversions = include $unitsFile;
		$base = $conversions['base'];
		return $base;
	}

	public static function configFile($quantity) {
		$configFile = __DIR__ . '/Config/' . $quantity . '.php';
		$userFile = wire()->config->paths->templates . 'Measurement/' . $quantity . '.php';
		if(file_exists($userFile)) {
			$unitsFile = $userFile;
		} else {
			$unitsFile = $configFile;
		}
		return $unitsFile;
	}

	public static function sumMeasurements(array $measurements, ?string $unit = null) {
		$baseMagnitude = 0;
		$mixedUnits = false;
		$quantity = null;
		$suppliedUnit = $unit;
		foreach($measurements as $key => $measurement) {
			if(!is_a($measurement, 'ProcessWire\Measurement')) {
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
		$baseUnit = self::getBaseUnit($quantity);

		$result = new Measurement($quantity, $baseUnit, $baseMagnitude);
		if($suppliedUnit || !$mixedUnits) {
			$result->convertTo($unit);
		} else {
			wire()->warning(__("Result of measurement addition is given in base units as inconsistent measurement units supplied and no unit argument was given"));
		}
		return $result;
	}

	public static function combineMeasurements(array $numerators, array $denominators, ?string $quantity = null, ?string $unit = null) {
		$tempDimension = new MeasurementDimension(1, 1);
		$tempMagnitude = 1;
		$allMeasurements = array_merge($numerators, $denominators); // numeric keys so just appends denominators
		foreach($allMeasurements as $key => $measurement) {
			$type = (in_array($measurement, $numerators)) ? 'numerator' : 'denominator';
			if(!is_a($measurement, 'ProcessWire\Measurement') && !is_a($measurement, 'ProcessWire\BaseMeasurement') ) {
				throw new MeasurementException(sprintf(__('Array contents for %1$s[%2$s] in combineMeasurements() must be a Measurement or BaseMeasurement object'), $type, $key));
			}
			if(!$measurement->get('dimension')) {
				throw new MeasurementException(sprintf(__('Array contents for %1$s[%2$s] in combineMeasurements() must be a Measurement or BaseMeasurement object with a dimension.'), $type, $key));
			}
			$newBaseMagnitude = (is_a($measurement, 'ProcessWire\Measurement')) ? $measurement->valueAsBase() : $measurement->get('baseMagnitude');
			if($measurement->get('baseMagnitude') && $newBaseMagnitude != $measurement->get('baseMagnitude')) {
				wire()->warning(sprintf(__('Updating base magnitude for %1$s %2$s from %3$s to %4$s.'), $type, $key, $measurement->get('baseMagnitude'), $newBaseMagnitude));
			}
			if(is_a($measurement, 'ProcessWire\Measurement')) $measurement->set('baseMagnitude', $newBaseMagnitude);
			if($type == 'numerator') {
				$tempMagnitude *= $newBaseMagnitude;
				$tempDimension = $tempDimension->multiplyBy($measurement->get('dimension'));
			} else {  // denominator
				$tempMagnitude /= $newBaseMagnitude;
				$tempDimension = $tempDimension->divideBy($measurement->get('dimension'));
			}
		}
		$result = new BaseMeasurement($tempMagnitude, $tempDimension);
		$compatibleQuantities = $tempDimension->getQuantities();
		if($quantity) {
			if(!in_array($quantity, $compatibleQuantities)) {
				throw new MeasurementDifferentTypeException(sprintf(__('Result is not compatible with chosen quantity - %s'), $quantity));
			} else if($unit) {
				if(!in_array($unit, array_keys(self::getUnits($quantity)))) {
					throw new MeasurementInvalidUnitException(sprintf(__('Unit %1$s is not compatible with chosen quantity %2$s'), $unit, $quantity));
				} else {
					$result = new Measurement($quantity, $unit);
					$result->convertFromBase($tempMagnitude);
				}
			}
		} else {
			if(count($compatibleQuantities) == 0) {
				wire()->warning(__("Result is an unknown quantity. Returning  a result of class 'BaseMeasurement' - i.e a magnitude and dimension only"));
			} else {
				$quantity = $compatibleQuantities[0];
				if(count($compatibleQuantities) > 1) {
					wire()->warning(sprintf(__('Result is an ambiguous quantity. Returning as the first-listed quantity - %s, in base units'), $quantity));
				}
				$unit = self::getBaseUnit($quantity);
				$result = new Measurement($quantity, $unit, $tempMagnitude);
				wire()->warning(sprintf(__('Quantity not specified - returning a %s in base units'), $quantity));
			}
		}
		return $result;
	}

	public static function addSelectableUnit($field, $unit) {
		$f = wire()->fields->get($field);
		$units = $f->get('units');
		if(!in_array($unit, $units)) $units[] = $unit;
		$f->set('units', $units);
		$f->save();
	}

}
