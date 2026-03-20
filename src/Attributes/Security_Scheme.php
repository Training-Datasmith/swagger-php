<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Security_Scheme extends OA\Security_Scheme
{
    /**
     * @param string|class-string|object|null     $ref
     * @param string|non-empty-array<string>|null $type
     * @param list<Flow>                          $flows
     * @param array<string,mixed>|null            $x
     * @param list<Attachable>|null               $attachables
     */
    public function __construct(
        string|object|null $ref = null,
        ?string $security_scheme = null,
        string|array|null $type = null,
        ?string $description = Generator::UNDEFINED,
        ?string $name = null,
        ?string $in = null,
        ?string $bearer_format = null,
        ?string $scheme = null,
        ?string $open_id_connect_url = null,
        ?array $flows = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['ref' => $ref ?? Generator::UNDEFINED, 'securityScheme' => $security_scheme ?? Generator::UNDEFINED, 'type' => $type ?? Generator::UNDEFINED, 'description' => $description, 'name' => $name ?? Generator::UNDEFINED, 'in' => $in ?? Generator::UNDEFINED, 'bearerFormat' => $bearer_format ?? Generator::UNDEFINED, 'scheme' => $scheme ?? Generator::UNDEFINED, 'openIdConnectUrl' => $open_id_connect_url ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED, 'value' => $this->combine($flows)]);
    }
}