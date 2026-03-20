<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
/**
 * Shorthand for a json response.
 *
 * Example:
 * ```php
 * #[OA\JsonContent(
 *     ref: '#/components/schemas/user'
 * )]
 * ```
 * vs.
 * ```php
 * #[OA\MediaType(
 *     mediaType: 'application/json',
 *     schema: new OA\Schema(
 *         ref: '#/components/schemas/user'
 *     )
 * )
 * ```
 *
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Json_Content extends OA\Json_Content
{
    /**
     * @param list<Encoding>                                               $encoding
     * @param string|class-string|object|null                              $ref
     * @param list<string>                                                 $required
     * @param list<Property>                                               $properties
     * @param string|non-empty-array<string>|null                          $type
     * @param array<Examples>                                              $examples
     * @param array<Schema|OA\Schema>                                      $allOf
     * @param array<Schema|OA\Schema>                                      $anyOf
     * @param array<Schema|OA\Schema>                                      $oneOf
     * @param list<string|int|float|bool|\UnitEnum|null>|class-string|null $enum
     * @param array<string,mixed>|null                                     $x
     * @param list<Attachable>|null                                        $attachables
     */
    public function __construct(
        ?array $encoding = null,
        // Schema
        string|object|null $ref = null,
        ?string $schema = null,
        ?string $title = null,
        ?string $description = Generator::UNDEFINED,
        ?int $max_properties = null,
        ?int $min_properties = null,
        ?array $required = null,
        ?array $properties = null,
        string|array|null $type = null,
        ?string $format = null,
        ?Items $items = null,
        ?string $collection_format = null,
        ?string $pattern = null,
        ?Discriminator $discriminator = null,
        ?bool $read_only = null,
        ?bool $write_only = null,
        ?Xml $xml = null,
        ?External_Documentation $external_docs = null,
        mixed $example = Generator::UNDEFINED,
        ?array $examples = null,
        ?bool $nullable = null,
        ?bool $deprecated = null,
        ?array $all_of = null,
        ?array $any_of = null,
        ?array $one_of = null,
        ?string $content_encoding = null,
        ?string $content_media_type = null,
        // JSON Schema
        mixed $default = Generator::UNDEFINED,
        int|float|null $maximum = null,
        bool|int|float|null $exclusive_maximum = null,
        int|float|null $minimum = null,
        bool|int|float|null $exclusive_minimum = null,
        int|null $max_length = null,
        int|null $min_length = null,
        int|null $max_items = null,
        int|null $min_items = null,
        bool|null $unique_items = null,
        array|string|null $enum = null,
        mixed $not = Generator::UNDEFINED,
        bool|Additional_Properties|null $additional_properties = null,
        array|null $additional_items = null,
        array|null $contains = null,
        array|null $pattern_properties = null,
        array|null $unevaluated_properties = null,
        mixed $dependencies = Generator::UNDEFINED,
        mixed $property_names = Generator::UNDEFINED,
        mixed $const = Generator::UNDEFINED,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct([
            // Schema
            'ref' => $ref ?? Generator::UNDEFINED,
            'schema' => $schema ?? Generator::UNDEFINED,
            'title' => $title ?? Generator::UNDEFINED,
            'description' => $description,
            'maxProperties' => $max_properties ?? Generator::UNDEFINED,
            'minProperties' => $min_properties ?? Generator::UNDEFINED,
            'required' => $required ?? Generator::UNDEFINED,
            'properties' => $properties ?? Generator::UNDEFINED,
            'type' => $type ?? Generator::UNDEFINED,
            'format' => $format ?? Generator::UNDEFINED,
            'collectionFormat' => $collection_format ?? Generator::UNDEFINED,
            'pattern' => $pattern ?? Generator::UNDEFINED,
            'readOnly' => $read_only ?? Generator::UNDEFINED,
            'writeOnly' => $write_only ?? Generator::UNDEFINED,
            'xml' => $xml ?? Generator::UNDEFINED,
            'example' => $example,
            'nullable' => $nullable ?? Generator::UNDEFINED,
            'deprecated' => $deprecated ?? Generator::UNDEFINED,
            'allOf' => $all_of ?? Generator::UNDEFINED,
            'anyOf' => $any_of ?? Generator::UNDEFINED,
            'oneOf' => $one_of ?? Generator::UNDEFINED,
            'contentEncoding' => $content_encoding ?? Generator::UNDEFINED,
            'contentMediaType' => $content_media_type ?? Generator::UNDEFINED,
            // JSON Schema
            'default' => $default,
            'maximum' => $maximum ?? Generator::UNDEFINED,
            'exclusiveMaximum' => $exclusive_maximum ?? Generator::UNDEFINED,
            'minimum' => $minimum ?? Generator::UNDEFINED,
            'exclusiveMinimum' => $exclusive_minimum ?? Generator::UNDEFINED,
            'maxLength' => $max_length ?? Generator::UNDEFINED,
            'minLength' => $min_length ?? Generator::UNDEFINED,
            'maxItems' => $max_items ?? Generator::UNDEFINED,
            'minItems' => $min_items ?? Generator::UNDEFINED,
            'uniqueItems' => $unique_items ?? Generator::UNDEFINED,
            'enum' => $enum ?? Generator::UNDEFINED,
            'not' => $not,
            'additionalProperties' => $additional_properties ?? Generator::UNDEFINED,
            'additionalItems' => $additional_items ?? Generator::UNDEFINED,
            'contains' => $contains ?? Generator::UNDEFINED,
            'patternProperties' => $pattern_properties ?? Generator::UNDEFINED,
            'unevaluatedProperties' => $unevaluated_properties ?? Generator::UNDEFINED,
            'dependencies' => $dependencies,
            'propertyNames' => $property_names,
            'const' => $const,
            // abstract annotation
            'x' => $x ?? Generator::UNDEFINED,
            'attachables' => $attachables ?? Generator::UNDEFINED,
            'value' => $this->combine($items, $discriminator, $external_docs, $examples, $encoding),
        ]);
    }
}