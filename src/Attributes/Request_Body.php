<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class Request_Body extends OA\Request_Body
{
    /**
     * @param string|class-string|object|null                                                          $ref
     * @param array<MediaType|JsonContent|XmlContent>|MediaType|JsonContent|XmlContent|Attachable|null $content
     * @param array<string,mixed>|null                                                                 $x
     * @param list<Attachable>|null                                                                    $attachables
     */
    public function __construct(
        string|object|null $ref = null,
        ?string $request = null,
        ?string $description = Generator::UNDEFINED,
        ?bool $required = null,
        array|Media_Type|Json_Content|Xml_Content|Attachable|null $content = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['ref' => $ref ?? Generator::UNDEFINED, 'request' => $request ?? Generator::UNDEFINED, 'description' => $description, 'required' => $required ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED, 'value' => $this->combine($content)]);
    }
}