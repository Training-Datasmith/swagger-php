<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

/**
 * @Annotation
 */
class Put extends Operation
{
    /**
     * @inheritdoc
     */
    public $method = 'put';
    /**
     * @inheritdoc
     */
    public static $_parents = [Path_Item::class];
}