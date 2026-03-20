<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api;

use Open_Api\Annotations as OA;
/**
 * Result of the analyser.
 *
 * Pretends to be an array of annotations but also contains detected classes and helper functions for the processors.
 */
class Analysis
{
    /** @var \SplObjectStorage<OA\AbstractAnnotation, Context> */
    public \Spl_Object_Storage $annotations;
    /**
     * Class definitions.
     */
    public array $classes = [];
    /**
     * Interface definitions.
     */
    public array $interfaces = [];
    /**
     * Trait definitions.
     */
    public array $traits = [];
    /**
     * Enum definitions.
     */
    public array $enums = [];
    /**
     * The target OpenApi annotation.
     */
    public ?OA\Open_Api $openapi = null;
    /**
     * @param list<OA\AbstractAnnotation> $annotations
     */
    public function __construct(array $annotations = [], public ?Context $context = null)
    {
        $this->annotations = new \Spl_Object_Storage();
        $this->add_annotations($annotations, $this->context);
    }
    public function add_annotation(OA\Abstract_Annotation $annotation, Context $context): void
    {
        if ($this->annotations->offsetExists($annotation)) {
            return;
        }
        $context->ensure_root($this->context);
        if ($annotation instanceof OA\Open_Api) {
            $this->openapi = $this->openapi ?: $annotation;
        } else {
            if ($context->is('annotations') === false) {
                $context->annotations = [];
            }
            if (in_array($annotation, $context->annotations, strict: true) === false) {
                $context->annotations[] = $annotation;
            }
        }
        $this->annotations->offsetSet($annotation, $context);
        foreach (get_object_vars($annotation) as $property => $value) {
            if (in_array($property, $annotation::$_blacklist)) {
                if ($property === '_unmerged') {
                    foreach ($value as $item) {
                        $this->add_annotation($item, $context);
                    }
                }
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    if ($item instanceof OA\Abstract_Annotation) {
                        $this->add_annotation($item, $context);
                    }
                }
            } elseif ($value instanceof OA\Abstract_Annotation) {
                $this->add_annotation($value, $context);
            }
        }
    }
    /**
     * @param list<OA\AbstractAnnotation> $annotations
     */
    public function add_annotations(array $annotations, Context $context): void
    {
        foreach ($annotations as $annotation) {
            $this->add_annotation($annotation, $context);
        }
    }
    public function add_class_definition(array $definition): void
    {
        $class = $definition['context']->fully_qualified_name($definition['class']);
        $this->classes[$class] = $definition;
    }
    public function add_interface_definition(array $definition): void
    {
        $interface = $definition['context']->fully_qualified_name($definition['interface']);
        $this->interfaces[$interface] = $definition;
    }
    public function add_trait_definition(array $definition): void
    {
        $trait = $definition['context']->fully_qualified_name($definition['trait']);
        $this->traits[$trait] = $definition;
    }
    public function add_enum_definition(array $definition): void
    {
        $enum = $definition['context']->fully_qualified_name($definition['enum']);
        $this->enums[$enum] = $definition;
    }
    public function add_analysis(Analysis $analysis): void
    {
        foreach ($analysis->annotations as $annotation) {
            $this->add_annotation($annotation, $analysis->annotations[$annotation]);
        }
        $this->classes = array_merge($this->classes, $analysis->classes);
        $this->interfaces = array_merge($this->interfaces, $analysis->interfaces);
        $this->traits = array_merge($this->traits, $analysis->traits);
        $this->enums = array_merge($this->enums, $analysis->enums);
        if (!$this->openapi instanceof OA\Open_Api && $analysis->openapi instanceof OA\Open_Api) {
            $this->openapi = $analysis->openapi;
        }
    }
    /**
     * Get all subclasses of the given parent class.
     *
     * @param class-string $parent the parent class
     *
     * @return array map of class => definition pairs of sub-classes
     */
    public function get_sub_classes(string $parent): array
    {
        $definitions = [];
        foreach ($this->classes as $class => $class_definition) {
            if ($class_definition['extends'] === $parent) {
                $definitions[$class] = $class_definition;
                $definitions = array_merge($definitions, $this->get_sub_classes($class));
            }
        }
        return $definitions;
    }
    /**
     * Get a list of all super classes for the given class.
     *
     * @param class-string|null $class  the class name
     * @param bool              $direct flag to find only the actual class parents
     *
     * @return array map of class => definition pairs of parent classes
     */
    public function get_super_classes(?string $class, bool $direct = false): array
    {
        $class_definition = $this->classes[$class ?? ''] ?? null;
        if (!$class_definition || empty($class_definition['extends'])) {
            // unknown class, or no inheritance
            return [];
        }
        $extends = $class_definition['extends'];
        $extends_definition = $this->classes[$extends] ?? null;
        if (!$extends_definition) {
            return [];
        }
        $parent_details = [$extends => $extends_definition];
        if ($direct) {
            return $parent_details;
        }
        return array_merge($parent_details, $this->get_super_classes($extends));
    }
    /**
     * Get the list of interfaces used by the given class or by classes which it extends.
     *
     * @param class-string|null $class  the class name
     * @param bool              $direct flag to find only the actual class interfaces
     *
     * @return array map of class => definition pairs of interfaces
     */
    public function get_interfaces_of_class(?string $class, bool $direct = false): array
    {
        $classes = $direct ? [] : array_keys($this->get_super_classes($class));
        // add self
        $classes[] = $class;
        $definitions = [];
        foreach ($classes as $clazz) {
            if (isset($this->classes[$clazz])) {
                $definition = $this->classes[$clazz];
                if (isset($definition['implements'])) {
                    foreach ($definition['implements'] as $interface) {
                        if (array_key_exists($interface, $this->interfaces)) {
                            $definitions[$interface] = $this->interfaces[$interface];
                        }
                    }
                }
            }
        }
        if (!$direct) {
            // expand recursively for interfaces extending other interfaces
            $collect = function ($interfaces, $cb) use (&$definitions): void {
                foreach ($interfaces as $interface) {
                    if (isset($this->interfaces[$interface]['extends'])) {
                        $cb($this->interfaces[$interface]['extends'], $cb);
                        foreach ($this->interfaces[$interface]['extends'] as $fqdn) {
                            $definitions[$fqdn] = $this->interfaces[$fqdn];
                        }
                    }
                }
            };
            $collect(array_keys($definitions), $collect);
        }
        return $definitions;
    }
    /**
     * Get the list of traits used by the given class/trait or by classes which it extends.
     *
     * @param string|null $source the source name
     * @param bool        $direct flag to find only the actual class traits
     *
     * @return array map of class => definition pairs of traits
     */
    public function get_traits_of_class(?string $source, bool $direct = false): array
    {
        $sources = $direct ? [] : array_keys($this->get_super_classes($source));
        // add self
        $sources[] = $source;
        $definitions = [];
        foreach ($sources as $sourze) {
            if (isset($this->classes[$sourze]) || isset($this->traits[$sourze])) {
                $definition = $this->classes[$sourze] ?? $this->traits[$sourze];
                if (isset($definition['traits'])) {
                    foreach ($definition['traits'] as $trait) {
                        if (array_key_exists($trait, $this->traits)) {
                            $definitions[$trait] = $this->traits[$trait];
                        }
                    }
                }
            }
        }
        if (!$direct) {
            // expand recursively for traits using other traits
            $collect = function ($traits, $cb) use (&$definitions): void {
                foreach ($traits as $trait) {
                    if (isset($this->traits[$trait]['traits'])) {
                        $cb($this->traits[$trait]['traits'], $cb);
                        foreach ($this->traits[$trait]['traits'] as $fqdn) {
                            $definitions[$fqdn] = $this->traits[$fqdn];
                        }
                    }
                }
            };
            $collect(array_keys($definitions), $collect);
        }
        return $definitions;
    }
    /**
     * @template T extends OA\AbstractAnnotation
     *
     * @param class-string<T>|list<class-string<T>> $classes one or more class names
     * @param bool                                  $strict  in non-strict mode child classes are also detected
     *
     * @return list<T>
     */
    public function get_annotations_of_type($classes, bool $strict = false): array
    {
        $unique = new \Spl_Object_Storage();
        $annotations = [];
        foreach ((array) $classes as $class) {
            /** @var OA\AbstractAnnotation $annotation */
            foreach ($this->annotations as $annotation) {
                if ($annotation instanceof $class && (!$strict || $annotation->is_root($class) && !$unique->offsetExists($annotation))) {
                    $unique->offsetSet($annotation);
                    $annotations[] = $annotation;
                }
            }
        }
        return $annotations;
    }
    /**
     * @template T of OA\AbstractAnnotation
     *
     * @param  string          $fqdn        the source class/interface/trait
     * @param  class-string<T> $sourceClass
     * @return T|null
     */
    public function get_annotation_for_source(string $fqdn, string $source_class = OA\Schema::class): ?OA\Abstract_Annotation
    {
        $fqdn = '\\' . ltrim($fqdn, '\\');
        foreach ([$this->classes, $this->interfaces, $this->traits, $this->enums] as $definitions) {
            if (array_key_exists($fqdn, $definitions)) {
                $definition = $definitions[$fqdn];
                if (is_iterable($definition['context']->annotations)) {
                    /** @var OA\AbstractAnnotation $annotation */
                    foreach (array_reverse($definition['context']->annotations) as $annotation) {
                        if ($annotation instanceof $source_class && $annotation->is_root($source_class) && !$annotation->_context->is('generated')) {
                            return $annotation;
                        }
                    }
                }
            }
        }
        return null;
    }
    /**
     * Build an analysis with only the annotations that are merged into the OpenAPI annotation.
     */
    public function merged(): Analysis
    {
        if (!$this->openapi instanceof OA\Open_Api) {
            throw new Open_Api_Exception('No openapi target set. Run the MergeIntoOpenApi processor');
        }
        $unmerged = $this->openapi->_unmerged;
        $this->openapi->_unmerged = [];
        $analysis = new Analysis([$this->openapi], $this->context);
        $this->openapi->_unmerged = $unmerged;
        return $analysis;
    }
    /**
     * Analysis with only the annotations that not merged.
     */
    public function unmerged(): Analysis
    {
        return $this->split()->unmerged;
    }
    /**
     * Split the annotation into two analysis.
     * One with annotations that are merged and one with annotations that are not merged.
     *
     * @return \stdClass {merged: Analysis, unmerged: Analysis}
     */
    public function split(): \stdClass
    {
        $result = new \stdClass();
        $result->merged = $this->merged();
        $result->unmerged = new Analysis([], $this->context);
        foreach ($this->annotations as $annotation) {
            if ($result->merged->annotations->offsetExists($annotation) === false) {
                $result->unmerged->annotations->offsetSet($annotation, $this->annotations[$annotation]);
            }
        }
        return $result;
    }
    public function validate(): bool
    {
        if (!$this->openapi instanceof OA\Open_Api) {
            $this->context->logger->warning('No openapi target set. Run the MergeIntoOpenApi processor before validate()');
            return false;
        }
        $is_valid = true;
        $version = $this->openapi->openapi;
        $context = new \stdClass();
        foreach ($this->collect_annotations($this->openapi) as $annotation) {
            $is_valid = $annotation->validate($this, $version, $context) && $is_valid;
        }
        return $is_valid;
    }
    /**
     * @return array<OA\AbstractAnnotation>
     */
    protected function collect_annotations(OA\Abstract_Annotation $root): array
    {
        $annotations = [$root];
        foreach (get_object_vars($root) as $field => $value) {
            if (null === $value) {
                continue;
            }
            if (Generator::is_default($value)) {
                continue;
            }
            if (is_scalar($value)) {
                continue;
            }
            if (in_array($field, $root::$_blacklist)) {
                continue;
            }
            if ($value instanceof OA\Abstract_Annotation) {
                $annotations = array_merge($annotations, $this->collect_annotations($value));
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    if ($item instanceof OA\Abstract_Annotation) {
                        $annotations = array_merge($annotations, $this->collect_annotations($item));
                    }
                }
            }
        }
        return $annotations;
    }
}