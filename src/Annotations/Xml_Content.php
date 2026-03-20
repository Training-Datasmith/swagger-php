<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
/**
 * Shorthand for a xml response.
 *
 * Use as <code>@OA\Schema</code> inside a <code>Response</code> and <code>MediaType</code>-><code>'application/xml'</code> will be generated.
 *
 * @Annotation
 */
class Xml_Content extends Schema
{
    /**
     * A map between a property name and its encoding information.
     *
     * @var list<Encoding>
     */
    public $encoding = Generator::UNDEFINED;
    /**
     * @inheritdoc
     */
    public static $_parents = [];
    /**
     * @inheritdoc
     */
    public static $_nested = [Discriminator::class => 'discriminator', Items::class => 'items', Property::class => ['properties', 'property'], External_Documentation::class => 'externalDocs', Xml::class => 'xml', Additional_Properties::class => 'additionalProperties', Encoding::class => ['encoding', 'property'], Examples::class => ['examples', 'example'], Attachable::class => ['attachables']];
}