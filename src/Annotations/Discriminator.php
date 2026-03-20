<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Generator;
/**
 * The discriminator is a specific object in a schema which is used to inform the consumer of
 * the specification of an alternative schema based on the value associated with it.
 *
 * This object is based on the [JSON Schema Specification](http://json-schema.org) and uses a predefined subset of it.
 * On top of this subset, there are extensions provided by this specification to allow for more complete documentation.
 *
 * @see [Discriminator Object](https://spec.openapis.org/oas/v3.1.1.html#discriminator-object)
 * @see [JSON Schema](http://json-schema.org/)
 *
 * @Annotation
 */
class Discriminator extends Abstract_Annotation
{
    /**
     * The name of the property in the payload that will hold the discriminator value.
     *
     * @var string
     */
    public $property_name = Generator::UNDEFINED;
    /**
     * An object to hold mappings between payload values and schema names or references.
     *
     * @var array<string,string>
     */
    public $mapping = Generator::UNDEFINED;
    /**
     * @inheritdoc
     */
    public static $_required = ['propertyName'];
    /**
     * @inheritdoc
     */
    public static $_types = ['propertyName' => 'string'];
    /**
     * @inheritdoc
     */
    public static $_parents = [Schema::class, Property::class, Additional_Properties::class, Items::class, Json_Content::class, Xml_Content::class];
    /**
     * @inheritdoc
     */
    public static $_nested = [Attachable::class => ['attachables']];
}