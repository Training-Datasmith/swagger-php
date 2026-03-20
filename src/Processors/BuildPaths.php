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
 * Build the openapi->paths using the detected <code>@OA\PathItem</code> and <code>@OA\Operation</code> (<code>@OA\Get</code>, etc).
 */
class Build_Paths
{
    public function __invoke(Analysis $analysis): void
    {
        $paths = [];
        // Merge @OA\PathItems with the same path.
        if (!Generator::is_default($analysis->openapi->paths)) {
            foreach ($analysis->openapi->paths as $annotation) {
                if (empty($annotation->path)) {
                    $annotation->_context->logger->warning($annotation->identity() . ' is missing required property "path" in ' . $annotation->_context);
                } elseif (isset($paths[$annotation->path])) {
                    $paths[$annotation->path]->merge_properties($annotation);
                    $analysis->annotations->offsetUnset($annotation);
                } else {
                    $paths[$annotation->path] = $annotation;
                }
            }
        }
        $operations = $analysis->unmerged()->get_annotations_of_type(OA\Operation::class);
        // Merge @OA\Operations into existing @OA\PathItems or create a new one.
        foreach ($operations as $operation) {
            if ($operation->path) {
                if (empty($paths[$operation->path])) {
                    $paths[$operation->path] = $path_item = new OA\Path_Item(['path' => $operation->path, '_context' => new Context(['generated' => true], $operation->_context)]);
                    $analysis->add_annotation($path_item, $path_item->_context);
                }
                if ($paths[$operation->path]->merge([$operation])) {
                    $operation->_context->logger->warning('Unable to merge ' . $operation->identity() . ' in ' . $operation->_context);
                }
            }
        }
        if ($paths) {
            $analysis->openapi->paths = array_values($paths);
        }
    }
}