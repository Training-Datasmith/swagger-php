<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS)]
class Discriminator extends OA\Discriminator
{
    /**
     * @param array<string,string>|null $mapping
     * @param array<string,mixed>|null  $x
     * @param list<Attachable>|null     $attachables
     */
    public function __construct(
        ?string $property_name = null,
        ?array $mapping = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['propertyName' => $property_name ?? Generator::UNDEFINED, 'mapping' => $mapping ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED]);
    }
}