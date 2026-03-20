<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Context;
use Open_Api\Generator;
/**
 * Use the Schema context to extract useful information and inject that into the annotation.
 *
 * Merges properties.
 */
class Augment_Schemas
{
    public function __invoke(Analysis $analysis): void
    {
        $schemas = $analysis->get_annotations_of_type(OA\Schema::class);
        $this->augment_schema($schemas);
        $this->merge_unmerged_properties($analysis);
        $this->augment_type($analysis, $schemas);
        $this->merge_all_of($analysis, $schemas);
    }
    /**
     * @param array<OA\Schema> $schemas
     */
    protected function augment_schema(array $schemas): void
    {
        foreach ($schemas as $schema) {
            if (!$schema->is_root(OA\Schema::class)) {
                continue;
            }
            if (Generator::is_default($schema->schema)) {
                if ($schema->_context->is('class')) {
                    $schema->schema = $schema->_context->class;
                } elseif ($schema->_context->is('interface')) {
                    $schema->schema = $schema->_context->interface;
                } elseif ($schema->_context->is('trait')) {
                    $schema->schema = $schema->_context->trait;
                } elseif ($schema->_context->is('enum')) {
                    $schema->schema = $schema->_context->enum;
                }
            }
        }
    }
    /**
     * Merge unmerged @OA\Property annotations into the @OA\Schema of the class.
     */
    protected function merge_unmerged_properties(Analysis $analysis): void
    {
        // Merge unmerged @OA\Property annotations into the @OA\Schema of the class
        $unmerged_properties = $analysis->unmerged()->get_annotations_of_type(OA\Property::class);
        foreach ($unmerged_properties as $property) {
            if ($property->_context->nested) {
                continue;
            }
            $schema_context = (($property->_context->with('class') ?: $property->_context->with('interface')) ?: $property->_context->with('trait')) ?: $property->_context->with('enum');
            if ($schema_context->annotations) {
                foreach ($schema_context->annotations as $annotation) {
                    if ($annotation instanceof OA\Schema) {
                        if ($annotation->_context->nested) {
                            // we shouldn't merge property into nested schemas
                            continue;
                        }
                        $annotation->merge([$property], true);
                        break;
                    }
                }
            }
        }
    }
    /**
     * Set schema type based on various properties.
     *
     * @param array<OA\Schema> $schemas
     */
    protected function augment_type(Analysis $analysis, array $schemas): void
    {
        foreach ($schemas as $schema) {
            if (Generator::is_default($schema->type)) {
                if (is_array($schema->properties) && $schema->properties !== []) {
                    $schema->type = 'object';
                } elseif (is_array($schema->additional_properties) && $schema->additional_properties !== []) {
                    $schema->type = 'object';
                } elseif (is_array($schema->pattern_properties) && $schema->pattern_properties !== []) {
                    $schema->type = 'object';
                } elseif (is_array($schema->unevaluated_properties) && $schema->unevaluated_properties !== []) {
                    $schema->type = 'object';
                } elseif (is_array($schema->property_names) && $schema->property_names !== []) {
                    $schema->type = 'object';
                }
            } else if (is_string($schema->type) && $type_schema = $analysis->get_annotation_for_source($schema->type)) {
                if (Generator::is_default($schema->format)) {
                    $schema->ref = OA\Components::ref($type_schema);
                    $schema->type = Generator::UNDEFINED;
                }
            }
        }
    }
    /**
     * Merge schema properties into <code>allOf</code> if both exist.
     *
     * @param array<OA\Schema> $schemas
     */
    protected function merge_all_of(Analysis $analysis, array $schemas): void
    {
        foreach ($schemas as $schema) {
            if (!Generator::is_default($schema->properties) && !Generator::is_default($schema->all_of)) {
                $all_of_properties_schema = null;
                foreach ($schema->all_of as $all_of_schema) {
                    if (!Generator::is_default($all_of_schema->properties)) {
                        $all_of_properties_schema = $all_of_schema;
                        break;
                    }
                }
                if (!$all_of_properties_schema) {
                    $all_of_properties_schema = new OA\Schema(['properties' => [], 'type' => 'object', '_context' => new Context(['generated' => true], $schema->_context)]);
                    $analysis->add_annotation($all_of_properties_schema, $all_of_properties_schema->_context);
                    $schema->all_of[] = $all_of_properties_schema;
                }
                $all_of_properties_schema->properties = array_merge($all_of_properties_schema->properties, $schema->properties);
                /* @phpstan-ignore assign.propertyType */
                $schema->properties = Generator::UNDEFINED;
            }
        }
    }
}