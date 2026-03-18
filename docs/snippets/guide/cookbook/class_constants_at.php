<?php

declare(strict_types=1);

namespace Openapi\Snippets\Cookbook\ClassConstants;

use OpenApi\Attributes as OA;

#[OA\Schema]
class Airport
{
    #[OA\Property(property: 'kind')]
    public const KIND = 'Airport';
}
