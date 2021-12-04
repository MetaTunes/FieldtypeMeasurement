<?php namespace MetaTunes\MeasurementClasses;
use ProcessWire\WireData;
use function ProcessWire\{wire, __};

use PHPMailer\PHPMailer\Exception;


/**
 * #pw-summary This class stores conversion definitions for a unit
 *
 * #pw-body =
 * It reflects the contents of the config file 'unit' values.
 *
 * It has properties for the unit, baseUnit and conversion and data items for the other elements in the unit array.
 *
 * #pw-body
 */
class ConversionDefinition extends WireData
{
    /** @var string */
    public $unit;

    /** @var string */
    public $baseUnit;

    /** @var float|Callable */
    public $conversion;

	/**
	 * @param string $unit
	 * @param string $baseUnit
	 * @param $definition
	 * @throws MeasurementException
	 */
    public function __construct(string $unit, string $baseUnit, $definition)
    {
        $this->unit = $unit;
        $this->baseUnit = $baseUnit;
        $this->conversion = $definition['conversion'];
			foreach($definition as $key => $item) {
				if(in_array($key, ['conversion', 'unit', 'conversion'])) continue;
				$this->set($key, $item);
			}
        if(!is_numeric($this->conversion) && !is_callable($this->conversion)) {
            throw new MeasurementException($this->_("A conversion must be either numeric or a callable."));
        }
    }

	/**
	 * @return string
	 */
    public function getUnit(): string
    {
        return $this->unit;
    }

	/**
	 * @return string
	 */
    public function getBaseUnit(): string
    {
        return $this->baseUnit;
    }

	/**
	 * @return bool
	 */
    public function isBaseUnit(): bool
    {
        return $this->unit === $this->baseUnit;
    }

	/**
	 * Return the base unit magnitude
	 *
	 * @param $value
	 * @return float
	 * @throws MeasurementException
	 */
	public function convertToBase($value): float  // $value can be an array for combi units
    {
//		$value = (float) $value;
    	//bd([$this->conversion, $value]);
//		bd(debug_backtrace());
        if (is_numeric($this->conversion)) {
            return $value * $this->conversion;
        } elseif (is_callable($this->conversion)) {
			try {
				$converter = $this->conversion;
			}
			catch(\Exception $e) {
				throw new MeasurementException($this->_("The conversion callback function cannot be executed. Perhaps an input error?"));
			}
            return $converter($value, false);
        }
        throw new MeasurementException($this->_("The conversion must be either numeric or callable."));
    }

	/**
	 * Return the magnitude in the current unit from the base magnitude
	 *
	 * @param float $value
	 * @return float|int
	 * @throws MeasurementException
	 */
	public function convertFromBase(float $value)
    {
        if (is_numeric($this->conversion)) {
            return $value / $this->conversion;
        } elseif (is_callable($this->conversion)) {
        	try {
				$converter = $this->conversion;
			}
			catch(\Exception $e) {
				throw new MeasurementException($this->_("The conversion callback function cannot be executed. Perhaps an input error?"));
			}
            return $converter($value, true); // $value can be an array for combi units
        }
        throw new MeasurementException($this->_("The conversion must be either numeric or callable."));
    }
}