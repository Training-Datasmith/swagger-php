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
 * Merge reusable annotation into <code>@OA\Schemas</code>.
 */
class Merge_Into_Components
{
    public function __invoke(Analysis $analysis): void
    {
        $components = $analysis->openapi->components;
        if (Generator::is_default($components)) {
            $components = new OA\Components(['_context' => new Context(['generated' => true], $analysis->context)]);
        }
        /** @var OA\AbstractAnnotation $annotation */
        foreach ($analysis->annotations as $annotation) {
            if ($annotation instanceof OA\Abstract_Annotation && in_array(OA\Components::class, $annotation::$_parents) && false === $annotation->_context->is('nested')) {
                // A top level annotation.
                $components->merge([$annotation], true);
                $analysis->openapi->components = $components;
            }
        }
    }
}