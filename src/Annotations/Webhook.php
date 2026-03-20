<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Generator;
/**
 * Acts like a <code>PathItem</code> with the main difference being that it requires <code>webhook</code> instead of <code>path</code>.
 *
 * @since OpenAPI 3.1.0
 *
 * @Annotation
 */
class Webhook extends Path_Item
{
    /**
     * Key for the webhooks map.
     *
     * @var string
     */
    public $webhook = Generator::UNDEFINED;
    /**
     * @inheritdoc
     */
    public static $_required = ['webhook'];
    /**
     * @inheritdoc
     */
    public static $_parents = [Open_Api::class];
    /**
     * @inheritdoc
     */
    public static $_types = ['webhook' => 'string'];
}