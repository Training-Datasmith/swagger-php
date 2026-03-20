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
 * Tracks the use of all <code>Components</code> and removed unused schemas.
 */
class Clean_Unused_Components
{
    use Concerns\Annotation_Trait;
    public function __construct(protected bool $enabled = false)
    {
    }
    public function is_enabled(): bool
    {
        return $this->enabled;
    }
    /**
     * Enables/disables the <code>CleanUnusedComponents</code> processor.
     */
    public function set_enabled(bool $enabled): Clean_Unused_Components
    {
        $this->enabled = $enabled;
        return $this;
    }
    public function __invoke(Analysis $analysis): void
    {
        if (!$this->enabled || Generator::is_default($analysis->openapi->components)) {
            return;
        }
        $analysis->annotations = $this->collect_annotations($analysis->annotations);
        // allow multiple runs to catch nested dependencies
        for ($ii = 0; $ii < 10; ++$ii) {
            if (!$this->cleanup($analysis)) {
                break;
            }
        }
    }
    protected function cleanup(Analysis $analysis): bool
    {
        $used_refs = [];
        foreach ($analysis->annotations as $annotation) {
            if (property_exists($annotation, 'ref') && !Generator::is_default($annotation->ref) && $annotation->ref !== null) {
                $used_refs[$annotation->ref] = $annotation->ref;
            }
            foreach (['allOf', 'anyOf', 'oneOf'] as $sub) {
                if (property_exists($annotation, $sub) && !Generator::is_default($annotation->{$sub})) {
                    foreach ($annotation->{$sub} as $sub_elem) {
                        if (is_object($sub_elem) && property_exists($sub_elem, 'ref') && !Generator::is_default($sub_elem->ref) && $sub_elem->ref !== null) {
                            $used_refs[$sub_elem->ref] = $sub_elem->ref;
                        }
                    }
                }
            }
            if ($annotation instanceof OA\Open_Api || $annotation instanceof OA\Operation) {
                if (!Generator::is_default($annotation->security)) {
                    foreach ($annotation->security as $security) {
                        foreach (array_keys($security) as $security_name) {
                            $ref = OA\Components::COMPONENTS_PREFIX . 'securitySchemes/' . $security_name;
                            $used_refs[$ref] = $ref;
                        }
                    }
                }
            }
        }
        $unused_refs = [];
        foreach (OA\Components::$_nested as $nested) {
            if (2 == count($nested)) {
                // $nested[1] is the name of the property that holds the component name
                [$component_type, $name_property] = $nested;
                if (!Generator::is_default($analysis->openapi->components->{$component_type})) {
                    foreach ($analysis->openapi->components->{$component_type} as $component) {
                        $ref = OA\Components::ref($component);
                        if (!in_array($ref, $used_refs)) {
                            $unused_refs[$ref] = [$ref, $name_property];
                        }
                    }
                }
            }
        }
        // remove unused
        foreach ($unused_refs as $ref_details) {
            [$ref, $name_property] = $ref_details;
            [$hash, $components, $component_type, $name] = explode('/', $ref);
            foreach ($analysis->openapi->components->{$component_type} as $ii => $component) {
                if ($component->{$name_property} == $name) {
                    $annotation = $analysis->openapi->components->{$component_type}[$ii];
                    $this->remove_annotation($analysis->annotations, $annotation);
                    unset($analysis->openapi->components->{$component_type}[$ii]);
                    if (!$analysis->openapi->components->{$component_type}) {
                        $analysis->openapi->components->{$component_type} = Generator::UNDEFINED;
                    }
                }
            }
        }
        return [] !== $unused_refs;
    }
}