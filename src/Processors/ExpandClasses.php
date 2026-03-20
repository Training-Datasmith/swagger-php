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
 * Iterate over the chain of ancestors of a schema and:
 * - if the ancestor has a schema
 *   => inherit from the ancestor if it has a schema (allOf) and stop.
 * - else
 *   => merge ancestor properties into the schema.
 */
class Expand_Classes
{
    use Concerns\Merge_Properties_Trait;
    public function __invoke(Analysis $analysis): void
    {
        $schemas = $analysis->get_annotations_of_type(OA\Schema::class, true);
        foreach ($schemas as $schema) {
            if ($schema->_context->is('class')) {
                $ancestors = $analysis->get_super_classes($schema->_context->fully_qualified_name($schema->_context->class));
                $existing = [];
                foreach ($ancestors as $ancestor) {
                    $ancestor_schema = $analysis->get_annotation_for_source($ancestor['context']->fully_qualified_name($ancestor['class']));
                    if ($ancestor_schema) {
                        $ref_path = Generator::is_default($ancestor_schema->schema) ? $ancestor['class'] : $ancestor_schema->schema;
                        $this->inherit_from($analysis, $schema, $ancestor_schema, $ref_path, $ancestor['context']);
                        // one ancestor is enough
                        break;
                    } else {
                        $this->merge_methods($schema, $ancestor, $existing);
                        $this->merge_properties($schema, $ancestor, $existing);
                    }
                }
            }
        }
    }
}