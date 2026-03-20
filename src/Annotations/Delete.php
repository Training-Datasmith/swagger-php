<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

/**
 * @Annotation
 */
class Delete extends Operation
{
    /**
     * @inheritdoc
     */
    public $method = 'delete';
    /**
     * @inheritdoc
     */
    public static $_parents = [Path_Item::class];
}