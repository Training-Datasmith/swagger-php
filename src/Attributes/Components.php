<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS)]
class Components extends OA\Components
{
    /**
     * @param array<Schema|OA\Schema>|null $schemas
     * @param list<Response>|null          $responses
     * @param list<Parameter>|null         $parameters
     * @param list<RequestBody>|null       $requestBodies
     * @param array<Examples>|null         $examples
     * @param list<Header>|null            $headers
     * @param list<SecurityScheme>|null    $securitySchemes
     * @param list<Link>|null              $links
     * @param array<string,mixed>|null     $x
     * @param list<Attachable>|null        $attachables
     */
    public function __construct(
        ?array $schemas = null,
        ?array $responses = null,
        ?array $parameters = null,
        ?array $request_bodies = null,
        ?array $examples = null,
        ?array $headers = null,
        ?array $security_schemes = null,
        ?array $links = null,
        ?array $callbacks = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['callbacks' => $callbacks ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED, 'value' => $this->combine($schemas, $responses, $parameters, $examples, $request_bodies, $headers, $security_schemes, $links)]);
    }
}