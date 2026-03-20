<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Generator;
/**
 * @Annotation
 */
class Property extends Schema
{
    /**
     * The key into Schema->properties array.
     *
     * @var string
     */
    public $property = Generator::UNDEFINED;
    /**
     * @var Encoding
     */
    public $encoding = Generator::UNDEFINED;
    /**
     * @inheritdoc
     */
    public static $_parents = [Additional_Properties::class, Schema::class, Json_Content::class, Xml_Content::class, Property::class, Items::class];
    /**
     * @inheritdoc
     */
    public static $_nested = [Discriminator::class => 'discriminator', Items::class => 'items', Property::class => ['properties', 'property'], External_Documentation::class => 'externalDocs', Xml::class => 'xml', Additional_Properties::class => 'additionalProperties', Encoding::class => 'encoding', Attachable::class => ['attachables']];
    public function jsonSerialize(): \stdClass
    {
        $data = parent::jsonSerialize();
        unset($data->encoding);
        return $data;
    }
}