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
 * Ensures that all tags used on operations also exist in the global <code>tags</code> list.
 */
class Augment_Tags
{
    public function __construct(
        /** @var array<string> */
        protected array $whitelist = [],
        protected bool $with_description = true
    )
    {
    }
    /**
     * Whitelist tags to keep even if not used. <code>*</code> may be used to keep all unused.
     */
    public function set_whitelist(array $whitelist): Augment_Tags
    {
        $this->whitelist = $whitelist;
        return $this;
    }
    /**
     * Enables/disables generation of default tag descriptions.
     */
    public function set_with_description(bool $with_description): Augment_Tags
    {
        $this->with_description = $with_description;
        return $this;
    }
    public function __invoke(Analysis $analysis): void
    {
        $operations = $analysis->get_annotations_of_type(OA\Operation::class);
        $used_tag_names = [];
        foreach ($operations as $operation) {
            if (!Generator::is_default($operation->tags)) {
                $used_tag_names = array_merge($used_tag_names, $operation->tags);
            }
        }
        $used_tag_names = array_unique($used_tag_names);
        $declared_tags = [];
        if (!Generator::is_default($analysis->openapi->tags)) {
            foreach ($analysis->openapi->tags as $tag) {
                $declared_tags[$tag->name] = $tag;
            }
        }
        if ($declared_tags) {
            // last one wins
            $analysis->openapi->tags = array_values($declared_tags);
        }
        // Add a tag for each tag that is used in operations but not declared in the global tags
        if ($used_tag_names) {
            $declated_tag_names = array_keys($declared_tags);
            foreach ($used_tag_names as $tag_name) {
                if (!in_array($tag_name, $declated_tag_names)) {
                    $analysis->openapi->merge([new OA\Tag(['name' => $tag_name, 'description' => $this->with_description ? $tag_name : Generator::UNDEFINED])]);
                }
            }
        }
        // clear invalid parents
        foreach ($declared_tags as $tag) {
            if (!array_key_exists($tag->parent, $declared_tags)) {
                $tag->parent = Generator::UNDEFINED;
            }
        }
        $this->remove_unused_tags($used_tag_names, $declared_tags, $analysis);
    }
    private function remove_unused_tags(array $used_tag_names, array $declared_tags, Analysis $analysis): void
    {
        if (in_array('*', $this->whitelist)) {
            return;
        }
        $tags_to_keep = array_merge($used_tag_names, $this->whitelist);
        foreach ($declared_tags as $tag) {
            if (in_array($tag->name, $tags_to_keep)) {
                continue;
            }
            if (false === $index = array_search($tag, $analysis->openapi->tags, true)) {
                continue;
            }
            $analysis->annotations->offsetUnset($tag);
            unset($analysis->openapi->tags[$index]);
            $analysis->openapi->tags = array_values($analysis->openapi->tags);
        }
    }
}