<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

/**
 * @Annotation
 */
class Additional_Properties extends Schema
{
    /**
     * @inheritdoc
     */
    public static $_parents = [Schema::class, Property::class, Items::class, Json_Content::class, Xml_Content::class, Additional_Properties::class];
    /**
     * @inheritdoc
     */
    public static $_nested = [Discriminator::class => 'discriminator', Items::class => 'items', Property::class => ['properties', 'property'], External_Documentation::class => 'externalDocs', Xml::class => 'xml', Additional_Properties::class => 'additionalProperties', Attachable::class => ['attachables']];
}