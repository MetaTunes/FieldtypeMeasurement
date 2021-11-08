<?php namespace ProcessWire;

use InvalidArgumentException;
use PHPMailer\PHPMailer\Exception;
use ProcessWire\MeasurementException;

class ConversionDefinition extends WireData
{
    /** @var string */
    private $unit;

    /** @var string */
    private $baseUnit;

    /** @var float|Callable */
    private $conversion;

	/**
	 * @param string $unit
	 * @param string $baseUnit
	 * @param float|Callable $conversion
	 * @throws \ProcessWire\MeasurementException
	 */
    public function __construct(string $unit, string $baseUnit, $conversion)
    {
        $this->unit = $unit;
        $this->baseUnit = $baseUnit;
        $this->conversion = $conversion;

        if (! is_numeric($conversion) && ! is_callable($conversion)) {
            throw new MeasurementException($this->_("A conversion must be either numeric or a callable."));
        }
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getBaseUnit(): string
    {
        return $this->baseUnit;
    }

    public function isBaseUnit(): bool
    {
        return $this->unit === $this->baseUnit;
    }

	/**
	 * @throws \ProcessWire\MeasurementException
	 */
	public function convertToBase($value): float  // $value can be an array for combi units
    {
//		$value = (float) $value;
    	//bd([$this->conversion, $value]);
		//bd(debug_backtrace());
        if (is_numeric($this->conversion)) {
            return $value * $this->conversion;
        } elseif (is_callable($this->conversion)) {
			try {
				$converter = $this->conversion;
			}
			catch(Exception $e) {
				throw new MeasurementException($this->_("The conversion callback function cannot be executed. Perhaps an input error?"));
			}
            return $converter($value, false);
        }
        throw new MeasurementException($this->_("The conversion must be either numeric or callable."));
    }

	/**
	 * @throws \ProcessWire\MeasurementException
	 */
	public function convertFromBase(float $value)
    {
        if (is_numeric($this->conversion)) {
            return $value / $this->conversion;
        } elseif (is_callable($this->conversion)) {
        	try {
				$converter = $this->conversion;
			}
			catch(Exception $e) {
				throw new MeasurementException($this->_("The conversion callback function cannot be executed. Perhaps an input error?"));
			}
            return $converter($value, true); // $value can be an array for combi units
        }
        throw new MeasurementException($this->_("The conversion must be either numeric or callable."));
    }
}