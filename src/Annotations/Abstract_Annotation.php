<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Context;
use Open_Api\Generator;
use Open_Api\Open_Api_Exception;
use Symfony\Component\Yaml\Yaml;
/**
 * The openapi annotation base class.
 */
abstract class Abstract_Annotation implements \JsonSerializable
{
    /**
     * While the OpenAPI Specification tries to accommodate most use cases, additional data can be added to extend the specification at certain points.
     * For further details see https://github.com/OAI/OpenAPI-Specification/blob/main/versions/3.1.0.md#specificationExtensions
     * The keys inside the array will be prefixed with <code>x-</code>.
     *
     * @var array<string,mixed>
     */
    public $x = Generator::UNDEFINED;
    /**
     * Arbitrary attachables for this annotation.
     * These will be ignored but can be used for custom processing.
     *
     * @var array
     */
    public $attachables = Generator::UNDEFINED;
    public ?Context $_context;
    /**
     * Annotations that couldn't be merged by mapping or postprocessing.
     *
     * @var array
     */
    public $_unmerged = [];
    /**
     * The properties which are required by [the spec](https://github.com/OAI/OpenAPI-Specification/blob/main/versions/3.1.0.md).
     *
     * @var list<string>
     */
    public static $_required = [];
    /**
     * Specify the type of the property.
     *
     * Examples:
     *   'name' => 'string'         // a string
     *   'required' => 'boolean',   // true or false
     *   'tags' => '[string]',      // string array
     *   'in' => ["query", "header", "path", "formData", "body"] // must be one on these
     *   'oneOf' => [Schema::class] // array of schema objects.
     *
     * @var array<string,string|array<string>>
     */
    public static $_types = [];
    /**
     * Declarative mapping of Annotation types to properties.
     *
     * Examples:
     *   Info::class => 'info',                // Set @OA\Info annotation as the info property.
     *   Parameter::class => ['parameters'],   // Append @OA\Parameter annotations the parameters list.
     *   PathItem::class => ['paths', 'path'], // Add @OA\PathItem annotation to the `paths` map and use `path` as key.
     *
     * @var array<class-string<AbstractAnnotation>,string|array<string>>
     */
    public static $_nested = [];
    /**
     * Reverse mapping of $_nested with the allowed parent annotations.
     *
     * @var array<class-string<AbstractAnnotation>>
     */
    public static $_parents = [];
    /**
     * Properties that are blacklisted from the JSON output.
     *
     * @var array<string>
     */
    public static $_blacklist = ['_context', '_unmerged', '_analysis', 'attachables'];
    public function __construct(array $properties)
    {
        if (isset($properties['_context'])) {
            $this->_context = $properties['_context'];
            unset($properties['_context']);
        } elseif (Generator::$context) {
            $this->_context = Generator::$context;
        } else {
            $this->_context = new Context(['generated' => true]);
        }
        if ($this->_context->is('annotations') === false) {
            $this->_context->annotations = [];
        }
        $this->_context->annotations[] = $this;
        $nested_context = new Context(['nested' => $this], $this->_context);
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
                if (is_array($value)) {
                    foreach ($value as $key => $annotation) {
                        if ($annotation instanceof Abstract_Annotation) {
                            $this->{$property}[$key] = $this->nested($annotation, $nested_context);
                        }
                    }
                }
            } elseif ($property !== 'value') {
                $this->{$property} = $value;
            } elseif (is_array($value)) {
                $annotations = [];
                foreach ($value as $annotation) {
                    if ($annotation instanceof Abstract_Annotation) {
                        $annotations[] = $annotation;
                    } else {
                        $this->_context->logger->warning('Unexpected field in ' . $this->identity() . ' in ' . $this->_context);
                    }
                }
                $this->merge($annotations);
            } elseif (is_object($value)) {
                $this->merge([$value]);
            } else if (!Generator::is_default($value)) {
                $this->_context->logger->warning('Unexpected parameter "' . $property . '" in ' . $this->identity());
            }
        }
    }
    /**
     * Merge given annotations to their mapped properties configured in static::$_nested.
     *
     * Annotations that couldn't be merged are added to the _unmerged array.
     *
     * @param list<AbstractAnnotation> $annotations
     * @param bool                     $ignore      Ignore unmerged annotations
     *
     * @return list<AbstractAnnotation> The unmerged annotations
     */
    public function merge(array $annotations, bool $ignore = false): array
    {
        $unmerged = [];
        $nested_context = new Context(['nested' => $this], $this->_context);
        foreach ($annotations as $annotation) {
            $mapped = false;
            if ($details = $this->match_nested($annotation)) {
                $property = $details->value;
                if (is_array($property)) {
                    $property = $property[0];
                    if (Generator::is_default($this->{$property})) {
                        $this->{$property} = [];
                    }
                    $this->{$property}[] = $this->nested($annotation, $nested_context);
                    $mapped = true;
                } elseif (Generator::is_default($this->{$property})) {
                    // ignore duplicate nested if only one expected
                    $this->{$property} = $this->nested($annotation, $nested_context);
                    $mapped = true;
                }
            }
            if (!$mapped) {
                $unmerged[] = $annotation;
            }
        }
        if (!$ignore) {
            foreach ($unmerged as $annotation) {
                $this->_unmerged[] = $this->nested($annotation, $nested_context);
            }
        }
        return $unmerged;
    }
    /**
     * Merge the properties from the given object into this annotation.
     * Prevents overwriting properties that are already configured.
     *
     * @param object $object
     */
    public function merge_properties($object): void
    {
        $current_values = get_object_vars($this);
        foreach ($object as $property => $value) {
            if ($property === '_context') {
                continue;
            }
            if (Generator::is_default($current_values[$property])) {
                // Overwrite default values
                $this->{$property} = $value;
                continue;
            }
            if ($property === '_unmerged') {
                $this->_unmerged = array_merge($this->_unmerged, $value);
                continue;
            }
            if ($current_values[$property] !== $value) {
                // New value is not the same?
                if (Generator::is_default($value)) {
                    continue;
                }
                $identity = method_exists($object, 'identity') ? $object->identity() : $object::class;
                $context1 = $this->_context;
                $context2 = property_exists($object, '_context') ? $object->_context : 'unknown';
                if ($this->{$property} instanceof Abstract_Annotation) {
                    $context1 = $this->{$property}->_context;
                }
                $this->_context->logger->error('Multiple definitions for ' . $identity . '->' . $property . "\n     Using: " . $context1 . "\n  Skipping: " . $context2);
            }
        }
    }
    /**
     * Generate the documentation in YAML format.
     *
     * @param int-mask-of<Yaml::PARSE_*>|null $flags A bit field of PARSE_* constants to customize the YAML parser behavior
     */
    public function to_yaml(?int $flags = null): string
    {
        if ($flags === null) {
            $flags = Yaml::DUMP_OBJECT_AS_MAP ^ Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE;
        }
        return Yaml::dump(json_decode($this->to_json(JSON_INVALID_UTF8_IGNORE)), 10, 2, $flags);
    }
    /**
     * Generate the documentation in JSON format.
     */
    public function to_json(?int $flags = null): string
    {
        if ($flags === null) {
            $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE;
        }
        return json_encode($this, $flags);
    }
    public function __debugInfo()
    {
        $properties = [];
        foreach (get_object_vars($this) as $property => $value) {
            if (!Generator::is_default($value)) {
                $properties[$property] = $value;
            }
        }
        return $properties;
    }
    public function jsonSerialize(): \stdClass
    {
        $data = new \stdClass();
        // Strip undefined values.
        foreach (get_object_vars($this) as $property => $value) {
            if (!Generator::is_default($value)) {
                $data->{$property} = $value;
            }
        }
        // Strip properties that are for internal (swagger-php) use.
        foreach (static::$_blacklist as $property) {
            unset($data->{$property});
        }
        // Correct empty array to empty objects.
        foreach (static::$_types as $property => $type) {
            if ($type === 'object' && is_array($data->{$property}) && $data->{$property} === []) {
                $data->{$property} = new \stdClass();
            }
        }
        // Inject vendor properties.
        unset($data->x);
        if (is_array($this->x)) {
            foreach ($this->x as $property => $value) {
                $prefixed = 'x-' . $property;
                $data->{$prefixed} = $value;
            }
        }
        // Map nested keys
        foreach (static::$_nested as $nested) {
            if (is_string($nested)) {
                continue;
            }
            if (count($nested) === 1) {
                continue;
            }
            $property = $nested[0];
            if (Generator::is_default($this->{$property})) {
                continue;
            }
            $key_field = $nested[1];
            $object = new \stdClass();
            foreach ($this->{$property} as $key => $item) {
                if (is_numeric($key) === false && is_array($item)) {
                    $object->{$key} = $item;
                } else {
                    $key = $item->{$key_field};
                    if (!Generator::is_default($key) && empty($object->{$key})) {
                        $object->{$key} = $item instanceof \JsonSerializable ? $item->jsonSerialize() : $item;
                        unset($object->{$key}->{$key_field});
                    }
                }
            }
            $data->{$property} = $object;
        }
        // $ref
        if (isset($data->ref)) {
            // Only specific https://github.com/OAI/OpenAPI-Specification/blob/3.1.0/versions/3.1.0.md#reference-object
            $ref = ['$ref' => $data->ref];
            if (!$this->_context->is_version('3.0.x')) {
                foreach (['summary', 'description'] as $prop) {
                    if (property_exists($data, $prop)) {
                        $ref[$prop] = $data->{$prop};
                    }
                }
            }
            if (property_exists($this, 'nullable') && $this->nullable === true) {
                $ref = ['oneOf' => [$ref]];
                if (!$this->_context->is_version('3.0.x')) {
                    $ref['oneOf'][] = ['type' => 'null'];
                } else {
                    $ref['nullable'] = $data->nullable;
                }
                unset($data->ref, $data->nullable);
                // preserve other properties
                foreach (get_object_vars($data) as $property => $value) {
                    $ref[$property] = $value;
                }
            }
            $data = (object) $ref;
        }
        if ($this->_context->is_version('3.0.x')) {
            if (isset($data->exclusive_minimum) && is_numeric($data->exclusive_minimum)) {
                $data->minimum = $data->exclusive_minimum;
                $data->exclusive_minimum = true;
            }
            if (isset($data->exclusive_maximum) && is_numeric($data->exclusive_maximum)) {
                $data->maximum = $data->exclusive_maximum;
                $data->exclusive_maximum = true;
            }
            if (isset($data->type) && is_array($data->type)) {
                if (in_array('null', $data->type)) {
                    $data->nullable = true;
                    $data->type = array_filter($data->type, static fn($v): bool => $v !== 'null');
                    if (1 === count($data->type)) {
                        $data->type = array_pop($data->type);
                    }
                }
            }
            if (isset($data->type) && is_array($data->type)) {
                if (1 === count($data->type)) {
                    $data->type = array_pop($data->type);
                } else {
                    unset($data->type);
                }
            }
            unset($data->unevaluated_properties);
        }
        if (!$this->_context->is_version('3.0.x')) {
            if (isset($data->nullable)) {
                if (true === $data->nullable) {
                    if (isset($data->one_of)) {
                        $data->one_of[] = ['type' => 'null'];
                    } elseif (isset($data->any_of)) {
                        $data->any_of[] = ['type' => 'null'];
                    } elseif (isset($data->all_of)) {
                        $data->all_of[] = ['type' => 'null'];
                    } elseif (isset($data->type)) {
                        $data->type = (array) $data->type;
                        $data->type[] = 'null';
                    }
                }
                unset($data->nullable);
            }
            if (isset($data->minimum) && isset($data->exclusive_minimum)) {
                if (true === $data->exclusive_minimum) {
                    $data->exclusive_minimum = $data->minimum;
                    unset($data->minimum);
                } elseif (false === $data->exclusive_minimum) {
                    unset($data->exclusive_minimum);
                }
            }
            if (isset($data->maximum) && isset($data->exclusive_maximum)) {
                if (true === $data->exclusive_maximum) {
                    $data->exclusive_maximum = $data->maximum;
                    unset($data->maximum);
                } elseif (false === $data->exclusive_maximum) {
                    unset($data->exclusive_maximum);
                }
            }
        }
        return $data;
    }
    /**
     * Validate a given value against a `_$type` definition.
     */
    private function validate_value_type(string $type, mixed $value): bool
    {
        if (str_starts_with($type, '[') && str_ends_with($type, ']')) {
            // $value must be an array
            if (!$this->validate_value_type('array', $value)) {
                return false;
            }
            $item_type = substr($type, 1, -1);
            foreach ($value as $item) {
                if (!$this->validate_value_type($item_type, $item)) {
                    return false;
                }
            }
            return true;
        }
        if (is_subclass_of($type, Abstract_Annotation::class)) {
            $type = 'object';
        }
        $is_valid_type = fn(string $type, mixed $value): bool => match ($type) {
            'string' => is_string($value),
            'boolean' => is_bool($value),
            'integer' => is_int($value),
            'number' => is_numeric($value),
            'object' => is_object($value),
            'array' => is_array($value) && array_is_list($value),
            'scheme' => in_array($value, ['http', 'https', 'ws', 'wss'], strict: true),
            default => throw new Open_Api_Exception('Invalid type "' . $type . '"'),
        };
        foreach (explode('|', $type) as $tt) {
            if ($is_valid_type(trim($tt), $value)) {
                return true;
            }
        }
        return false;
    }
    public function validate(?Analysis $analysis = null, string $version = Open_Api::DEFAULT_VERSION, ?object $context = null): bool
    {
        $is_valid = true;
        // validate unmerged
        foreach ($this->_unmerged as $annotation) {
            if (!is_object($annotation)) {
                $this->_context->logger->warning('Unexpected type: "' . gettype($annotation) . '" in ' . $this->identity() . '->_unmerged, expecting a Annotation object');
                break;
            }
            if ($details = $this->match_nested($annotation)) {
                $property = $details->value;
                if (is_array($property)) {
                    $this->_context->logger->warning('Only one ' . $annotation->identity([]) . ' allowed for ' . $this->identity() . ' multiple found, skipped: ' . $annotation->_context);
                } else {
                    $this->_context->logger->warning('Only one ' . $annotation->identity([]) . ' allowed for ' . $this->identity() . " multiple found in:\n    Using: " . $this->{$property}->_context . "\n  Skipped: " . $annotation->_context);
                }
            } elseif ($annotation instanceof Abstract_Annotation) {
                $message = 'Unexpected ' . $annotation->identity();
                if ($annotation::$_parents) {
                    $message .= ', expected to be inside ' . implode(', ', Abstract_Annotation::shorten($annotation::$_parents));
                }
                $this->_context->logger->warning($message . ' in ' . $annotation->_context);
            }
            $is_valid = false;
        }
        // validate conflicting keys
        foreach ($this::$_nested as $annotation_class => $nested) {
            if (is_string($nested)) {
                continue;
            }
            if (count($nested) === 1) {
                continue;
            }
            $property = $nested[0];
            if (Generator::is_default($this->{$property})) {
                continue;
            }
            $keys = [];
            $key_field = $nested[1];
            /** @var AbstractAnnotation $item */
            foreach ($this->{$property} as $key => $item) {
                if (is_array($item) && !is_numeric($key)) {
                    $this->_context->logger->warning($this->identity() . '->' . $property . ' is an object literal, use nested ' . Abstract_Annotation::shorten($annotation_class) . '() annotation(s) in ' . $this->_context);
                    $keys[$key] = $item;
                } elseif (Generator::is_default($item->{$key_field})) {
                    $this->_context->logger->error($item->identity() . ' is missing key-field: "' . $key_field . '" in ' . $item->_context);
                } elseif (isset($keys[$item->{$key_field}])) {
                    $this->_context->logger->error('Multiple ' . $item->identity([]) . ' with the same ' . $key_field . '="' . $item->{$key_field} . "\":\n  " . $item->_context . "\n  " . $keys[$item->{$key_field}]->_context);
                } else {
                    $keys[$item->{$key_field}] = $item;
                }
            }
        }
        // validate refs
        if ($analysis?->openapi && property_exists($this, 'ref') && !Generator::is_default($this->ref) && is_string($this->ref)) {
            if (str_starts_with($this->ref, '#/')) {
                try {
                    $analysis->openapi->ref($this->ref);
                } catch (\Exception $e) {
                    $this->_context->logger->warning($e->get_message() . ' for ' . $this->identity() . ' in ' . $this->_context, ['exception' => $e]);
                    $is_valid = false;
                }
            }
        }
        // validate required properties
        if (!property_exists($this, 'ref') || Generator::is_default($this->ref) || !is_string($this->ref)) {
            foreach ($this::$_required as $property) {
                if (Generator::is_default($this->{$property})) {
                    $message = 'Missing required field "' . $property . '" for ' . $this->identity() . ' in ' . $this->_context;
                    foreach ($this::$_nested as $class => $nested) {
                        $nested_property = is_array($nested) ? $nested[0] : $nested;
                        if ($property === $nested_property) {
                            if ($this instanceof Open_Api) {
                                $message = 'Required ' . Abstract_Annotation::shorten($class) . '() not found';
                            } elseif (is_array($nested)) {
                                $message = $this->identity() . ' requires at least one ' . Abstract_Annotation::shorten($class) . '() in ' . $this->_context;
                            } else {
                                $message = $this->identity() . ' requires a ' . Abstract_Annotation::shorten($class) . '() in ' . $this->_context;
                            }
                            break;
                        }
                    }
                    $this->_context->logger->warning($message);
                }
            }
        }
        // validate types
        foreach ($this::$_types as $property => $type) {
            $value = $this->{$property};
            if (Generator::is_default($value)) {
                continue;
            }
            if ($value === null) {
                continue;
            }
            if (is_string($type)) {
                if (!$this->validate_value_type($type, $value)) {
                    $this->_context->logger->warning($this->identity() . '->' . $property . ' is a "' . gettype($value) . '", expecting a "' . $type . '" in ' . $this->_context);
                    $is_valid = false;
                }
            } elseif (is_array($type)) {
                // enum?
                if (!in_array($value, $type)) {
                    $this->_context->logger->warning($this->identity() . '->' . $property . ' "' . $value . '" is invalid, expecting "' . implode('", "', $type) . '" in ' . $this->_context);
                }
            } else {
                throw new Open_Api_Exception('Invalid ' . static::class . '::$_types[' . $property . ']');
            }
        }
        // validate example/examples
        if (property_exists($this, 'example') && property_exists($this, 'examples')) {
            if (!Generator::is_default($this->example) && !Generator::is_default($this->examples)) {
                $this->_context->logger->warning($this->identity() . ': "example" and "examples" are mutually exclusive');
                $is_valid = false;
            }
        }
        return $is_valid;
    }
    /**
     * Return a simple string representation of the annotation.
     *
     * @param array|null $properties the properties to include in the string representation
     * @example "@OA\Response(response=200)"
     */
    public function identity(?array $properties = null): string
    {
        $class = static::class;
        if (null === $properties) {
            $properties = [];
            /** @var class-string<AbstractAnnotation> $parent */
            foreach (static::$_parents as $parent) {
                foreach ($parent::$_nested as $annotation_class => $entry) {
                    if ($annotation_class === $class && is_array($entry) && !Generator::is_default($this->{$entry[1]})) {
                        $properties[] = $entry[1];
                        break 2;
                    }
                }
            }
        }
        $details = [];
        foreach ($properties as $property) {
            $value = $this->{$property};
            if ($value !== null && !Generator::is_default($value)) {
                $details[] = $property . '=' . (is_string($value) ? '"' . $value . '"' : $value);
            }
        }
        return static::shorten(static::class) . '(' . implode(',', $details) . ')';
    }
    /**
     * Check if <code>$other</code> can be nested, and if so, return details about where/how.
     *
     * @param AbstractAnnotation $other the other annotation
     *
     * @return null|object key/value object or <code>null</code>
     */
    public function match_nested($other)
    {
        if ($other instanceof Abstract_Annotation && array_key_exists($root = $other->get_root(), static::$_nested)) {
            return (object) ['key' => $root, 'value' => static::$_nested[$root]];
        }
        return null;
    }
    /**
     * Get the root annotation.
     *
     * This is used for resolving type equality and nesting rules to allow those rules to also work for custom,
     * derived annotation classes.
     *
     * @return class-string the root annotation class in the <code>OpenApi\\Annotations</code> namespace
     */
    public function get_root(): string
    {
        $class = static::class;
        do {
            if (str_starts_with($class, 'OpenApi\Annotations\\')) {
                break;
            }
        } while ($class = get_parent_class($class));
        return $class;
    }
    /**
     * Match the annotation root.
     *
     * @param class-string $thisClass the root class to match
     */
    public function is_root(string $this_class): bool
    {
        return static::class === $this_class || $this->get_root() === $this_class;
    }
    /**
     * Wrap the context with a reference to the annotation it is nested in.
     */
    protected function nested(Abstract_Annotation $annotation, Context $nested_context): self
    {
        if (property_exists($annotation, '_context') && $annotation->_context === $this->_context) {
            $annotation->_context = $nested_context;
        }
        return $annotation;
    }
    protected function combine(...$args): array
    {
        $combined = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $combined = array_merge($combined, $arg);
            } else {
                $combined[] = $arg;
            }
        }
        return array_filter($combined, static fn($value): bool => !Generator::is_default($value) && $value !== null);
    }
    /**
     * Shorten class name(s).
     *
     * @param array|object|string $classes Class(es) to shorten
     *
     * @return string|list<string> One or more shortened class names
     */
    protected static function shorten($classes)
    {
        $short = [];
        foreach ((array) $classes as $class) {
            $short[] = '@' . str_replace(['OpenApi\Annotations\\', 'OpenApi\Attributes\\'], 'OA\\', (string) $class);
        }
        return is_array($classes) ? $short : array_pop($short);
    }
}