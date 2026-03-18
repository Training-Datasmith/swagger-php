<?php

declare(strict_types=1);

namespace Openapi\Snippets\Cookbook\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema]
enum State
{
    case OPEN;
    case MERGED;
    case DECLINED;
}

#[OA\Schema]
class PullRequest
{
    #[OA\Property]
    public State $state;
}
