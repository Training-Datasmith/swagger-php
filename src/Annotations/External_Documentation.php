<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Generator;
/**
 * Allows referencing an external resource for extended documentation.
 *
 * @see [External Documentation Object](https://spec.openapis.org/oas/v3.1.1.html#external-documentation-object)
 *
 * @Annotation
 */
class External_Documentation extends Abstract_Annotation
{
    /**
     * A short description of the target documentation. GFM syntax can be used for rich text representation.
     *
     * @var string
     */
    public $description = Generator::UNDEFINED;
    /**
     * The URL for the target documentation.
     *
     * @var string
     */
    public $url = Generator::UNDEFINED;
    /**
     * @inheritdoc
     */
    public static $_types = ['description' => 'string', 'url' => 'string'];
    /**
     * @inheritdoc
     */
    public static $_required = ['url'];
    /**
     * @inheritdoc
     */
    public static $_parents = [Open_Api::class, Tag::class, Schema::class, Additional_Properties::class, Property::class, Operation::class, Get::class, Post::class, Put::class, Delete::class, Patch::class, Head::class, Options::class, Trace::class, Items::class, Json_Content::class, Xml_Content::class];
    /**
     * @inheritdoc
     */
    public static $_nested = [Attachable::class => ['attachables']];
}