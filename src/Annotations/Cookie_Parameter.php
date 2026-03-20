<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

/**
 * A <code>@OA\Request</code> cookie parameter.
 *
 * @Annotation
 */
class Cookie_Parameter extends Parameter
{
    /**
     * @inheritdoc
     * This takes 'cookie' as the default location.
     */
    public $in = 'cookie';
}