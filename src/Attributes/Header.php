<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Header extends OA\Header
{
    /**
     * @param string|class-string|object|null $ref
     * @param array<string,mixed>|null        $x
     * @param list<Attachable>|null           $attachables
     */
    public function __construct(
        string|object|null $ref = null,
        ?string $header = null,
        ?string $description = Generator::UNDEFINED,
        ?bool $required = null,
        ?Schema $schema = null,
        ?bool $deprecated = null,
        ?bool $allow_empty_value = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['ref' => $ref ?? Generator::UNDEFINED, 'header' => $header ?? Generator::UNDEFINED, 'description' => $description, 'required' => $required ?? Generator::UNDEFINED, 'deprecated' => $deprecated ?? Generator::UNDEFINED, 'allowEmptyValue' => $allow_empty_value ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED, 'value' => $this->combine($schema)]);
    }
}