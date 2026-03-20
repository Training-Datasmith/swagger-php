<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

/**
 * @since OpenAPI 3.2.0
 *
 * @Annotation
 */
class Query extends Operation
{
    /**
     * @inheritdoc
     */
    public $method = 'query';
    /**
     * @inheritdoc
     */
    public static $_parents = [Path_Item::class];
}