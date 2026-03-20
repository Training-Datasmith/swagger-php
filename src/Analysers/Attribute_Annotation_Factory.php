<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Analysers;

use Open_Api\Annotations as OA;
use Open_Api\Attributes as OAT;
use Open_Api\Context;
use Open_Api\Generator;
use Open_Api\Generator_Aware_Trait;
class Attribute_Annotation_Factory implements Annotation_Factory_Interface
{
    use Generator_Aware_Trait;
    public function __construct(protected bool $ignore_other_attributes = false)
    {
    }
    public function is_supported(): bool
    {
        return true;
    }
    public function build(\Reflector $reflector, Context $context): array
    {
        if (!$this->is_supported()) {
            return [];
        }
        if ($reflector instanceof \ReflectionProperty && $reflector->is_promoted()) {
            // handled via __construct() parameter
            return [];
        }
        // no proper way to inject
        Generator::$context = $context;
        /** @var list<OA\AbstractAnnotation> $annotations */
        $annotations = [];
        try {
            $attribute_name = $this->ignore_other_attributes ? [OA\Abstract_Annotation::class, \Reflection_Attribute::IS_INSTANCEOF] : [];
            foreach ($reflector->get_attributes(...$attribute_name) as $attribute) {
                if (class_exists($attribute->get_name())) {
                    $instance = $attribute->new_instance();
                    if ($instance instanceof OA\Abstract_Annotation) {
                        $annotations[] = $instance;
                    } else {
                        if (false === $context->is('other')) {
                            $context->other = [];
                        }
                        $context->other[] = $instance;
                    }
                } else {
                    $context->logger->debug(sprintf('Could not instantiate attribute "%s"; class not found.', $attribute->get_name()));
                }
            }
            if ($reflector instanceof \ReflectionMethod) {
                // also look at parameter attributes
                foreach ($reflector->get_parameters() as $rp) {
                    foreach ([OA\Property::class, OAT\Parameter::class, OA\Request_Body::class] as $attribute_name) {
                        foreach ($rp->get_attributes($attribute_name, \Reflection_Attribute::IS_INSTANCEOF) as $attribute) {
                            /** @var OA\Property|OAT\Parameter|OA\RequestBody $instance */
                            $instance = $attribute->new_instance();
                            $instance->_context = new Context(['nested' => false, 'property' => $rp->get_name(), 'reflector' => $rp], $context);
                            if ($instance instanceof OA\Property) {
                                if ($rp->is_promoted()) {
                                    // ensure each property has its own context
                                    $instance->_context = new Context(['generated' => true, 'annotations' => [$instance], 'property' => $rp->get_name(), 'reflector' => $rp], $context);
                                    // promoted parameter - docblock is available via class/property
                                    if ($comment = $rp->get_declaring_class()->get_property($rp->get_name())->get_doc_comment()) {
                                        $instance->_context->comment = $comment;
                                    }
                                } else {
                                    $instance->_context->property = $rp->get_name();
                                }
                            }
                            $annotations[] = $instance;
                        }
                    }
                }
            }
        } finally {
            Generator::$context = null;
        }
        // merge backwards into parents...
        $is_parent = static function (OA\Abstract_Annotation $annotation, OA\Abstract_Annotation $possible_parent): bool {
            // regular annotation hierarchy
            $explicit_parent = null !== $possible_parent->match_nested($annotation) && !$annotation instanceof OA\Attachable;
            $is_parent_allowed = false;
            // support Attachable subclasses
            if ($is_attachable = $annotation instanceof OA\Attachable) {
                if (!$is_parent_allowed = null === $annotation->allowed_parents()) {
                    // check for allowed parents
                    foreach ($annotation->allowed_parents() as $allowed_parent) {
                        if ($possible_parent instanceof $allowed_parent) {
                            $is_parent_allowed = true;
                            break;
                        }
                    }
                }
            }
            // Attachables can always be nested (unless explicitly restricted)
            return $is_attachable && $is_parent_allowed || $annotation->get_root() !== $possible_parent->get_root() && $explicit_parent;
        };
        $annotations_without_parent = [];
        foreach ($annotations as $index => $annotation) {
            $merged_into_parent = false;
            for ($ii = 0; $ii < count($annotations); ++$ii) {
                if ($ii === $index) {
                    continue;
                }
                $possible_parent = $annotations[$ii];
                if ($is_parent($annotation, $possible_parent)) {
                    $merged_into_parent = true;
                    $possible_parent->merge([$annotation]);
                }
            }
            if (!$merged_into_parent) {
                $annotations_without_parent[] = $annotation;
            }
        }
        return $annotations_without_parent;
    }
}