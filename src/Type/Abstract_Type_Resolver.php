<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Type;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Generator;
use Open_Api\Type_Resolver_Interface;
abstract class Abstract_Type_Resolver implements Type_Resolver_Interface
{
    protected function type2ref(OA\Schema $schema, Analysis $analysis, string $source_class = OA\Schema::class): void
    {
        if (!Generator::is_default($schema->type) && !is_array($schema->type)) {
            if ($type_schema = $analysis->get_annotation_for_source($schema->type, $source_class)) {
                $schema->type = Generator::UNDEFINED;
                $schema->ref = OA\Components::ref($type_schema);
            }
        }
    }
    /**
     * @param string|array $type
     */
    public function map_native_type(OA\Schema $schema, $type): bool
    {
        if (is_array($type)) {
            $mapped = [];
            foreach ($type as $t) {
                $mapped[] = $this->native2spec(strtolower((string) $t));
            }
            $schema->type = $mapped;
            return true;
        }
        $type = strtolower($type);
        if (!array_key_exists($type, Type_Resolver_Interface::NATIVE_TYPE_MAP)) {
            return false;
        }
        $type = Type_Resolver_Interface::NATIVE_TYPE_MAP[$type];
        if (is_array($type)) {
            if (Generator::is_default($schema->format)) {
                $schema->format = $type[1];
            }
            $type = $type[0];
        }
        $schema->type = $type;
        return true;
    }
    public function native2spec(string $type): string
    {
        $mapped = array_key_exists($type, Type_Resolver_Interface::NATIVE_TYPE_MAP) ? Type_Resolver_Interface::NATIVE_TYPE_MAP[$type] : $type;
        return is_array($mapped) ? $mapped[0] : $mapped;
    }
    public function augment_schema_type(Analysis $analysis, OA\Schema $schema, string $source_class = OA\Schema::class): void
    {
        $context = $schema->_context;
        if (null === $context->reflector || $context->nested) {
            return;
        }
        /* @phpstan-ignore argument.type */
        $this->do_augment($analysis, $schema, $context->reflector, $source_class);
        $this->map_native_type($schema, $schema->type);
    }
    /**
     * @param \ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector
     */
    abstract protected function do_augment(Analysis $analysis, OA\Schema $schema, \Reflector $reflector, string $source_class = OA\Schema::class): void;
}