<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Generator;
/**
 * Look at all (direct) interfaces for a schema and:
 * - merge interfaces annotations/methods into the schema if the interface does not have a schema itself
 * - inherit from the interface if it has a schema (allOf).
 */
class Expand_Interfaces
{
    use Concerns\Merge_Properties_Trait;
    public function __invoke(Analysis $analysis): void
    {
        $schemas = $analysis->get_annotations_of_type(OA\Schema::class, true);
        foreach ($schemas as $schema) {
            if ($schema->_context->is('class')) {
                $class_name = $schema->_context->fully_qualified_name($schema->_context->class);
                $interfaces = $analysis->get_interfaces_of_class($class_name, true);
                if (class_exists($class_name) && ($parent = get_parent_class($class_name)) && $inherited = array_keys(class_implements($parent))) {
                    // strip interfaces we inherit from ancestor
                    foreach (array_keys($interfaces) as $interface) {
                        if (in_array(ltrim((string) $interface, '\\'), $inherited)) {
                            unset($interfaces[$interface]);
                        }
                    }
                }
                $existing = [];
                foreach ($interfaces as $interface) {
                    $interface_name = $interface['context']->fully_qualified_name($interface['interface']);
                    $interface_schema = $analysis->get_annotation_for_source($interface_name);
                    if ($interface_schema) {
                        $ref_path = Generator::is_default($interface_schema->schema) ? $interface['interface'] : $interface_schema->schema;
                        $this->inherit_from($analysis, $schema, $interface_schema, $ref_path, $interface['context']);
                    } else {
                        $this->merge_methods($schema, $interface, $existing);
                    }
                }
            }
        }
    }
}