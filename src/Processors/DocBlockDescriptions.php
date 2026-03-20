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
 * Checks if the annotation has a summary and/or description property
 * and uses the text in the comment block (above the annotations) as summary and/or description.
 *
 * Use <code>null</code>, for example <code>@Annotation(description=null)</code>,
 * if you don't want the annotation to have a description.
 */
class Doc_Block_Descriptions
{
    use Concerns\Docblock_Trait;
    public function __invoke(Analysis $analysis): void
    {
        /** @var OA\AbstractAnnotation $annotation */
        foreach ($analysis->annotations as $annotation) {
            if (property_exists($annotation, '_context') === false) {
                // only annotations with context
                continue;
            }
            if (!$this->is_docblock_root($annotation)) {
                // only top-level annotations
                continue;
            }
            if ($annotation instanceof OA\Parameter) {
                // they have their dedicated processor
                continue;
            }
            if ($annotation instanceof OA\Property) {
                // they have their dedicated processor
                continue;
            }
            $has_summary = property_exists($annotation, 'summary');
            $has_description = property_exists($annotation, 'description');
            if (!$has_summary && !$has_description) {
                continue;
            }
            if ($has_summary && $has_description) {
                $this->summary_and_description($annotation);
            } elseif ($has_description) {
                $this->description($annotation);
            }
        }
    }
    /**
     * @param OA\Operation|OA\Property|OA\Parameter|OA\Schema $annotation
     */
    protected function description(OA\Abstract_Annotation $annotation): void
    {
        if (!Generator::is_default($annotation->description)) {
            if ($annotation->description === null) {
                $annotation->description = Generator::UNDEFINED;
            }
            return;
        }
        $annotation->description = $this->parse_docblock($annotation->_context->comment);
    }
    /**
     * @param OA\Operation|OA\Property|OA\Parameter|OA\Schema $annotation
     */
    protected function summary_and_description(OA\Abstract_Annotation $annotation): void
    {
        $ignore_summary = !Generator::is_default($annotation->summary);
        $ignore_description = !Generator::is_default($annotation->description);
        if ($annotation->summary === null) {
            $ignore_summary = true;
            $annotation->summary = Generator::UNDEFINED;
        }
        if ($annotation->description === null) {
            $annotation->description = Generator::UNDEFINED;
            $ignore_description = true;
        }
        if ($ignore_summary && $ignore_description) {
            return;
        }
        $content = $this->parse_docblock($annotation->_context->comment);
        if ($ignore_summary) {
            $annotation->description = $content;
        } elseif ($ignore_description) {
            $annotation->summary = $content;
        } else {
            $annotation->summary = $this->extract_comment_summary($content);
            $annotation->description = $this->extract_comment_description($content);
        }
    }
}