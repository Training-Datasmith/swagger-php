<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors\Concerns;

use Open_Api\Annotations as OA;
trait Annotation_Trait
{
    /**
     * Collects a (complete) list of all nested/referenced annotations starting from the given root.
     *
     * @param string|array|iterable|OA\AbstractAnnotation $root
     */
    public function collect_annotations($root): \Spl_Object_Storage
    {
        $storage = new \Spl_Object_Storage();
        $this->traverse_annotations($root, static function ($item) use (&$storage): void {
            if ($item instanceof OA\Abstract_Annotation && !$storage->offsetExists($item)) {
                $storage->offsetSet($item);
            }
        });
        return $storage;
    }
    /**
     * Remove all annotations that are part of the <code>$annotation</code> tree.
     */
    public function remove_annotation(iterable $root, OA\Abstract_Annotation $annotation, bool $recurse = true): void
    {
        $remove = $this->collect_annotations($annotation);
        $this->traverse_annotations($root, static function ($item) use ($remove): void {
            if ($item instanceof \Spl_Object_Storage) {
                foreach ($remove as $annotation) {
                    $item->offsetUnset($annotation);
                }
            }
        }, $recurse);
    }
    /**
     * @param string|array|iterable|OA\AbstractAnnotation $root
     */
    public function traverse_annotations($root, callable $callable, bool $recurse = true): void
    {
        $callable($root);
        if (is_iterable($root) && $recurse) {
            foreach ($root as $value) {
                $this->traverse_annotations($value, $callable, $recurse);
            }
        } elseif ($root instanceof OA\Abstract_Annotation) {
            foreach (array_merge($root::$_nested, ['allOf', 'anyOf', 'oneOf', 'callbacks']) as $properties) {
                foreach ((array) $properties as $property) {
                    if (isset($root->{$property})) {
                        $this->traverse_annotations($root->{$property}, $callable, $recurse);
                    }
                }
            }
        }
    }
}