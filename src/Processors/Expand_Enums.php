<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Generator;
use Open_Api\Generator_Aware_Interface;
use Open_Api\Generator_Aware_Trait;
use Open_Api\Open_Api_Exception;
/**
 * Expands PHP enums.
 *
 * Determines <code>schema</code>, <code>enum</code> and <code>type</code>.
 */
class Expand_Enums implements Generator_Aware_Interface
{
    use Generator_Aware_Trait;
    public function __construct(protected ?string $enum_names = null)
    {
    }
    public function get_enum_names(): ?string
    {
        return $this->enum_names;
    }
    /**
     * Specifies the name of the extension variable where backed enum names will be stored.
     * Set to <code>null</code> to avoid writing backed enum names.
     *
     * Example:
     * <code>->setEnumNames('enumNames')</code> yields:
     * ```yaml
     *   x-enumNames:
     *     - NAME1
     *     - NAME2
     * ```
     */
    public function set_enum_names(?string $enum_names = null): void
    {
        $this->enum_names = $enum_names;
    }
    public function __invoke(Analysis $analysis): void
    {
        if (!class_exists('\ReflectionEnum')) {
            return;
        }
        $this->expand_context_enum($analysis);
        $this->expand_schema_enum($analysis);
    }
    protected function expand_context_enum(Analysis $analysis): void
    {
        $schemas = $analysis->get_annotations_of_type(OA\Schema::class, true);
        foreach ($schemas as $schema) {
            if ($schema->_context->is('enum')) {
                $re = new \Reflection_Enum($schema->_context->fully_qualified_name($schema->_context->enum) ?? '');
                $schema->schema = Generator::is_default($schema->schema) ? $re->get_short_name() : $schema->schema;
                $schema_type = $schema->type;
                $enum_type = null;
                if ($re->is_backed()) {
                    $enum_type = $re->get_backing_type()->get_name();
                }
                // no (or invalid) schema type means name
                $use_name = Generator::is_default($schema_type) || $enum_type && $this->generator->get_type_resolver()->native2spec($enum_type) != $schema_type;
                $schema->enum = array_map(static fn(\Reflection_Enum_Unit_Case $case): int|string => $use_name || !$case instanceof \Reflection_Enum_Backed_Case ? $case->name : $case->get_backing_value(), $re->get_cases());
                if ($this->enum_names !== null && !$use_name) {
                    $schema_x = Generator::is_default($schema->x) ? [] : $schema->x;
                    $schema_x[$this->enum_names] = array_map(static fn(\Reflection_Enum_Unit_Case $case): string => $case->name, $re->get_cases());
                    $schema->x = $schema_x;
                }
                $schema->type = $use_name ? 'string' : $enum_type;
                $this->generator->get_type_resolver()->map_native_type($schema, $schema_type);
            }
        }
    }
    protected function expand_schema_enum(Analysis $analysis): void
    {
        $schemas = $analysis->get_annotations_of_type([OA\Schema::class, OA\Server_Variable::class]);
        foreach ($schemas as $schema) {
            if (Generator::is_default($schema->enum)) {
                continue;
            }
            if (is_string($schema->enum)) {
                // might be enum class-string
                if (is_a($schema->enum, \Unit_Enum::class, true)) {
                    $cases = $schema->enum::cases();
                } else {
                    throw new Open_Api_Exception("Unexpected enum value, requires specifying the Enum class string: {$schema->enum}");
                }
            } else {
                // might be an array of \UnitEnum::class, string, int, etc...
                assert(is_array($schema->enum));
                $cases = [];
                // transform \UnitEnum into individual cases
                /** @var string|class-string<\UnitEnum> $enum */
                foreach ($schema->enum as $enum) {
                    if (is_string($enum) && function_exists('enum_exists') && enum_exists($enum)) {
                        foreach ($enum::cases() as $case) {
                            $cases[] = $case;
                        }
                    } else {
                        $cases[] = $enum;
                    }
                }
            }
            $enums = [];
            foreach ($cases as $enum) {
                $enums[] = is_a($enum, \Unit_Enum::class) ? $enum->value ?? $enum->name : $enum;
            }
            $schema->enum = $enums;
        }
    }
}