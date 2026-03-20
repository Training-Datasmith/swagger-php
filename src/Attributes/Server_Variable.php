<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS)]
class Server_Variable extends OA\Server_Variable
{
    /**
     * @param list<string|int|float|bool|\UnitEnum|null>|class-string|null $enum
     * @param array<string,mixed>|null                                     $x
     * @param list<Attachable>|null                                        $attachables
     */
    public function __construct(
        ?string $server_variable = null,
        ?string $description = Generator::UNDEFINED,
        ?string $default = null,
        array|string|null $enum = null,
        ?array $variables = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['serverVariable' => $server_variable ?? Generator::UNDEFINED, 'description' => $description, 'default' => $default ?? Generator::UNDEFINED, 'enum' => $enum ?? Generator::UNDEFINED, 'variables' => $variables ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED]);
    }
}