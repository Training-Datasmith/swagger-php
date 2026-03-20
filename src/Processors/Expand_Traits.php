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
 * Look at all (direct) traits for a schema and:
 * - merge trait annotations/methods/properties into the schema if the trait does not have a schema itself
 * - inherit from the trait if it has a schema (allOf).
 */
class Expand_Traits
{
    use Concerns\Merge_Properties_Trait;
    public function __invoke(Analysis $analysis): void
    {
        $schemas = $analysis->get_annotations_of_type(OA\Schema::class, true);
        // do regular trait inheritance / merge
        foreach ($schemas as $schema) {
            if ($schema->_context->is('trait')) {
                $traits = $analysis->get_traits_of_class($schema->_context->fully_qualified_name($schema->_context->trait), true);
                $existing = [];
                foreach ($traits as $trait) {
                    $trait_schema = $analysis->get_annotation_for_source($trait['context']->fully_qualified_name($trait['trait']));
                    if ($trait_schema) {
                        $ref_path = Generator::is_default($trait_schema->schema) ? $trait['trait'] : $trait_schema->schema;
                        $this->inherit_from($analysis, $schema, $trait_schema, $ref_path, $trait['context']);
                    } else {
                        $this->merge_methods($schema, $trait, $existing);
                        $this->merge_properties($schema, $trait, $existing);
                    }
                }
            }
        }
        foreach ($schemas as $schema) {
            if ($schema->_context->is('class') && !$schema->_context->is('generated')) {
                // look at class traits
                $traits = $analysis->get_traits_of_class($schema->_context->fully_qualified_name($schema->_context->class), true);
                $existing = [];
                foreach ($traits as $trait) {
                    $trait_schema = $analysis->get_annotation_for_source($trait['context']->fully_qualified_name($trait['trait']));
                    if ($trait_schema) {
                        $ref_path = Generator::is_default($trait_schema->schema) ? $trait['trait'] : $trait_schema->schema;
                        $this->inherit_from($analysis, $schema, $trait_schema, $ref_path, $trait['context']);
                    } else {
                        $this->merge_methods($schema, $trait, $existing);
                        $this->merge_properties($schema, $trait, $existing);
                    }
                }
                // also merge ancestor traits of non schema parents
                $ancestors = $analysis->get_super_classes($schema->_context->fully_qualified_name($schema->_context->class));
                $existing = [];
                foreach ($ancestors as $ancestor) {
                    $ancestor_schema = $analysis->get_annotation_for_source($ancestor['context']->fully_qualified_name($ancestor['class']));
                    if ($ancestor_schema) {
                        // stop here as we inherit everything above
                        break;
                    } else {
                        $traits = $analysis->get_traits_of_class($schema->_context->fully_qualified_name($ancestor['class']), true);
                        foreach ($traits as $trait) {
                            $this->merge_methods($schema, $trait, $existing);
                            $this->merge_properties($schema, $trait, $existing);
                        }
                    }
                }
            }
        }
    }
}