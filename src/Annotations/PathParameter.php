<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Annotations as OA;
/**
 * A <code>@OA\Request</code> path parameter.
 *
 * @Annotation
 */
class Path_Parameter extends Parameter
{
    /**
     * @inheritdoc
     * This takes 'path' as the default location.
     */
    public $in = 'path';
    /**
     * @inheritdoc
     */
    public $required = true;
    /**
     * @inheritdoc
     */
    public static $_required = ['name'];
}