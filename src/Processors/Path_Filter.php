<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors;

use Open_Api\Analysis;
use Open_Api\Generator;
use Open_Api\Processors\Concerns\Annotation_Trait;
/**
 * Allows to filter endpoints based on tags and/or path.
 *
 * If no <code>tags</code> or <code>paths</code> filters are set, no filtering is performed.
 *
 * All filter (regular) expressions must be enclosed within delimiter characters as they are used as-is.
 */
class Path_Filter
{
    use Annotation_Trait;
    public function __construct(protected array $tags = [], protected array $paths = [], protected bool $recurse_cleanup = false)
    {
    }
    public function get_tags(): array
    {
        return $this->tags;
    }
    /**
     * A list of regular expressions to match <code>tags</code> to include.
     *
     * @param array<string> $tags
     */
    public function set_tags(array $tags): Path_Filter
    {
        $this->tags = $tags;
        return $this;
    }
    public function get_paths(): array
    {
        return $this->paths;
    }
    /**
     * A list of regular expressions to match <code>paths</code> to include.
     *
     * @param array<string> $paths
     */
    public function set_paths(array $paths): Path_Filter
    {
        $this->paths = $paths;
        return $this;
    }
    public function is_recurse_cleanup(): bool
    {
        return $this->recurse_cleanup;
    }
    /**
     * Flag to do a recursive cleanup of unused paths and their nested annotations.
     */
    public function set_recurse_cleanup(bool $recurse_cleanup): void
    {
        $this->recurse_cleanup = $recurse_cleanup;
    }
    public function __invoke(Analysis $analysis): void
    {
        if (($this->tags || $this->paths) && !Generator::is_default($analysis->openapi->paths)) {
            $filtered = [];
            foreach ($analysis->openapi->paths as $path_item) {
                $matched = null;
                foreach ($this->tags as $pattern) {
                    foreach ($path_item->operations() as $operation) {
                        if (!Generator::is_default($operation->tags)) {
                            foreach ($operation->tags as $tag) {
                                if (preg_match($pattern, $tag)) {
                                    $matched = $path_item;
                                    break 3;
                                }
                            }
                        }
                    }
                }
                foreach ($this->paths as $pattern) {
                    if (preg_match($pattern, $path_item->path)) {
                        $matched = $path_item;
                        break;
                    }
                }
                if ($matched) {
                    $filtered[] = $matched;
                } else {
                    $this->remove_annotation($analysis->annotations, $path_item, $this->recurse_cleanup);
                }
            }
            $analysis->openapi->paths = $filtered;
        }
    }
}