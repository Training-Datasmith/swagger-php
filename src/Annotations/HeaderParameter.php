<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Annotations as OA;
/**
 * A <code>@OA\Request</code> header parameter.
 *
 * @Annotation
 */
class Header_Parameter extends Parameter
{
    /**
     * @inheritdoc
     * This takes 'header' as the default location.
     */
    public $in = 'header';
}