<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS)]
class Flow extends OA\Flow
{
    /**
     * @param 'implicit'|'password'|'authorizationCode'|'clientCredentials'|null $flow
     * @param array<string,mixed>|null                                           $x
     * @param list<Attachable>|null                                              $attachables
     */
    public function __construct(
        ?string $authorization_url = null,
        ?string $token_url = null,
        ?string $refresh_url = null,
        ?string $flow = null,
        ?array $scopes = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['authorizationUrl' => $authorization_url ?? Generator::UNDEFINED, 'tokenUrl' => $token_url ?? Generator::UNDEFINED, 'refreshUrl' => $refresh_url ?? Generator::UNDEFINED, 'flow' => $flow ?? Generator::UNDEFINED, 'scopes' => $scopes ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED]);
    }
}