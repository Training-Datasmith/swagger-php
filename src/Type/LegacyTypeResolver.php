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
use Open_Api\Type_Resolver_Interface;
/**
 * @deprecated use `TypeInfoTypeResolver` instead
 */
class Legacy_Type_Resolver extends Abstract_Type_Resolver
{
    /** @inheritdoc */
    protected function do_augment(Analysis $analysis, OA\Schema $schema, \Reflector $reflector, string $source_class = OA\Schema::class): void
    {
        $docblock_details = $this->get_docblock_type_details($reflector, $schema->_context);
        $reflection_type_details = $this->get_reflection_type_details($reflector, $schema->_context);
        // we only consider nullable hints if the type is explicitly set
        if (Generator::is_default($schema->nullable) && ($docblock_details->types && $docblock_details->nullable || $reflection_type_details->types && $reflection_type_details->nullable)) {
            $schema->nullable = true;
        }
        if (Generator::is_default($schema->type, $schema->one_of, $schema->all_of, $schema->any_of) && ($docblock_details->explicit_type || $reflection_type_details->explicit_type)) {
            $details = $docblock_details->types || $docblock_details->unsupported ? $docblock_details : $reflection_type_details;
            // for now
            if (1 === count($details->types)) {
                $schema->type = $details->types[0];
            }
            if ('int' === $schema->type && is_array($details->explicit_details)) {
                if (array_key_exists('min', $details->explicit_details)) {
                    $schema->minimum = $details->explicit_details['min'];
                    $schema->maximum = $details->explicit_details['max'];
                } elseif ('non-zero-int' === $details->explicit_type) {
                    $schema->not = $schema->_context->is_version('3.0.x') ? ['enum' => [0]] : ['const' => 0];
                }
            }
        }
        if ($docblock_details->is_array || $reflection_type_details->is_array && !$docblock_details->unsupported) {
            $this->augment_items($schema, $analysis);
        }
        $this->type2ref($schema, $analysis);
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
    protected function augment_items(OA\Schema $schema, Analysis $analysis): void
    {
        if (!Generator::is_default($schema->type)) {
            if (Generator::is_default($schema->items)) {
                $schema->items = new OA\Items(['type' => $schema->type, '_context' => new Context(['generated' => true], $schema->_context)]);
                $this->type2ref($schema->items, $analysis);
                $analysis->add_annotation($schema->items, $schema->items->_context);
                if (!Generator::is_default($schema->ref)) {
                    $schema->items->ref = $schema->ref;
                    $schema->ref = Generator::UNDEFINED;
                }
            } elseif (Generator::is_default($schema->items->type, $schema->items->one_of, $schema->items->all_of, $schema->items->any_of)) {
                $schema->items->type = $schema->type;
                $this->type2ref($schema->items, $analysis);
            }
        }
        $this->map_native_type($schema->items, $schema->items->type);
        $schema->type = 'array';
    }
    protected function normalise_type_result(?string $explicit_type = null, ?array $explicit_details = null, array $types = [], ?string $name = null, ?bool $nullable = null, ?bool $is_array = null, bool $unsupported = false, ?Context $context = null): \stdClass
    {
        $types = array_filter($types, static fn(string $t): bool => !in_array($t, ['null', ''], strict: true));
        if ($context) {
            foreach ($types as $ii => $type) {
                if (!array_key_exists(strtolower((string) $type), Type_Resolver_Interface::NATIVE_TYPE_MAP) && !class_exists($type)) {
                    if (($resolved = $context->fully_qualified_name($type)) && class_exists($resolved)) {
                        $types[$ii] = ltrim($resolved, '\\');
                    } else {
                        // invalid type
                        unset($types[$ii]);
                    }
                }
            }
            // ensure we reset numeric keys
            $types = array_values($types);
        }
        $explicit_type = $explicit_type ?: ($types ? $types[0] : null);
        return (object) ['explicitType' => $explicit_type, 'explicitDetails' => $explicit_details, 'types' => $types, 'name' => $name, 'nullable' => $explicit_type ? $nullable : true, 'isArray' => $is_array, 'unsupported' => $unsupported];
    }
    /**
     * @param \ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector
     */
    protected function get_reflection_type_details(\Reflector $reflector, ?Context $context): \stdClass
    {
        $rtype = $reflector instanceof \ReflectionClass ? $reflector->get_name() : ($reflector instanceof \ReflectionMethod ? $reflector->get_return_type() : (method_exists($reflector, 'getType') ? $reflector->get_type() : null));
        $is_array = false;
        $types = [];
        if ($rtype instanceof \ReflectionUnionType) {
            foreach ($rtype->get_types() as $utype) {
                // more nesting is not supported
                if ($utype instanceof \ReflectionNamedType) {
                    $types[] = $utype->get_name();
                }
            }
        } elseif ($rtype instanceof \ReflectionNamedType) {
            $types[] = $rtype->get_name();
        }
        if (1 === count($types) && 'array' === $types[0]) {
            $types = ['mixed'];
            $is_array = true;
        }
        $name = $reflector->get_name();
        $nullable = (is_object($rtype) ? $rtype->allows_null() : true) || in_array('null', $types);
        return $this->normalise_type_result(null, null, array_reverse($types), $name, $nullable, $is_array, false, $context);
    }
    /**
     * @param \ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector
     */
    protected function get_docblock_type_details(\Reflector $reflector, ?Context $context): \stdClass
    {
        $doc_comment = match (true) {
            $reflector instanceof \ReflectionProperty => $reflector->is_promoted() && $reflector->get_declaring_class() && $reflector->get_declaring_class()->get_constructor() ? $reflector->get_declaring_class()->get_constructor()->get_doc_comment() : $reflector->get_doc_comment(),
            $reflector instanceof \ReflectionParameter => $reflector->get_declaring_function()->get_doc_comment(),
            $reflector instanceof \Reflection_Function_Abstract => $reflector->get_doc_comment(),
            default => null,
        };
        // cheat
        $name = $reflector->get_name();
        if (!$doc_comment) {
            return $this->normalise_type_result(null, null, [], $name, null, null, false, $context);
        }
        $tag_name = match (true) {
            $reflector instanceof \ReflectionProperty => $reflector->is_promoted() ? '@param' : '@var',
            $reflector instanceof \ReflectionParameter => '@param',
            $reflector instanceof \Reflection_Function_Abstract => '@return',
            default => null,
        };
        if (!$tag_name) {
            return $this->normalise_type_result(null, null, [], $name, null, null, false, $context);
        }
        $pattern = "/{$tag_name}\\s+(?<type>[^\\s]+)([ \t])?/im";
        if ('@param' === $tag_name) {
            // need to match on $name too
            $pattern = "/{$tag_name}\\s+(?<type>[^\\s]+)([ \t])?\\\${$name}([\\s\r\n])/im";
        }
        $doc_comment = str_replace("\r\n", "\n", $doc_comment);
        $doc_comment = str_replace('list', 'array', $doc_comment);
        $doc_comment = preg_replace('/\*\/[ \t]*$/', '', $doc_comment);
        // strip '*/'
        preg_match($pattern, (string) $doc_comment, $matches);
        $explicit_type = null;
        $explicit_details = null;
        $type = $matches['type'] ?? '';
        $nullable = in_array('null', explode('|', strtolower($type))) || str_contains($type, '?');
        $is_array = str_contains($type, '[]') || str_contains($type, 'array');
        $type = str_replace(['|null', 'null|', '?', 'null', '[]'], '', $type);
        $unsupported = false;
        $is_union = count(explode('|', $type)) > 1;
        if ($is_union && $is_array) {
            $type = '';
            $is_array = false;
            $unsupported = true;
        }
        // typed array
        $result = preg_match('/([^<]+)<([^>]+)>/', $type, $matches);
        if ($result) {
            $type = $is_array ? $matches[2] : $matches[1];
            if ('int' === $type) {
                $min_max = array_map(trim(...), explode(',', $matches[2]));
                if (2 === count($min_max)) {
                    $explicit_details = ['min' => (int) ('min' === $min_max[0] ? \PHP_INT_MIN : $min_max[0]), 'max' => (int) ('max' === $min_max[1] ? \PHP_INT_MAX : $min_max[1])];
                }
            }
        }
        // array shape
        $result = preg_match('/([^{]+){([^}]+)}/', $type, $matches);
        if ($result) {
            $shape_types = [];
            foreach (explode(',', $matches[2]) as $shape) {
                $token = explode(':', $shape);
                if (2 === count($token)) {
                    $shape_types[trim($token[0])] = trim($token[1]);
                }
            }
            $type = implode('|', $shape_types);
        }
        // special types
        switch ($type) {
            case 'positive-int':
                $explicit_type = $type;
                $explicit_details = ['min' => 1, 'max' => \PHP_INT_MAX];
                $type = 'int';
                break;
            case 'negative-int':
                $explicit_type = $type;
                $explicit_details = ['min' => \PHP_INT_MIN, 'max' => -1];
                $type = 'int';
                break;
            case 'non-positive-int':
                $explicit_type = $type;
                $explicit_details = ['min' => \PHP_INT_MIN, 'max' => 0];
                $type = 'int';
                break;
            case 'non-negative-int':
                $explicit_type = $type;
                $explicit_details = ['min' => 0, 'max' => \PHP_INT_MAX];
                $type = 'int';
                break;
            case 'non-zero-int':
                $explicit_type = $type;
                $explicit_details = [['min' => \PHP_INT_MIN, 'max' => -1], ['min' => 1, 'max' => \PHP_INT_MAX]];
                $type = 'int';
                break;
        }
        $type = ltrim($type, '\\');
        $types = explode('|', $type);
        return $this->normalise_type_result($explicit_type, $explicit_details, $types, $name, $nullable, $is_array, $unsupported, $context);
    }
}