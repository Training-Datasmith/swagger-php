<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

/**
 * @Annotation
 */
class Patch extends Operation
{
    /**
     * @inheritdoc
     */
    public $method = 'patch';
    /**
     * @inheritdoc
     */
    public static $_parents = [Path_Item::class];
}