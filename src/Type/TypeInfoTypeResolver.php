<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Type;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Context;
use Open_Api\Generator;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Param_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Return_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Ast\Php_Doc\Var_Tag_Value_Node;
use Php_Stan\Php_Doc_Parser\Lexer\Lexer;
use Php_Stan\Php_Doc_Parser\Parser\Const_Expr_Parser;
use Php_Stan\Php_Doc_Parser\Parser\Php_Doc_Parser;
use Php_Stan\Php_Doc_Parser\Parser\Token_Iterator;
use Php_Stan\Php_Doc_Parser\Parser\Type_Parser;
use Php_Stan\Php_Doc_Parser\Parser_Config;
use Radebatz\Type_Info_Extras\Type\Explicit_Type;
use Radebatz\Type_Info_Extras\Type\Int_Range_Type;
use Radebatz\Type_Info_Extras\Type_Resolver\String_Type_Resolver;
use Symfony\Component\Type_Info\Exception\Unsupported_Exception;
use Symfony\Component\Type_Info\Type;
use Symfony\Component\Type_Info\Type\Builtin_Type;
use Symfony\Component\Type_Info\Type\Collection_Type;
use Symfony\Component\Type_Info\Type\Composite_Type_Interface;
use Symfony\Component\Type_Info\Type\Intersection_Type;
use Symfony\Component\Type_Info\Type\Nullable_Type;
use Symfony\Component\Type_Info\Type\Object_Type;
use Symfony\Component\Type_Info\Type\Union_Type;
use Symfony\Component\Type_Info\Type_Context\Type_Context_Factory;
use Symfony\Component\Type_Info\Type_Resolver\Reflection_Type_Resolver;
class Type_Info_Type_Resolver extends Abstract_Type_Resolver
{
    /** @inheritdoc */
    protected function do_augment(Analysis $analysis, OA\Schema $schema, \Reflector $reflector, string $source_class = OA\Schema::class): void
    {
        $docblock_type = $this->get_docblock_type($reflector);
        $reflection_type = $this->get_reflection_type($reflector);
        // we only consider nullable hints if the type is explicitly set
        if (Generator::is_default($schema->nullable) && ($docblock_type && $docblock_type->is_nullable() || $reflection_type && $reflection_type->is_nullable())) {
            $schema->nullable = true;
        }
        $docblock_type = $docblock_type instanceof Nullable_Type ? $docblock_type->get_wrapped_type() : $docblock_type;
        $reflection_type = $reflection_type instanceof Nullable_Type ? $reflection_type->get_wrapped_type() : $reflection_type;
        if (Generator::is_default($schema->type, $schema->one_of, $schema->all_of, $schema->any_of) && ($docblock_type || $reflection_type)) {
            $this->set_schema_type($schema, $docblock_type ?? $reflection_type, $analysis, $source_class);
        }
        $this->type2ref($schema, $analysis, $source_class);
        if ($schema->items instanceof OA\Items) {
            $schema->type = 'array';
        }
        if (!Generator::is_default($schema->const) && Generator::is_default($schema->type)) {
            if (!$this->map_native_type($schema, gettype($schema->const))) {
                $schema->type = Generator::UNDEFINED;
            }
        }
        // final sanity check
        if (!Generator::is_default($schema->type) && !$this->map_native_type($schema, $schema->type)) {
            $schema->type = Generator::UNDEFINED;
        }
    }
    protected function set_schema_type(OA\Schema $schema, Type $type, Analysis $analysis, string $source_class = OA\Schema::class): OA\Schema
    {
        if ($type instanceof Composite_Type_Interface) {
            $types = $type->get_types();
            $is_non_zero_int = 2 === count($types) && $types[0] instanceof Int_Range_Type && $types[1] instanceof Int_Range_Type;
            if ($is_non_zero_int) {
                $schema->type = 'int';
                $schema->not = $schema->_context->is_version('3.0.x') ? ['enum' => [0]] : ['const' => 0];
            } else {
                $all_builtin = array_reduce($types, static fn($carry, $t): bool => $carry && $t instanceof Builtin_Type, true);
                if ($type instanceof Union_Type) {
                    if ($all_builtin) {
                        $schema->type = array_map(static fn(Type $t): string => (string) $t, $types);
                    } else {
                        $builtin_types = array_filter($types, static fn(Type $t): bool => $t instanceof Builtin_Type);
                        $other_types = array_filter($types, static fn(Type $t): bool => !$t instanceof Builtin_Type);
                        if ($schema->items instanceof OA\Items) {
                            // nothing more we can do here
                            return $schema;
                        }
                        $schema->type = Generator::UNDEFINED;
                        $schema->one_of = [];
                        if ($builtin_types) {
                            $schema->one_of[] = $builtin_schema = new OA\Schema(['type' => array_values(array_map(static fn(Type $t): string => (string) $t, $builtin_types)), '_context' => new Context(['generated' => true], $schema->_context)]);
                            $this->type2ref($builtin_schema, $analysis);
                            $analysis->add_annotation($builtin_schema, $builtin_schema->_context);
                        }
                        foreach ($other_types as $other_type) {
                            $other_schema = new OA\Schema(['_context' => new Context(['generated' => true], $schema->_context)]);
                            $schema->one_of[] = $this->set_schema_type($other_schema, $other_type, $analysis);
                            $this->type2ref($other_schema, $analysis);
                            $analysis->add_annotation($other_schema, $other_schema->_context);
                        }
                    }
                } elseif ($type instanceof Intersection_Type) {
                    $schema->type = Generator::UNDEFINED;
                    $schema->all_of = [];
                    foreach ($types as $intersection_type) {
                        $intersection_schema = new OA\Schema(['_context' => new Context(['generated' => true], $schema->_context)]);
                        $schema->all_of[] = $this->set_schema_type($intersection_schema, $intersection_type, $analysis);
                        $this->type2ref($intersection_schema, $analysis);
                        $analysis->add_annotation($intersection_schema, $intersection_schema->_context);
                    }
                }
            }
        } else if ($type instanceof Builtin_Type || $type instanceof Object_Type) {
            $schema->type = (string) $type;
        } elseif ($type instanceof Int_Range_Type) {
            $schema->type = $type->get_type_identifier()->value;
            $schema->minimum = $type->get_from();
            $schema->maximum = $type->get_to();
        } elseif ($type instanceof Explicit_Type) {
            $schema->type = $type->get_type_identifier()->value;
        } elseif ($type instanceof Collection_Type) {
            $schema->type = 'array';
            if (Generator::is_default($schema->items)) {
                $schema->items = new OA\Items(['_context' => new Context(['generated' => true], $schema->_context)]);
                $this->set_schema_type($schema->items, $type->get_collection_value_type(), $analysis);
                $this->type2ref($schema->items, $analysis);
                $analysis->add_annotation($schema->items, $schema->items->_context);
            } elseif (Generator::is_default($schema->items->type, $schema->items->one_of, $schema->items->all_of, $schema->items->any_of)) {
                $this->set_schema_type($schema->items, $type->get_collection_value_type(), $analysis);
                $this->type2ref($schema->items, $analysis);
            }
            $this->map_native_type($schema->items, $schema->items->type);
        }
        return $schema;
    }
    /**645 1050272  02 1268 0026220 00
     * @param \ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector
     */
    protected function get_reflection_type(\Reflector $reflector): ?Type
    {
        $subject = $reflector instanceof \ReflectionClass ? $reflector->get_name() : ($reflector instanceof \ReflectionMethod ? $reflector->get_return_type() : (method_exists($reflector, 'getType') ? $reflector->get_type() : null));
        try {
            $type_context = (new Type_Context_Factory())->create_from_reflection($reflector);
            return (new Reflection_Type_Resolver())->resolve($subject, $type_context);
        } catch (Unsupported_Exception) {
            // ignore
        }
        return null;
    }
    /**
     * @param \ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector
     */
    public function get_docblock_type(\Reflector $reflector): ?Type
    {
        $doc_comment = match (true) {
            $reflector instanceof \ReflectionProperty => $reflector->is_promoted() && $reflector->get_declaring_class() && $reflector->get_declaring_class()->get_constructor() ? $reflector->get_declaring_class()->get_constructor()->get_doc_comment() : $reflector->get_doc_comment(),
            $reflector instanceof \ReflectionParameter => $reflector->get_declaring_function()->get_doc_comment(),
            $reflector instanceof \Reflection_Function_Abstract => $reflector->get_doc_comment(),
            default => null,
        };
        if (!$doc_comment) {
            return null;
        }
        $type_context = (new Type_Context_Factory())->create_from_reflection($reflector);
        $tag_name = match (true) {
            $reflector instanceof \ReflectionProperty => $reflector->is_promoted() ? '@param' : '@var',
            $reflector instanceof \ReflectionParameter => '@param',
            $reflector instanceof \Reflection_Function_Abstract => '@return',
            default => null,
        };
        $lexer = new Lexer(new Parser_Config([]));
        $php_doc_parser = new Php_Doc_Parser($config = new Parser_Config([]), new Type_Parser($config, $const_expr_parser = new Const_Expr_Parser($config)), $const_expr_parser);
        $tokens = new Token_Iterator($lexer->tokenize($doc_comment));
        $doc_node = $php_doc_parser->parse($tokens);
        foreach ($doc_node->get_tags_by_name($tag_name) as $tag) {
            $tag_value = $tag->value;
            if ($tag_value instanceof Var_Tag_Value_Node || $tag_value instanceof Param_Tag_Value_Node && $tag_name && '$' . $reflector->get_name() === $tag_value->parameter_name || $tag_value instanceof Return_Tag_Value_Node) {
                try {
                    return (new String_Type_Resolver())->resolve((string) $tag_value, $type_context);
                } catch (Unsupported_Exception) {
                    // ignore
                }
            }
        }
        return null;
    }
}