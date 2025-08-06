<?php

/**
 * @brief rosetta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\rosetta;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Exception;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

abstract class Row
{
    /**
     * Create a new instance
     *
     * If a MetaRecord is given, the properties are initialized with the current values
     *
     * @var \ReflectionProperty[] $properties  Class properties
     */
    protected array $properties;

    public function __construct(?MetaRecord $rs = null)
    {
        if ($rs instanceof MetaRecord) {
            $reflectionClass = new ReflectionClass($this);
            if (!isset($this->properties)) {
                $this->properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
            }

            foreach ($this->properties as $property) {
                if ($rs->exists($property->getName())) {
                    $value = $rs->field($property->getName());
                    if ($property->hasType() && $property->getType() instanceof ReflectionNamedType) {
                        if (!$property->getType()->allowsNull() && $value === null) {
                            throw new Exception(sprintf('Null is not an allowed value for field %s', $property->getName()));
                        }
                        if ($value !== null) {
                            // Will only work with basic types (int, string, …)
                            settype($value, $property->getType()->getName());
                        }
                    } elseif ($property->hasType()) {
                        if ($property->getType() instanceof ReflectionUnionType || $property->getType() instanceof ReflectionIntersectionType) {
                            throw new Exception(sprintf('Unsupported property type for %s', $property->getName()));
                        }
                    }
                    $this->{$property->getName()} = $value;
                } else {
                    throw new Exception(sprintf('Field %s not present in record', $property->getName()));
                }
            }
        }
    }

    /**
     * Populate Row properties with given data
     *
     * Data array may be associative or sequential (must be in same order as declared properties in this case)
     *
     * @param  array<int|string, mixed>     $data     Data
     * @param  bool                         $complete Throw exception if some value(s) are missing from data
     */
    public function populate(array $data, bool $complete = false): void
    {
        if (!isset($this->properties)) {
            $reflectionClass  = new ReflectionClass($this);
            $this->properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
        }

        $index = 0;
        foreach ($this->properties as $property) {
            if (isset($data[$property->getName()]) || isset($data[$index])) {
                $value = $data[$property->getName()] ?? $data[$index];
                if ($property->hasType() && $property->getType() instanceof ReflectionNamedType) {
                    if (!$property->getType()->allowsNull() && $value === null) {
                        throw new Exception(sprintf('Null is not an allowed value for field %s', $property->getName()));
                    }
                    if ($value !== null) {
                        // Will only work with basic types (int, string, …)
                        settype($value, $property->getType()->getName());
                    }
                } elseif ($property->hasType()) {
                    if ($property->getType() instanceof ReflectionUnionType || $property->getType() instanceof ReflectionIntersectionType) {
                        throw new Exception(sprintf('Unsupported property type for %s', $property->getName()));
                    }
                }
                $this->{$property->getName()} = $value;
            } elseif ($complete) {
                throw new Exception(sprintf('Field %s not present in data', $property->getName()));
            }
            $index++;
        }
    }

    /**
     * Initialize Cursor data with Row properties values
     *
     * @param Cursor $cursor The Cursor to populate
     */
    public function setCursor(Cursor $cursor): void
    {
        if (!isset($this->properties)) {
            $reflectionClass  = new ReflectionClass($this);
            $this->properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
        }

        foreach ($this->properties as $property) {
            $propertyName            = $property->getName();
            $cursor->{$propertyName} = $this->{$propertyName};
        }
    }
}
