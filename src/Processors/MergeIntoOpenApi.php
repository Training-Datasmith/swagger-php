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
 * Merge all <code>@OA\OpenApi</code> annotations into one.
 */
class Merge_Into_Open_Api
{
    public function __construct(protected bool $merge_components = false)
    {
    }
    public function is_merge_components(): bool
    {
        return $this->merge_components;
    }
    /**
     *  If set to <code>true</code> allow multiple `@OA\Components` annotations to be merged.
     */
    public function set_merge_components(bool $merge_components): Merge_Into_Open_Api
    {
        $this->merge_components = $merge_components;
        return $this;
    }
    public function __invoke(Analysis $analysis): void
    {
        // Auto-create the OpenApi annotation.
        if (!$analysis->openapi) {
            $context = new Context(['generated' => true], $analysis->context);
            $analysis->add_annotation(new OA\Open_Api(['_context' => $context]), $context);
        }
        $openapi = $analysis->openapi;
        $openapi->_analysis = $analysis;
        // Merge annotations into the target openapi
        $merge = [];
        /** @var OA\AbstractAnnotation $annotation */
        foreach ($analysis->annotations as $annotation) {
            if ($annotation === $openapi) {
                continue;
            }
            if ($annotation instanceof OA\Open_Api) {
                $paths = $annotation->paths;
                unset($annotation->paths);
                $openapi->merge_properties($annotation);
                if (!Generator::is_default($paths)) {
                    foreach ($paths as $path) {
                        if (Generator::is_default($openapi->paths)) {
                            $openapi->paths = [];
                        }
                        $openapi->paths[] = $path;
                    }
                }
            } elseif ($annotation instanceof OA\Abstract_Annotation && in_array(OA\Open_Api::class, $annotation::$_parents) && false === $annotation->_context->is('nested')) {
                // A top-level annotation.
                $merge[] = $annotation;
            }
        }
        if ($this->is_merge_components()) {
            // merge Components
            $components_list = array_filter($merge, static fn(OA\Abstract_Annotation $annotation): bool => $annotation instanceof OA\Components);
            $first_components = $openapi->components;
            if (!Generator::is_default($first_components) && $components_list !== [] || count($merge) > 1) {
                if (Generator::is_default($first_components)) {
                    $first_components = array_shift($components_list);
                }
                foreach ($components_list as $components) {
                    foreach (OA\Components::$_nested as $nested) {
                        if (2 == count($nested)) {
                            $property = $nested[0];
                            if (!Generator::is_default($components->{$property})) {
                                $first_components->merge($components->{$property});
                            }
                        }
                    }
                    $analysis->annotations->offsetUnset($components);
                }
                $merge = array_filter($merge, static fn(OA\Abstract_Annotation $annotation): bool => !$annotation instanceof OA\Components);
                $merge[] = $first_components;
            }
        }
        $openapi->merge($merge, true);
    }
}