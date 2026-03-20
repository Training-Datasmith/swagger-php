<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors\Concerns;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Context;
use Open_Api\Generator;
/**
 * Steps:
 * 1. Determine direct parent / interfaces / traits
 * 2. With each:
 *    - traverse up inheritance tree
 *      - inherit from first with schema; all other with scheme can be ignored
 *      - merge from all without schema
 *        => update all $ref that might reference a property merged.
 */
trait Merge_Properties_Trait
{
    protected function inherit_from(Analysis $analysis, OA\Schema $schema, OA\Schema $from, string $ref_path, Context $context): void
    {
        if (Generator::is_default($schema->all_of)) {
            $schema->all_of = [];
        }
        // merging other properties into allOf is done in the AugmentSchemas processor
        $schema->all_of[] = $ref_schema = new OA\Schema(['ref' => OA\Components::ref($ref_path), '_context' => new Context(['generated' => true], $context)]);
        $analysis->add_annotation($ref_schema, $ref_schema->_context);
    }
    protected function merge_properties(OA\Schema $schema, array $from, array &$existing): void
    {
        foreach ($from['properties'] as $context) {
            if (is_iterable($context->annotations)) {
                foreach ($context->annotations as $annotation) {
                    if ($annotation instanceof OA\Property && !in_array($annotation->_context->property, $existing, strict: true)) {
                        $existing[] = $annotation->_context->property;
                        $schema->merge([$annotation], true);
                    }
                }
            }
        }
    }
    protected function merge_methods(OA\Schema $schema, array $from, array &$existing): void
    {
        foreach ($from['methods'] as $context) {
            if (is_iterable($context->annotations)) {
                foreach ($context->annotations as $annotation) {
                    if ($annotation instanceof OA\Property && !in_array($annotation->_context->property, $existing, strict: true)) {
                        $existing[] = $annotation->_context->property;
                        $schema->merge([$annotation], true);
                    }
                }
            }
        }
    }
}