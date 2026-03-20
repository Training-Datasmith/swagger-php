<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Generator;
trait Operation_Trait
{
    /**
     * @param list<Server>             $servers
     * @param list<string>             $tags
     * @param list<Parameter>          $parameters
     * @param list<Response>           $responses
     * @param array<string,mixed>|null $x
     * @param list<Attachable>|null    $attachables
     */
    public function __construct(
        ?string $path = null,
        ?string $operation_id = null,
        ?string $description = Generator::UNDEFINED,
        ?string $summary = Generator::UNDEFINED,
        ?array $security = null,
        ?array $servers = null,
        ?Request_Body $request_body = null,
        ?array $tags = null,
        ?array $parameters = null,
        ?array $responses = null,
        ?array $callbacks = null,
        ?External_Documentation $external_docs = null,
        ?bool $deprecated = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['path' => $path ?? Generator::UNDEFINED, 'operationId' => $operation_id ?? Generator::UNDEFINED, 'description' => $description, 'summary' => $summary, 'security' => $security ?? Generator::UNDEFINED, 'servers' => $servers ?? Generator::UNDEFINED, 'tags' => $tags ?? Generator::UNDEFINED, 'callbacks' => $callbacks ?? Generator::UNDEFINED, 'deprecated' => $deprecated ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED, 'value' => $this->combine($request_body, $responses, $parameters, $external_docs)]);
    }
}