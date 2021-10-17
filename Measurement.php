<?php namespace ProcessWire;

/*
 * This file is based on Measurement by Oliver Folkerd <oliver.folkerd@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once 'Exceptions/ConvertorDifferentTypeException.php';
require_once 'Exceptions/ConvertorException.php';
require_once 'Exceptions/ConvertorInvalidUnitException.php';
require_once 'Exceptions/FileNotFoundException.php';

require_once 'ConversionDefinition.php';
require_once 'ConversionRepository.php';

class Measurement extends WireData
{
    /** @var ?float */
    private $value;

    /** @var ?string */
    public $quantity;

    /** @var ConversionRepository */
    private $units;

    public function __construct(?string $quantity = null, ?string $unit = null, $magnitude = null)
    {
//    	d(debug_backtrace());
//		d($this);
		if(!is_array($magnitude)) $magnitude = explode('|', $magnitude);
        if($quantity) $this->loadUnits($quantity);
		$this->set('unit', $unit);
		$this->set('magnitude', $magnitude);
		$this->set('quantity', $quantity);
		bd($this, 'In Construct - 3 set');
		if($this->get('unit') and $this->get('quantity')) {
			bd($this, 'In Construct try shortLabel');
			$units = FieldtypeMeasurement::getUnits($this->quantity);
			if(isset($units['shortLabel'])) $this->set('shortLabel', $units['shortLabel']);
		} else {
			$this->set('shortLabel', '');
		}
        if($this->unit and !is_null($this->magnitude)) {
            $this->convertFrom($this->magnitude, $this->unit);
        }
    }

    /**
     * Allow switching between different unit definition files. Defaults to src/Config/Units.php
     * @param ?string $path Load your own units file if you want.
     * @throws FileNotFoundException
     */
    private function loadUnits(string $path): void
    {
		$path = FieldtypeMeasurement::configFile($path);
    	bd($path, 'loading');
    	$this->units = ConversionRepository::fromFile($path);
    }

    /**
     * Set from conversion value / unit
     *
     * @param  float  $value -  a numeric value to base conversions on
     * @param  string $unit (optional) - the unit symbol for the start value
     * @return Measurement
     * @throws ConvertorException - general errors
     * @throws ConvertorInvalidUnitException - specific invalid unit exception
     */
    public function convertFrom($value, ?string $unit = null): Measurement
    {
    	bd($this, 'this in from');
        if (! $unit) {
            $this->magnitude = $value;
            return $this;
        }

        //Convert single-valued arrays to float (only want combi units to be arrays for conversion)
        if(is_array($value) and count($value) == 1) $value = $value[0];

        if (! $this->units->unitExists($unit)) {
            throw new ConvertorInvalidUnitException("Conversion from Unit u=$unit not possible - unit does not exist.");
        }

		$conversionFrom = $this->units->getConversion($unit);
		$this->baseUnit = $conversionFrom->getBaseUnit();
		$this->magnitude = $conversionFrom->convertToBase($value);

		if(!$this->get('unit')) $this->set('unit', $this->baseUnit);
		$toUnit = $this->get('unit');

		$conversionTo = $this->units->getConversion($toUnit);
		if($this->baseUnit !== $conversionTo->getBaseUnit()) {
			throw new ConvertorDifferentTypeException("Cannot Convert Between Units of Different Types");
		}
		$this->magnitude = $conversionTo->convertFromBase($this->magnitude);


		return $this;
    }


    public function convertTo(string $unit, ?int $decimals = null, bool $round = true) {
		$this->magnitude = $this->valueAs($unit, $decimals, $round);
		$this->unit = $unit;
		$units = FieldtypeMeasurement::getUnits($this->get('quantity'));
		if(isset($units[$unit]['shortLabel'])) {
			$this->set('shortLabel', $units[$unit]['shortLabel']);
		}
		if(isset($units[$unit]['plural'])) {
			$this->set('plural', $units[$unit]['plural']);
		}
		return $this;
	}

	public function add(Measurement $measurement2, ?string $unit = null) {
		$operands = $this->operands($measurement2, $unit);
		$sum = $operands['value1'] + $operands['value2'];
		$sumUnit = ($unit) ?: $this->get('unit');
		$sumObject = new Measurement($this->get('quantity'), $operands['base'], $sum);
		return $sumObject->convertTo($sumUnit);
	}

	public function subtract(Measurement $measurement2, ?string $unit = null) {
		$operands = $this->operands($measurement2, $unit);
		bd($operands, 'operands');
		$diff = $operands['value1'] - $operands['value2'];
		$sumUnit = ($unit) ?: $this->get('unit');
		$sumObject = new Measurement($this->get('quantity'), $operands['base'], $diff);
		return $sumObject->convertTo($sumUnit);
	}

	protected function operands(Measurement $measurement2, ?string $unit) {
		$conversion1 = $this->units->getConversion($this->get('unit'));
		$conversion2 = $this->units->getConversion($measurement2->get('unit'));
		$conversion3 = (isset($unit) && $unit) ? $this->units->getConversion($unit) : null;
		$baseUnit1 = $conversion1->getBaseUnit();
		$baseUnit2 = $conversion2->getBaseUnit();
		$baseUnit3 = ($conversion3) ? $conversion3->getBaseUnit() : null;
		// Fail if base units are different
		if(($baseUnit1 != $baseUnit2) || ($baseUnit3 && $baseUnit3 != $baseUnit2)) throw new ConvertorException("Base units are different");
		$baseVal1 = $this->valueAs($baseUnit1);
		$baseVal2 = $measurement2->valueAs($baseUnit1);
		return ['base' => $baseUnit1, 'value1' => $baseVal1, 'value2' => $baseVal2];
	}

	/**
	 * Convert from value to new unit value
	 *
	 * @param string $unit -  the unit symbol (or array of symbols) for the conversion unit
	 * @param    ?int $decimals (optional, default-null) - the decimal precision of the conversion result
	 * @param boolean $round (optional, default-true) - round or floor the conversion result
	 * @return   float|array
	 * @throws \ProcessWire\ConvertorException
	 */
    public function valueAs(string $unit, ?int $decimals = null, bool $round = true)
    {
    	bd($this, 'this valueAs');
    	bd($unit, 'unit in valueAs');
        if (is_null($this->magnitude)) {
            throw new ConvertorException("From Value Not Set.");
        }

        if (is_array($unit)) {
            return $this->valueAsMany($unit, $decimals, $round);
        }

        $magnitude = $this->get('magnitude');
        if(is_array($magnitude) && (count($magnitude) == 1)) $this->set('magnitude', $magnitude[0]);

        if (! $this->units->unitExists($unit)) {
            throw new ConvertorInvalidUnitException("Conversion from Unit u=$unit not possible - unit does not exist.");
        }

        $conversionFrom = $this->units->getConversion($this->get('unit'));
        $conversionTo = $this->units->getConversion($unit);
        bd($conversionTo, 'conversion');

        if (! $this->baseUnit) {
            $this->baseUnit = $conversionFrom->getBaseUnit();
        }

        if ($conversionTo->getBaseUnit() !== $this->baseUnit) {
            throw new ConvertorDifferentTypeException("Cannot Convert Between Units of Different Types");
        }

        $baseMeasurement = $conversionFrom->convertToBase($this->magnitude);
        $result = $conversionTo->convertFromBase($baseMeasurement);

        if (! is_null($decimals)) {
            return $this->round($result, $decimals, $round);
        }
bd($result, 'result');
        return $result;
    }

    /**
     * @param string[] $units
     * @param ?int     $decimals
     * @param bool     $round
     * @return array
     */
    private function valueAsMany($units = [], ?int $decimals = null, $round = true)
    {
        return array_map(function ($unit) use ($decimals, $round) {
            return $this->valueAs($unit, $decimals, $round);
        }, $units);
    }

    /**
     * Convert from value to all compatible units.
     * @param int|null $decimals
     * @param bool     $round
     * @return array
     * @throws ConvertorException
     */
    public function valueAsAll(?int $decimals = null, bool $round = true)
    {
		$conversionFrom = $this->units->getConversion($this->get('unit'));
		if (! $this->baseUnit) {
			$this->baseUnit = $conversionFrom->getBaseUnit();
		}
        if (is_null($this->magnitude)) {
            throw new ConvertorException("From Value Not Set");
        }

        if (is_null($this->baseUnit)) {
            throw new ConvertorException("No From Unit Set");
        }

        return $this->valueAsMany($this->getUnits($this->baseUnit), $decimals, $round);
    }

	/**
	 * Convert from value to all selectable units.
	 * @param int|null $decimals
	 * @param bool     $round
	 * @return array
	 * @throws ConvertorException
	 */
	public function valueAsSelectable(?int $decimals = null, bool $round = true)
	{
		$conversionFrom = $this->units->getConversion($this->get('unit'));
		if (! $this->baseUnit) {
			$this->baseUnit = $conversionFrom->getBaseUnit();
		}
		if (is_null($this->magnitude)) {
			throw new ConvertorException("From Value Not Set");
		}

		if(is_null($this->baseUnit)) {
			throw new ConvertorException("No From Unit Set");
		}
		$units = array_combine($this->get('units'), $this->get('units'));
		return $this->valueAsMany($units, $decimals, $round);
	}

	/**
     * @param string         $unit
     * @param string         $base
     * @param float|Callable $conversion
     * @return bool
     */
    public function addUnit(string $unit, string $base, string $shortLabel, $conversion)
    {
        $conversion = new ConversionDefinition($unit, $base, $shortLabel, $conversion);
        $this->units->addConversion($conversion);
        return true;
    }

    /**
     * @param string $unit
     * @return bool
     */
    public function removeUnit(string $unit): bool
    {
        $this->units->removeConversion($unit);
        return true;
    }

    /**
     * List all available conversion units for given unit.
     * @param string $unit
     * @return string[]
     */
    public function getUnits(?string $unit = null): array
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
		bd($options, 'options in render');
		$magnitudes = (is_array($this->magnitude)) ? $this->magnitude : [$this->magnitude];
		$units = explode('|', $this->unit);
		$labels = explode('|', $this->shortLabel);
		$plurals = explode('|', $this->plural);

		$out = '';
		$count = count($magnitudes);
		foreach($magnitudes as $index => $magnitude) {
			$unit = (isset($units[$index])) ? $units[$index] : '';
			$shortLabel = (isset($labels[$index])) ? $labels[$index] : '';
			$plural = (isset($plurals[$index])) ? $plurals[$index] : '';
			$join = (isset($options['join'][$index])) ? $options['join'][$index] : '';
			$usePlural = ($magnitude != 1);
			switch($options['label']) {
				case 'long' :
					if($usePlural) {
						$label = ($plural) ? ' ' . $plural : ' ' . $unit . 's';
					} else {
						$label = ' ' . $unit;
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
		$defaultOptions = [
			'label' => 'short', // 'short', 'shortPadded' (with space to separate from magnitude), 'long', 'none'
			'position' => 'append', // 'append' - after the magnitude, 'prepend' - before the magnitude (only applies to shortLabels.
			'decimals' => 2,
			'round' => true, // otherwise value will be truncated
			'join' => [' '], // an array for joining characters for combi units (one less element than the number of units in the combi) - e.g. [' and ']
			'skipNil' => true
		];
		foreach($options as $key => $option) {
			$defaultOptions[$key] = $option;
		}
		bd($defaultOptions, 'defaultoptions');
		return $defaultOptions;
	}
}
