<?php namespace MetaTunes\MeasurementClasses;
use ProcessWire\WireData;
use function ProcessWire\{wire, __};


/**
 * #pw-summary This class stores conversion definitions in the units property of `Measurement objects
 *
 * #pw-body =
 * A repository essentially is an object which reflects the contents of the config file associated with the quantity including its unit definitions.
 * It has data items
 * * quantity - the quantity being measured (one per config file)
 * * dimension - a Dimension object
 * * notes - notes about this quantity
 * * base - the base unit for this quantity
 *
 * and a property 'definitions' which is the representation of the 'units' array in the config file, where the value of each 'unit' is a ConversionDefinition object
 * #pw-body
 */
class ConversionRepository extends WireData {

	/** @var array<string, ConversionDefinition> */
	public $definitions = [];

	/**
	 * @param string $quantity
	 * @param $dimension
	 * @param string $notes
	 * @param string $base
	 * @param ConversionDefinition ...$definitions
	 */
	public function __construct(string $quantity, $dimension, string $notes, string $base, ConversionDefinition ...$definitions) {
		$this->quantity = $quantity;
		$this->dimension = ($dimension) ?: new Dimension();
		$this->notes = $notes;
		$this->base = $base;
		foreach($definitions as $definition) {
//        	$definition['base'] = $base;
			$this->definitions[$definition->getUnit()] = $definition;
		}
	}

	/**
	 * Add a conversion definition to the repository
	 *
	 * @param ConversionDefinition $definition
	 * @throws MeasurementException
	 * @throws MeasurementInvalidUnitException
	 */
	public function addConversion(ConversionDefinition $definition): void {
		if($definition->getUnit()) {
			if($this->unitExists($definition->getUnit())) {
				throw new MeasurementInvalidUnitException(sprintf($this->_('Unit %s is already defined.'), $definition->getUnit()));
			}
			if(!$this->unitExists($definition->getBaseUnit()) && $definition->isBaseUnit()) {
				throw new MeasurementException($this->_("Base Unit Does Not Exist"));
			}
			$this->definitions[$definition->getUnit()] = $definition;
//		bd($this->definitions);
		}
	}

	/**
	 * Does this unit exist in the repository?
	 *
	 * @param string $unit
	 * @return bool
	 */
	public function unitExists(string $unit): bool {
		return array_key_exists($unit, $this->definitions);
	}

	/**
	 * Remove a conversion definition from the repository
	 *
	 * @param string $unit
	 * @throws MeasurementInvalidUnitException
	 */
	public function removeConversion(string $unit): void {
		$conversion = $this->getConversion($unit);

		if(!$conversion->isBaseUnit()) {
			unset($this->definitions[$unit]);
			return;
		}
		// Unit is a base-unit. Remove all related units first.
		foreach($this->getAvailableConversions($unit) as $relatedUnit) {
			if($unit === $relatedUnit) {
				continue;
			}
			$this->removeConversion($relatedUnit);
		}
		unset($this->definitions[$unit]);
	}

	/**
	 * Get the conversion definition for this unit
	 *
	 * @param string $unit
	 * @return ConversionDefinition
	 * @throws MeasurementInvalidUnitException
	 */
	public function getConversion($unit): ConversionDefinition {
		if(!$this->unitExists($unit)) {
			throw new MeasurementInvalidUnitException(sprintf(__('Unit %1$s is not defined for quantity %2$s.'), $unit, $this->quantity));
		}
		return $this->definitions[$unit];
	}

	/**
	 * Get the compatible conversions for this unit
	 *
	 * @param string $unit
	 * @return array
	 * @throws MeasurementInvalidUnitException
	 */
	public function getAvailableConversions(string $unit): array {
		$conversion = $this->getConversion($unit);
		$unitConversions = array_filter($this->definitions, function(ConversionDefinition $definition) use ($conversion) {
			return $conversion->getBaseUnit() === $definition->getBaseUnit();
		});
		return array_map(function(ConversionDefinition $definition) {
			return $definition->getUnit();
		}, $unitConversions);
	}
}