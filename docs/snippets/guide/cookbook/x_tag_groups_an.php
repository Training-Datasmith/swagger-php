<?php

declare(strict_types=1);

namespace Openapi\Snippets\Cookbook\XTagGroups;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     x={
 *         "tagGroups": {{"name": "User Management", "tags": {"Users", "API keys", "Admin"}}
 *         }
 *     }
 * )
 */
class OpenApiSpec
{
}
