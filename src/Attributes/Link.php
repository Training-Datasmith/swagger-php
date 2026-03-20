<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Link extends OA\Link
{
    /**
     * @param string|class-string|object|null $ref
     * @param array<string,mixed>             $parameters
     * @param array<string,mixed>|null        $x
     * @param list<Attachable>|null           $attachables
     */
    public function __construct(
        ?string $link = null,
        ?string $operation_ref = null,
        string|object|null $ref = null,
        ?string $operation_id = null,
        ?array $parameters = null,
        mixed $request_body = null,
        ?string $description = Generator::UNDEFINED,
        ?Server $server = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['link' => $link ?? Generator::UNDEFINED, 'operationRef' => $operation_ref ?? Generator::UNDEFINED, 'ref' => $ref ?? Generator::UNDEFINED, 'operationId' => $operation_id ?? Generator::UNDEFINED, 'parameters' => $parameters ?? Generator::UNDEFINED, 'requestBody' => $request_body ?? Generator::UNDEFINED, 'description' => $description, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED, 'value' => $this->combine($server)]);
    }
}