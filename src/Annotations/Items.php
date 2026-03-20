<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

/**
 * The description of an item in a Schema with type <code>array</code>.
 *
 * @Annotation
 */
class Items extends Schema
{
    /**
     * @inheritdoc
     */
    public static $_nested = [Discriminator::class => 'discriminator', Items::class => 'items', Property::class => ['properties', 'property'], External_Documentation::class => 'externalDocs', Xml::class => 'xml', Additional_Properties::class => 'additionalProperties', Attachable::class => ['attachables']];
    /**
     * @inheritdoc
     */
    public static $_parents = [Property::class, Additional_Properties::class, Schema::class, Json_Content::class, Xml_Content::class, Items::class];
}