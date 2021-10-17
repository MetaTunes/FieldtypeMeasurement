<?php namespace ProcessWire;

use InvalidArgumentException;
use ProcessWire\ConvertorException;
use ProcessWire\ConvertorInvalidUnitException;
use ProcessWire\FileNotFoundException;

final class ConversionRepository
{
    /** @var array<string, ConversionDefinition> */
    private $definitions = [];

    private function __construct(ConversionDefinition ...$definitions) {
        foreach ($definitions as $definition) {
//        	$definition['base'] = $base;
            $this->definitions[$definition->getUnit()] = $definition;
        }
        //bd(debug_backtrace());
        //bd($this, 'new repository');
//		throw new InvalidArgumentException('Constructed ');
    }

    public static function fromFile(string $path): ConversionRepository
    {
        if(!file_exists($path)) {
            throw new FileNotFoundException("File could not be found. Given path='$path'" .
                "either use the name of one of the pre defined configuration files or pass the complete path to the file.");
        }

        $data = include $path;

        if(!is_array($data)) {
            throw new InvalidArgumentException('The Unit definition must be an array.');
        }
		$base = $data['base'];
        $list = array_map(function ($key, $definition) use ($base) {
            if (! isset($definition['shortLabel']) || ! isset($definition['conversion'])) {
                throw new InvalidArgumentException('A conversion definition must have a shortLabel and conversion property.');
            }
            return new ConversionDefinition($key, $base, $definition['shortLabel'], $definition['conversion']);
        }, array_keys($data['units']), $data['units']);
       //bd($list, 'list');

        return new ConversionRepository(...$list);
    }

    public function getConversion(string $unit): ConversionDefinition
    {
        if (! $this->unitExists($unit)) {
            throw new ConvertorInvalidUnitException("Unit {$unit} is not defined.");
        }

        return $this->definitions[$unit];
    }

    public function unitExists(string $unit): bool
    {
        return array_key_exists($unit, $this->definitions);
    }

    public function getAvailableConversions(string $unit): array
    {
        $conversion = $this->getConversion($unit);

        $unitConversions = array_filter($this->definitions, function(ConversionDefinition  $definition) use ($conversion) {
            return $conversion->getBaseUnit() === $definition->getBaseUnit();
        });

        return array_map(function (ConversionDefinition $definition) {
            return $definition->getUnit();
        }, $unitConversions);
    }

    public function addConversion(ConversionDefinition $definition): void
    {
        if ($this->unitExists($definition->getUnit())) {
            throw new ConvertorInvalidUnitException("Unit {$definition->getUnit()} is already defined.");
        }

        if (! $this->unitExists($definition->getBaseUnit()) && $definition->isBaseUnit()) {
            throw new ConvertorException("Base Unit Does Not Exist");
        }

        $this->definitions[$definition->getUnit()] = $definition;
    }

    public function removeConversion(string $unit): void
    {
        $conversion = $this->getConversion($unit);

        if (! $conversion->isBaseUnit()) {
            unset($this->definitions[$unit]);
            return;
        }

        // Unit is a base-unit. Remove all related units first.
        foreach ($this->getAvailableConversions($unit) as $relatedUnit) {
            if ($unit === $relatedUnit) {
                continue;
            }

            $this->removeConversion($relatedUnit);
        }

        unset($this->definitions[$unit]);
    }
}
