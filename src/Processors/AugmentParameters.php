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
use Open_Api\Processors\Concerns\Docblock_Trait;
/**
 * Augments shared and operations parameters from docblock comments.
 */
class Augment_Parameters implements Generator_Aware_Interface
{
    use Docblock_Trait;
    use Generator_Aware_Trait;
    public function __construct(protected bool $augment_operation_parameters = true)
    {
    }
    public function is_augment_operation_parameters(): bool
    {
        return $this->augment_operation_parameters;
    }
    /**
     * If set to <code>true</code> try to find operation parameter descriptions in the operation docblock.
     */
    public function set_augment_operation_parameters(bool $augment_operation_parameters): Augment_Parameters
    {
        $this->augment_operation_parameters = $augment_operation_parameters;
        return $this;
    }
    public function __invoke(Analysis $analysis): void
    {
        $this->augment_parameters($analysis);
        $this->augment_shared_parameters($analysis);
        if ($this->augment_operation_parameters) {
            $this->augment_operation_parameters($analysis);
        }
    }
    protected function augment_parameters(Analysis $analysis): void
    {
        $parameters = $analysis->get_annotations_of_type(OA\Parameter::class);
        foreach ($parameters as $parameter) {
            $context = $parameter->_context;
            if (Generator::is_default($parameter->name) && null !== $context->reflector && method_exists($context->reflector, 'getName')) {
                $parameter->name = $context->reflector->get_name();
            }
            if ($context->reflector instanceof \ReflectionParameter) {
                $schema = Generator::is_default($parameter->schema) ? new OA\Schema(['_context' => new Context(['generated' => true, 'reflector' => $context->reflector], $context)]) : $parameter->schema;
                $this->generator->get_type_resolver()->augment_schema_type($analysis, $schema);
                $parameter->merge([new OA\Schema(['type' => $schema->type, 'format' => $schema->format, 'items' => $schema->items, 'oneOf' => $schema->one_of, 'allOf' => $schema->all_of, 'anyOf' => $schema->any_of, 'ref' => $schema->ref, '_context' => new Context(['nested' => $this, 'comment' => null, 'reflector' => $context->reflector], $context)])]);
                if (Generator::is_default($parameter->required)) {
                    $parameter->required = !$schema->is_nullable();
                }
            }
            if (!Generator::is_default($parameter->schema)) {
                $this->generator->get_type_resolver()->map_native_type($parameter->schema, $parameter->schema->type);
            }
        }
    }
    /**
     * Use the parameter->name as key field (parameter->parameter) when used as reusable component
     * (openapi->components->parameters).
     */
    protected function augment_shared_parameters(Analysis $analysis): void
    {
        if (!Generator::is_default($analysis->openapi->components) && !Generator::is_default($analysis->openapi->components->parameters)) {
            $keys = [];
            $parameters_without_key = [];
            foreach ($analysis->openapi->components->parameters as $parameter) {
                if (!Generator::is_default($parameter->parameter)) {
                    $keys[$parameter->parameter] = $parameter;
                } else {
                    $parameters_without_key[] = $parameter;
                }
            }
            foreach ($parameters_without_key as $parameter) {
                if (!Generator::is_default($parameter->name) && empty($keys[$parameter->name])) {
                    $parameter->parameter = $parameter->name;
                    $keys[$parameter->parameter] = $parameter;
                }
            }
        }
    }
    protected function augment_operation_parameters(Analysis $analysis): void
    {
        $operations = $analysis->get_annotations_of_type(OA\Operation::class);
        foreach ($operations as $operation) {
            if (!Generator::is_default($operation->parameters)) {
                $tags = [];
                $this->parse_docblock($operation->_context->comment, $tags);
                $docblock_params = $tags['param'] ?? [];
                foreach ($operation->parameters as $parameter) {
                    if (Generator::is_default($parameter->description)) {
                        if (array_key_exists($parameter->name, $docblock_params)) {
                            $details = $docblock_params[$parameter->name];
                            if ($details['description']) {
                                $parameter->description = $details['description'];
                            }
                        }
                    } elseif (null === $parameter->description) {
                        $parameter->description = Generator::UNDEFINED;
                    }
                }
            }
        }
    }
}