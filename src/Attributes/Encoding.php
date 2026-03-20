<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS)]
class Encoding extends OA\Encoding
{
    /**
     * @param list<Header>             $headers
     * @param array<string,mixed>|null $x
     * @param list<Attachable>|null    $attachables
     */
    public function __construct(
        ?string $property = null,
        ?string $content_type = null,
        ?array $headers = null,
        ?string $style = null,
        ?bool $explode = null,
        ?bool $allow_reserved = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['property' => $property ?? Generator::UNDEFINED, 'contentType' => $content_type ?? Generator::UNDEFINED, 'style' => $style ?? Generator::UNDEFINED, 'explode' => $explode ?? Generator::UNDEFINED, 'allowReserved' => $allow_reserved ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED, 'value' => $this->combine($headers)]);
    }
}