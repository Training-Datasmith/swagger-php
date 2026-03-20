<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class Path_Parameter extends Parameter
{
    /**
     * @inheritdoc
     */
    public $in = 'path';
    /**
     * @inheritdoc
     */
    public $required = true;
}