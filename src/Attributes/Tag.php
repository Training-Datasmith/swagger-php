<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Tag extends OA\Tag
{
    /**
     * @param array<string,mixed>|null $x
     * @param list<Attachable>|null    $attachables
     */
    public function __construct(
        ?string $name = null,
        ?string $description = Generator::UNDEFINED,
        ?string $summary = Generator::UNDEFINED,
        ?string $parent = null,
        ?string $kind = null,
        ?External_Documentation $external_docs = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['name' => $name ?? Generator::UNDEFINED, 'description' => $description, 'summary' => $summary, 'parent' => $parent ?? Generator::UNDEFINED, 'kind' => $kind ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED, 'value' => $this->combine($external_docs)]);
    }
}