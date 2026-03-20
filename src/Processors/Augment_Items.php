<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Context;
/**
 * Use the Schema context to extract useful information and inject that into the annotation.
 *
 * Merges properties.
 */
class Augment_Items
{
    public function __invoke(Analysis $analysis): void
    {
        $schemas = $analysis->get_annotations_of_type(OA\Schema::class);
        foreach ($schemas as $schema) {
            if ($schema->items instanceof OA\Items) {
                $schema->type = 'array';
            }
        }
    }
}