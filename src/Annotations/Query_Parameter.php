<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Annotations as OA;
/**
 * A <code>@OA\Request</code> query parameter.
 *
 * @Annotation
 */
class Query_Parameter extends Parameter
{
    /**
     * @inheritdoc
     * This takes 'query' as the default location.
     */
    public $in = 'query';
}