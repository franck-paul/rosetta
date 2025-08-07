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
     * @var \ReflectionProperty[] $properties  Class properties
     */
    protected array $properties;

    /**
     * @param MetaRecord|array<int|string, mixed>|null      $data       Current values
     * @param bool                                          $complete   True if all values must be present in data
     *
     * Use a MetaRecord to populate properties values or
     * Use an array (associative or indexed) to populate properties values
     *
     * If using an indexed array, values must be given in same order as declared properties.
     */
    public function __construct(MetaRecord|array|null $data = null, bool $complete = true)
    {
        if (!is_null($data)) {
            $this->populate($data, $complete);
        }
    }

    /**
     * @param MetaRecord|array<int|string, mixed>           $data       Current values
     * @param bool                                          $complete   True if all values must be present in data
     *
     * Use a MetaRecord to populate properties values or
     * Use an array (associative or indexed) to populate properties values
     *
     * If using an indexed array, values must be given in same order as declared properties.
     */
    public function populate(MetaRecord|array $data, bool $complete = true): void
    {
        $reflectionClass = new ReflectionClass($this);
        if (!isset($this->properties)) {
            $this->properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
        }

        $index = 0;
        foreach ($this->properties as $property) {
            $propertyName = $property->getName();
            $valueExists  = is_array($data) ? (isset($data[$propertyName]) || isset($data[$index])) : $data->exists($propertyName);
            if ($valueExists) {
                $value = is_array($data) ? $data[$propertyName] ?? $data[$index] : $data->field($propertyName);
                if ($property->hasType() && $property->getType() instanceof ReflectionNamedType) {
                    if (!$property->getType()->allowsNull() && $value === null) {
                        throw new Exception(sprintf('Null is not an allowed value for field %s', $propertyName));
                    }
                    if ($value !== null) {
                        // Will only work with basic types (int, string, …)
                        settype($value, $property->getType()->getName());
                    }
                } elseif ($property->hasType()) {
                    if ($property->getType() instanceof ReflectionUnionType || $property->getType() instanceof ReflectionIntersectionType) {
                        throw new Exception(sprintf('Unsupported property type for %s', $propertyName));
                    }
                }
                $this->{$propertyName} = $value;
            } elseif ($complete) {
                // Value is missing
                throw new Exception(sprintf('Field %s not present in %s', $propertyName, is_array($data) ? 'array' : 'record'));
            } elseif ($property->getType() instanceof ReflectionNamedType && $property->getType()->allowsNull()) {
                // Set value to null if nullable
                $this->{$propertyName} = null;
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
