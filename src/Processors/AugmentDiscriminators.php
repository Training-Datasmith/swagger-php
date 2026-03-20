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
 * Use the property context to extract useful information and inject that into the annotation.
 */
class Augment_Discriminators
{
    public function __invoke(Analysis $analysis): void
    {
        $discriminators = $analysis->get_annotations_of_type(OA\Discriminator::class);
        foreach ($discriminators as $discriminator) {
            if (!Generator::is_default($discriminator->mapping)) {
                foreach ($discriminator->mapping as $value => $type) {
                    if (is_string($type) && $type_schema = $analysis->get_annotation_for_source($type)) {
                        $discriminator->mapping[$value] = OA\Components::ref($type_schema);
                    }
                }
            }
        }
    }
}