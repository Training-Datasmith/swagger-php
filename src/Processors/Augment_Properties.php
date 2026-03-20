<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Context;
use Open_Api\Generator;
use Open_Api\Generator_Aware_Interface;
use Open_Api\Generator_Aware_Trait;
/**
 * Use the property context to extract useful information and inject that into the annotation.
 */
class Augment_Properties implements Generator_Aware_Interface
{
    use Concerns\Docblock_Trait;
    use Concerns\Ref_Trait;
    use Generator_Aware_Trait;
    public function __invoke(Analysis $analysis): void
    {
        $properties = $analysis->get_annotations_of_type(OA\Property::class);
        foreach ($properties as $property) {
            $context = $property->_context;
            $reflector = $context->reflector;
            if (Generator::is_default($property->property)) {
                $property->property = $property->_context->property;
            }
            if ($property->encoding instanceof OA\Encoding) {
                $property->encoding->property = $property->property;
            }
            if (Generator::is_default($property->const) && $reflector instanceof \Reflection_Class_Constant) {
                $property->const = $reflector->get_value();
            }
            if (Generator::is_default($property->description)) {
                $type_and_description = $this->parse_var_line((string) $context->comment);
                if ($type_and_description['description']) {
                    $property->description = trim($type_and_description['description']);
                } elseif ($this->is_docblock_root($property)) {
                    $property->description = $this->parse_docblock($context->comment);
                }
            } elseif (null === $property->description) {
                $property->description = Generator::UNDEFINED;
            }
            if (!Generator::is_default($property->ref)) {
                continue;
            }
            if (Generator::is_default($property->type)) {
                $this->generator->get_type_resolver()->augment_schema_type($analysis, $property);
            }
            $this->generator->get_type_resolver()->map_native_type($property, $property->type);
            if (Generator::is_default($property->example) && $example = $this->extract_example_description((string) $context->comment)) {
                $property->example = $example;
            }
            if (Generator::is_default($property->deprecated) && $deprecated = $this->is_deprecated($context->comment)) {
                $property->deprecated = $deprecated;
            }
        }
    }
}