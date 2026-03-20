<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Examples extends OA\Examples
{
    /**
     * @param string|class-string|object|null $ref
     * @param array<string,mixed>|null        $x
     * @param list<Attachable>|null           $attachables
     */
    public function __construct(
        ?string $example = null,
        ?string $summary = Generator::UNDEFINED,
        ?string $description = Generator::UNDEFINED,
        int|string|array|null $value = null,
        ?string $external_value = null,
        string|object|null $ref = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['example' => $example ?? Generator::UNDEFINED, 'summary' => $summary, 'description' => $description, 'value' => $value ?? Generator::UNDEFINED, 'externalValue' => $external_value ?? Generator::UNDEFINED, 'ref' => $ref ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED]);
    }
}