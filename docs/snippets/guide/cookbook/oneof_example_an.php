<?php

declare(strict_types=1);

namespace Openapi\Snippets\Cookbook\OneOf;

use OpenApi\Annotations as OA;

class Controller
{
    /**
     * @OA\Response(
     *     response=200,
     *     @OA\JsonContent(
     *         oneOf={
     *             @OA\Schema(ref="#/components/schemas/QualificationHolder"),
     *             @OA\Schema(
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/QualificationHolder")
     *             )
     *         }
     *     )
     * )
     */
    public function index()
    {
        // ...
    }
}
