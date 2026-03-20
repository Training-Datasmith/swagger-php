<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Generator;
use Open_Api\Open_Api_Exception;
/**
 * Augment media type encodings.
 */
class Augment_Media_Type
{
    public function __invoke(Analysis $analysis): void
    {
        $media_types = $analysis->get_annotations_of_type(OA\Media_Type::class);
        foreach ($media_types as $media_type) {
            $schema = $media_type->schema;
            if ($schema instanceof OA\Schema) {
                if (!Generator::is_default($schema->properties)) {
                    $this->merge_property_encodings($media_type, $schema->properties);
                } elseif (!Generator::is_default($schema->ref)) {
                    try {
                        $ref_schema = $analysis->openapi->ref($schema->ref);
                    } catch (Open_Api_Exception) {
                        // ignore
                        $ref_schema = null;
                    }
                    if ($ref_schema instanceof OA\Schema && !Generator::is_default($ref_schema->properties)) {
                        $this->merge_property_encodings($media_type, $ref_schema->properties);
                    }
                }
            }
        }
    }
    /**
     * @param array<OA\Property> $properties
     */
    protected function merge_property_encodings(OA\Media_Type $media_type, array $properties): void
    {
        foreach ($properties as $property) {
            if ($property->encoding instanceof OA\Encoding) {
                $media_type->merge([$property->encoding], true);
            }
        }
    }
}