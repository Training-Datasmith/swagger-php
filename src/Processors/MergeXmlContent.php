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
 * Split XmlContent into Schema and MediaType.
 */
class Merge_Xml_Content
{
    public function __invoke(Analysis $analysis): void
    {
        $annotations = $analysis->get_annotations_of_type(OA\Xml_Content::class);
        foreach ($annotations as $xml_content) {
            $parent = $xml_content->_context->nested;
            if (!$parent instanceof OA\Response && !$parent instanceof OA\Request_Body && !$parent instanceof OA\Parameter) {
                if ($parent) {
                    $xml_content->_context->logger->warning('Unexpected ' . $xml_content->identity() . ' in ' . $parent->identity() . ' in ' . $parent->_context);
                } else {
                    $xml_content->_context->logger->warning('Unexpected ' . $xml_content->identity() . ' must be nested');
                }
                continue;
            }
            if (Generator::is_default($parent->content)) {
                $parent->content = [];
            }
            $parent->content['application/xml'] = $media_type = new OA\Media_Type(['schema' => $xml_content, 'example' => $xml_content->example, 'examples' => $xml_content->examples, '_context' => new Context(['generated' => true], $xml_content->_context)]);
            $analysis->add_annotation($media_type, $media_type->_context);
            if (!$parent instanceof OA\Parameter) {
                $parent->content['application/xml']->media_type = 'application/xml';
            }
            $xml_content->example = Generator::UNDEFINED;
            $xml_content->examples = Generator::UNDEFINED;
            $index = array_search($xml_content, $parent->_unmerged, true);
            if ($index !== false) {
                array_splice($parent->_unmerged, $index, 1);
            }
        }
    }
}