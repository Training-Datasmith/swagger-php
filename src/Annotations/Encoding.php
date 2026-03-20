<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Generator;
/**
 * A single encoding definition applied to a single schema property.
 *
 * @see [Encoding Object](https://spec.openapis.org/oas/v3.1.0.html#encoding-object)
 *
 * @Annotation
 */
class Encoding extends Abstract_Annotation
{
    /**
     * The property name to which the encoding applies.
     *
     * @var string
     */
    public $property = Generator::UNDEFINED;
    /**
     * The content type.
     *
     * @var string
     */
    public $content_type = Generator::UNDEFINED;
    /**
     * Additional headers.
     *
     * @var list<Header>
     */
    public $headers = Generator::UNDEFINED;
    /**
     * @var string
     */
    public $style = Generator::UNDEFINED;
    /**
     * @var bool
     */
    public $explode = Generator::UNDEFINED;
    /**
     * @var bool
     */
    public $allow_reserved = Generator::UNDEFINED;
    /**
     * @inheritdoc
     */
    public static $_parents = [Json_Content::class, Xml_Content::class, Media_Type::class, Property::class];
    /**
     * @inheritdoc
     */
    public static $_nested = [Header::class => ['headers', 'header'], Attachable::class => ['attachables']];
    /**
     * @inheritdoc
     */
    public static $_types = ['contentType' => 'string'];
    public function jsonSerialize(): \stdClass
    {
        $data = parent::jsonSerialize();
        unset($data->property);
        return $data;
    }
}