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
 * Split JsonContent into Schema and MediaType.
 */
class Merge_Json_Content
{
    public function __invoke(Analysis $analysis): void
    {
        $annotations = $analysis->get_annotations_of_type(OA\Json_Content::class);
        foreach ($annotations as $json_content) {
            $parent = $json_content->_context->nested;
            if (!$parent instanceof OA\Response && !$parent instanceof OA\Request_Body && !$parent instanceof OA\Parameter) {
                if ($parent) {
                    $json_content->_context->logger->warning('Unexpected ' . $json_content->identity() . ' in ' . $parent->identity() . ' in ' . $parent->_context);
                } else {
                    $json_content->_context->logger->warning('Unexpected ' . $json_content->identity() . ' must be nested');
                }
                continue;
            }
            if (Generator::is_default($parent->content)) {
                $parent->content = [];
            }
            $parent->content['application/json'] = $media_type = new OA\Media_Type(['schema' => $json_content, 'example' => $json_content->example, 'examples' => $json_content->examples, 'encoding' => $json_content->encoding, '_context' => new Context(['generated' => true], $json_content->_context)]);
            $analysis->add_annotation($media_type, $media_type->_context);
            if (!$parent instanceof OA\Parameter) {
                $parent->content['application/json']->media_type = 'application/json';
            }
            $json_content->example = Generator::UNDEFINED;
            $json_content->examples = Generator::UNDEFINED;
            /* @phpstan-ignore assign.propertyType */
            $json_content->encoding = Generator::UNDEFINED;
            $index = array_search($json_content, $parent->_unmerged, true);
            if ($index !== false) {
                array_splice($parent->_unmerged, $index, 1);
            }
        }
    }
}