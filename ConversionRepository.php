<?php namespace ProcessWire;

use InvalidArgumentException;
use ProcessWire\MeasurementException;
use ProcessWire\MeasurementInvalidUnitException;
use ProcessWire\FileNotFoundException;

class ConversionRepository extends WireData {


	/** @var array<string, ConversionDefinition> */
	private $definitions = [];

	private function __construct(string $quantity, ConversionDefinition ...$definitions) {
		$this->quantity = $quantity;
		foreach($definitions as $definition) {
//        	$definition['base'] = $base;
			$this->definitions[$definition->getUnit()] = $definition;
		}
	}

	/**
	 * @throws \ProcessWire\MeasurementException
	 * @throws \ProcessWire\FileNotFoundException
	 */
	public static function fromFile(string $path): ConversionRepository {
		if(!file_exists($path)) {
			throw new FileNotFoundException(sprintf(__('File could not be found. Given path=%s. 
            Either use the name of one of the pre defined configuration files or pass the complete path to the file.'), $path));
		}
		$quantity = basename($path, '.php');

		$data = include $path;

		if(!is_array($data)) {
			throw new InvalidArgumentException(__("The Unit definition must be an array."));
		}
		$base = $data['base'];
		$units = (wire()->session->get($quantity)) ?: [];
		$units = array_merge($data['units'], $units);
		$list = array_map(function($key, $definition) use ($base) {
			if(!isset($definition['shortLabel']) || !isset($definition['conversion'])) {
				throw new InvalidArgumentException(__("A conversion definition must have a shortLabel and conversion property."));
			}
			return new ConversionDefinition($key, $base, $definition['conversion']);
		}, array_keys($units), $units);
		//bd($list, 'list');


		return new ConversionRepository($quantity, ...$list);
	}

	/**
	 * @throws \ProcessWire\MeasurementException
	 * @throws \ProcessWire\MeasurementInvalidUnitException
	 */
	public function addConversion(ConversionDefinition $definition): void {
		if($this->unitExists($definition->getUnit())) {
			throw new MeasurementInvalidUnitException(sprintf($this->_('Unit %s is already defined.'), $definition->getUnit()));
		}

		if(!$this->unitExists($definition->getBaseUnit()) && $definition->isBaseUnit()) {
			throw new MeasurementException($this->_("Base Unit Does Not Exist"));
		}

		$this->definitions[$definition->getUnit()] = $definition;
	}

	public function unitExists(string $unit): bool {
		return array_key_exists($unit, $this->definitions);
	}

	/**
	 * @throws \ProcessWire\MeasurementInvalidUnitException
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
	 * @throws \ProcessWire\MeasurementInvalidUnitException
	 */
	public function getConversion(string $unit): ConversionDefinition {
		if(!$this->unitExists($unit)) {
			throw new MeasurementInvalidUnitException(sprintf(__('Unit %1$s is not defined for quantity %2$s.'), $unit, $this->quantity));
		}

		return $this->definitions[$unit];
	}

	/**
	 * @throws \ProcessWire\MeasurementInvalidUnitException
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