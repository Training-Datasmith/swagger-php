<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Generator;
/**
 * @see [Tag Object](https://spec.openapis.org/oas/v3.1.1.html#tag-object)
 *
 * @Annotation
 */
class Tag extends Abstract_Annotation
{
    /**
     * The name of the tag.
     *
     * @var string
     */
    public $name = Generator::UNDEFINED;
    /**
     * A short description for the tag. GFM syntax can be used for rich text representation.
     *
     * @var string
     */
    public $description = Generator::UNDEFINED;
    /**
     * A short summary for display purposes.
     *
     * @since OpenAPI 3.2.0
     * @var string
     */
    public $summary = Generator::UNDEFINED;
    /**
     * Additional external documentation for this tag.
     *
     * @var ExternalDocumentation
     */
    public $external_docs = Generator::UNDEFINED;
    /**
     * Name of the parent tag.
     *
     * @since OpenAPI 3.2.0
     * @var string
     */
    public $parent = Generator::UNDEFINED;
    /**
     * Machine-readable category.
     *
     * @since OpenAPI 3.2.0
     * @var string
     */
    public $kind = Generator::UNDEFINED;
    /**
     * @inheritdoc
     */
    public static $_required = ['name'];
    /**
     * @inheritdoc
     */
    public static $_types = ['name' => 'string', 'description' => 'string', 'summary' => 'string', 'parent' => 'string', 'kind' => 'string'];
    /**
     * @inheritdoc
     */
    public static $_parents = [Open_Api::class];
    /**
     * @inheritdoc
     */
    public static $_nested = [External_Documentation::class => 'externalDocs', Attachable::class => ['attachables']];
    public function jsonSerialize(): \stdClass
    {
        $data = parent::jsonSerialize();
        if ($this->_context->is_version(['3.0.x', '3.1.x'])) {
            unset($data->summary);
            unset($data->parent);
            unset($data->kind);
        }
        return $data;
    }
}