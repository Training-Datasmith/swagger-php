<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Generator;
class Augment_Refs
{
    use Concerns\Ref_Trait;
    public function __invoke(Analysis $analysis): void
    {
        $this->resolve_all_of_refs($analysis);
        $this->resolve_fqcn_refs($analysis);
        $this->remove_duplicate_refs($analysis);
    }
    /**
     * Update refs broken due to <code>allOf</code> augmenting.
     */
    protected function resolve_all_of_refs(Analysis $analysis): void
    {
        $schemas = $analysis->get_annotations_of_type(OA\Schema::class);
        // ref rewriting
        $updated_refs = [];
        foreach ($schemas as $schema) {
            if (!Generator::is_default($schema->all_of)) {
                // do we have to keep track of property refs that need updating?
                foreach ($schema->all_of as $ii => $all_of_schema) {
                    if (!Generator::is_default($all_of_schema->properties)) {
                        $updated_refs[OA\Components::ref($schema->schema . '/properties', false)] = OA\Components::ref($schema->schema . '/allOf/' . $ii . '/properties', false);
                        break;
                    }
                }
            }
        }
        if ($updated_refs) {
            foreach ($analysis->annotations as $annotation) {
                if (property_exists($annotation, 'ref') && !Generator::is_default($annotation->ref) && $annotation->ref !== null) {
                    foreach ($updated_refs as $orig_ref => $updated_ref) {
                        if (str_starts_with((string) $annotation->ref, $orig_ref)) {
                            $annotation->ref = str_replace($orig_ref, $updated_ref, (string) $annotation->ref);
                        }
                    }
                }
            }
        }
    }
    protected function resolve_fqcn_refs(Analysis $analysis): void
    {
        $annotations = $analysis->get_annotations_of_type(OA\Components::component_types());
        foreach ($annotations as $annotation) {
            if (property_exists($annotation, 'ref') && !Generator::is_default($annotation->ref) && is_string($annotation->ref) && !$this->is_ref($annotation->ref)) {
                // check if we can resolve the ref to a component
                $resolved = false;
                foreach (OA\Components::component_types() as $type) {
                    if ($ref_schema = $analysis->get_annotation_for_source($annotation->ref, $type)) {
                        $resolved = true;
                        $annotation->ref = OA\Components::ref($ref_schema);
                    }
                }
                if (!$resolved && $ref_annotation = $analysis->get_annotation_for_source($annotation->ref, $annotation::class)) {
                    $annotation->ref = OA\Components::ref($ref_annotation);
                }
            }
        }
    }
    protected function remove_duplicate_refs(Analysis $analysis): void
    {
        $schemas = $analysis->get_annotations_of_type(OA\Schema::class);
        foreach ($schemas as $schema) {
            if (!Generator::is_default($schema->all_of)) {
                $refs = [];
                $dupes = [];
                foreach ($schema->all_of as $ii => $all_of_schema) {
                    if (!Generator::is_default($all_of_schema->ref)) {
                        if (in_array($all_of_schema->ref, $refs)) {
                            $dupes[] = $all_of_schema->ref;
                            $analysis->annotations->offsetUnset($all_of_schema);
                            unset($schema->all_of[$ii]);
                            continue;
                        }
                        $refs[] = $all_of_schema->ref;
                    }
                }
                if ($dupes) {
                    $schema->all_of = array_values($schema->all_of);
                }
            }
        }
    }
}