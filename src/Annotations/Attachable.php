<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

/**
 * A container for custom data to be attached to an annotation.
 *
 * These will be ignored by `swagger-php` but can be used for custom processing.
 *
 * @Annotation
 */
class Attachable extends Abstract_Annotation
{
    /**
     * @inheritdoc
     */
    public static $_parents = [Additional_Properties::class, Components::class, Contact::class, Delete::class, Discriminator::class, Encoding::class, Examples::class, External_Documentation::class, Flow::class, Get::class, Head::class, Header::class, Info::class, Items::class, Json_Content::class, License::class, Link::class, Media_Type::class, Open_Api::class, Operation::class, Options::class, Parameter::class, Patch::class, Path_Item::class, Path_Parameter::class, Post::class, Property::class, Put::class, Request_Body::class, Response::class, Schema::class, Security_Scheme::class, Server::class, Server_Variable::class, Tag::class, Trace::class, Webhook::class, Xml::class, Xml_Content::class];
    /**
     * Allows to type-hint a specific parent annotation class.
     *
     * Container to allow custom annotations that are limited to a subset of potential parent
     * annotation classes.
     *
     * @return array<class-string>|null List of valid parent annotation classes. If <code>null</code>, the default nesting rules apply.
     */
    public function allowed_parents(): ?array
    {
        return null;
    }
}