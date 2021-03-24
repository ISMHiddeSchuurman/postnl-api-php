<?php
/**
 * The MIT License (MIT).
 *
 * Copyright (c) 2017-2020 Michael Dekker (https://github.com/firstred)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 * associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software
 * is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT
 * NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author    Michael Dekker <git@michaeldekker.nl>
 * @copyright 2017-2020 Michael Dekker
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace ThirtyBees\PostNL\Entity;

use Sabre\Xml\Writer;
use ThirtyBees\PostNL\Util\XmlSerializable;
use ThirtyBees\PostNL\Exception\InvalidArgumentException;
use ThirtyBees\PostNL\Util\UUID;

/**
 * Class Entity.
 *
 * @method string         getId()
 * @method string         getCurrentService()
 * @method AbstractEntity setId(string $id)
 * @method AbstractEntity setCurrentService(string $service)
 */
abstract class AbstractEntity implements \JsonSerializable, XmlSerializable
{
    // @codingStandardsIgnoreStart
    /** @var array */
    public static $defaultProperties = [];
    /** @var string */
    protected $id;
    /** @var string */
    protected $currentService;
    // @codingStandardsIgnoreEnd

    /**
     * AbstractEntity constructor.
     */
    public function __construct()
    {
        // Assign a default ID to this object
        $this->id = UUID::generate();
    }

    /**
     * Create an instance of this class without touching the constructor.
     *
     * @param array $properties
     *
     * @return static|object|null
     * @throws \ReflectionException
     */
    public static function create(array $properties = [])
    {
        if (__CLASS__ === get_called_class()) {
            return null;
        }

        try {
            $reflectionClass = new \ReflectionClass(get_called_class());
        } catch (\Exception $e) {
            return null;
        }

        $instance = $reflectionClass->newInstanceWithoutConstructor();

        foreach ($properties as $name => $value) {
            $instance->{'set'.$name}($value);
        }
        $instance->id = UUID::generate();

        return $instance;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return object|null
     *
     * @throws InvalidArgumentException
     */
    public function __call($name, $value)
    {
        $methodName = substr($name, 0, 3);
        $propertyName = substr($name, 3, strlen($name));
        if ('Id' === $propertyName) {
            $propertyName = 'id';
        } elseif ('CurrentService' === $propertyName) {
            $propertyName = 'currentService';
        } elseif ('ReasonNotimeframes' === $propertyName) {
            $propertyName = 'ReasonNoTimeframes';
        }

        if ('get' === $methodName) {
            if (property_exists($this, $propertyName)) {
                return $this->{$propertyName};
            } else {
                return null;
            }
        } elseif ('set' === $methodName) {
            if (!is_array($value) || count($value) < 1) {
                throw new InvalidArgumentException('Value is missing');
            }

            if (property_exists($this, $propertyName)) {
                $this->{$propertyName} = $value[0];
            }

            return $this;
        }

        throw new InvalidArgumentException('Not a valid `get` or `set` method');
    }

    /**
     * Return a serializable array for `json_encode`.
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function jsonSerialize()
    {
        $json = [];
        if (!$this->currentService || !in_array($this->currentService, array_keys(static::$defaultProperties))) {
            throw new InvalidArgumentException('Service not set before serialization');
        }

        foreach (array_keys(static::$defaultProperties[$this->currentService]) as $propertyName) {
            if (isset($this->{$propertyName})) {
                $json[$propertyName] = $this->{$propertyName};
            }
        }

        return $json;
    }

    /**
     * Return a serializable array for the XMLWriter.
     *
     * @param Writer $writer
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function xmlSerialize(Writer $writer)
    {
        $xml = [];
        if (!$this->currentService || !in_array($this->currentService, array_keys(static::$defaultProperties))) {
            throw new InvalidArgumentException('Service not set before serialization');
        }

        foreach (static::$defaultProperties[$this->currentService] as $propertyName => $namespace) {
            if (isset($this->{$propertyName})) {
                $xml[$namespace ? "{{$namespace}}{$propertyName}" : $propertyName] = $this->{$propertyName};
            }
        }

        $writer->write($xml);
    }

    /**
     * Deserialize JSON.
     *
     * @param array $json JSON as associative array
     *
     * @return AbstractEntity
     */
    public static function jsonDeserialize(array $json)
    {
        reset($json);
        $shortClassName = key($json);
        $fullClassName = static::getFullEntityClassName($shortClassName);

        // The only key in this associate array should be the object's name
        // The value should be the object itself

        if (!$fullClassName || !class_exists($fullClassName) || !is_array($json[$shortClassName])) {
            // If it's not a known object, just return the property
            return $json[$shortClassName];
        }

        $object = call_user_func([$fullClassName, 'create']);
        foreach ($json[$shortClassName] as $key => $value) {
            $fullClassName = static::getFullEntityClassName($key);
            $propertyName = $key;

            // If key is plural, try the singular version, because this might be an array
            if (!$fullClassName && 's' === substr($key, -1)) {
                $fullClassName = static::getFullEntityClassName(substr($key, 0, strlen($key) - 1));
                $propertyName = substr($propertyName, 0, strlen($propertyName) - 1);
            }

            if (is_array($value) && is_subclass_of($fullClassName, AbstractEntity::class)) {
                $entities = [];
                foreach ($value as $name => $item) {
                    $entities[] = static::jsonDeserialize([$propertyName => $item]);
                }
                $object->{'set'.$key}($entities);
            } else {
                $object->{'set'.$key}(static::jsonDeserialize([$propertyName => $value]));
            }
        }

        return $object;
    }

    /**
     * Deserialize XML.
     *
     * @param array $xml Associative array representation of XML response, using Clark notation for namespaces
     *
     * @return AbstractEntity
     */
    public static function xmlDeserialize(array $xml)
    {
        if (!isset($xml['name']) && isset($xml[0]['name'])) {
            $xml = $xml[0];
        }

        $shortClassName = preg_replace('/(\{.*\})([A-Za-z]+)/', '$2', $xml['name']);
        $fullClassName = static::getFullEntityClassName($shortClassName);

        // The only key in this associate array should be the object's name
        // The value should be the object itself

        if (!$fullClassName || !class_exists($fullClassName) || !is_array($xml['value'])) {
            // If it's not a known object, just return the property
            return $xml['value'];
        }

        $object = call_user_func([$fullClassName, 'create']);
        foreach ($xml['value'] as $value) {
            $shortClassName = preg_replace('/(\{.*\})([A-Za-z]+)/', '$2', $value['name']);
            $fullClassName = static::getFullEntityClassName($shortClassName);

            // If key is plural, try the singular version, because this might be an array
            if (in_array($shortClassName, ['OldStatuses', 'Statuses', 'Addresses'])) {
                $fullClassName = static::getFullEntityClassName(substr($shortClassName, 0, strlen($shortClassName) - 2));
            } elseif (!$fullClassName && 's' === substr($shortClassName, -1)) {
                $fullClassName = static::getFullEntityClassName(substr($shortClassName, 0, strlen($shortClassName) - 1));
            }

            if (!$value['value']) {
                $object->{'set'.$shortClassName}($value['value']);
            } elseif (is_array($value['value'])
                && count($value['value']) >= 1
                && !in_array($shortClassName, ['Customer', 'OpeningHours', 'Customs'])
                && is_subclass_of($fullClassName, AbstractEntity::class)
            ) {
                $entities = [];
                if (isset($value['value'][0]['value']) && !is_array($value['value'][0]['value'])) {
                    $object->{'set'.$shortClassName}(static::xmlDeserialize([$value]));
                } else {
                    foreach (array_values($value['value']) as $item) {
                        if (!is_array($item['value'])) {
                            $entities[$item['name']] = $item['value'];
                        } else {
                            $entities[] = static::xmlDeserialize([$item]);
                        }
                    }

                    $object->{'set'.$shortClassName}($entities);
                }
            } else {
                $object->{'set'.$shortClassName}(static::xmlDeserialize([$value]));
            }
        }

        return $object;
    }

    /**
     * Get the full class (incl. namespace) for the given short class name.
     *
     * @param string $shortName
     *
     * @return false|string The full name if found, else `false`
     */
    public static function getFullEntityClassName($shortName)
    {
        foreach ([
            '\\ThirtyBees\\PostNL\\Entity',
            '\\ThirtyBees\\PostNL\\Entity\\Message',
            '\\ThirtyBees\\PostNL\\Entity\\Request',
            '\\ThirtyBees\\PostNL\\Entity\\Response',
            '\\ThirtyBees\\PostNL\\Entity\\SOAP',
        ] as $namespace) {
            if (class_exists("$namespace\\$shortName")) {
                return "$namespace\\$shortName";
            }
        }

        return false;
    }

    /**
     * Determine if the array is associative.
     *
     * @param array $array
     *
     * @return bool
     */
    protected static function isAssociativeArray($array)
    {
        if ([] === $array || !is_array($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
