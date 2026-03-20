<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS)]
class Open_Api extends OA\Open_Api
{
    /**
     * @param list<Server>|null        $servers
     * @param list<Tag>|null           $tags
     * @param array<PathItem>|null     $paths
     * @param list<Webhook>|null       $webhooks
     * @param array<string,mixed>|null $x
     * @param list<Attachable>|null    $attachables
     */
    public function __construct(
        string $openapi = self::DEFAULT_VERSION,
        ?Info $info = null,
        ?array $servers = null,
        ?array $security = null,
        ?array $tags = null,
        ?External_Documentation $external_docs = null,
        ?array $paths = null,
        ?Components $components = null,
        ?array $webhooks = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['openapi' => $openapi, 'security' => $security ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED, 'value' => $this->combine($info, $servers, $tags, $external_docs, $paths, $components, $webhooks)]);
    }
}