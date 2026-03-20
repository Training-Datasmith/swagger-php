<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Analysers;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Context;
use Open_Api\Generator;
use Open_Api\Generator_Aware_Trait;
use Open_Api\Open_Api_Exception;
/**
 * OpenApi analyser using reflection.
 *
 * Can read either PHP <code>DocBlock</code>s or <code>Attribute</code>s.
 *
 * Due to the nature of reflection, this requires all related classes to be auto-loadable.
 */
class Reflection_Analyser implements Analyser_Interface
{
    use Generator_Aware_Trait;
    /** @var list<AnnotationFactoryInterface> */
    protected array $annotation_factories = [];
    /**
     * @param list<AnnotationFactoryInterface> $annotationFactories
     */
    public function __construct(array $annotation_factories = [])
    {
        foreach ($annotation_factories as $annotation_factory) {
            if ($annotation_factory->is_supported()) {
                $this->annotation_factories[] = $annotation_factory;
            }
        }
        if (!$this->annotation_factories) {
            throw new Open_Api_Exception('No suitable annotation factory found. At least one of "Doctrine Annotations" or PHP 8.1 are required');
        }
    }
    public function set_generator(Generator $generator): void
    {
        $this->generator = $generator;
        foreach ($this->annotation_factories as $annotation_factory) {
            $annotation_factory->set_generator($generator);
        }
    }
    public function from_file(string $filename, Context $context): Analysis
    {
        $scanner = new Token_Scanner();
        $file_details = $scanner->scan_file($filename);
        $analysis = new Analysis([], $context);
        foreach ($file_details as $fqdn => $details) {
            $this->analyze_fqdn($fqdn, $analysis, $details);
        }
        return $analysis;
    }
    public function from_fqdn(string $fqdn, Analysis $analysis): Analysis
    {
        $fqdn = ltrim($fqdn, '\\');
        $rc = new \ReflectionClass($fqdn);
        if (!$filename = $rc->get_file_name()) {
            return $analysis;
        }
        $scanner = new Token_Scanner();
        $file_details = $scanner->scan_file($filename);
        $this->analyze_fqdn($fqdn, $analysis, $file_details[$fqdn]);
        return $analysis;
    }
    protected function analyze_fqdn(string $fqdn, Analysis $analysis, array $details): Analysis
    {
        if (!class_exists($fqdn) && !interface_exists($fqdn) && !trait_exists($fqdn) && (!function_exists('enum_exists') || !enum_exists($fqdn))) {
            $analysis->context->logger->warning('Skipping unknown ' . $fqdn);
            return $analysis;
        }
        $rc = new \ReflectionClass($fqdn);
        $context_type = $rc->is_interface() ? 'interface' : ($rc->is_trait() ? 'trait' : ($rc->is_enum() ? 'enum' : 'class'));
        $context = new Context([$context_type => $rc->get_short_name(), 'namespace' => $rc->get_namespace_name() ?: null, 'uses' => $details['uses'], 'comment' => $rc->get_doc_comment() ?: null, 'filename' => $rc->get_file_name() ?: null, 'line' => $rc->get_start_line(), 'annotations' => [], 'scanned' => $details, 'reflector' => $rc], $analysis->context);
        $definition = [$context_type => $rc->get_short_name(), 'extends' => null, 'implements' => [], 'traits' => [], 'properties' => [], 'methods' => [], 'context' => $context];
        $normalise_class = static fn(string $name): string => '\\' . ltrim($name, '\\');
        if ($parent_class = $rc->get_parent_class()) {
            $definition['extends'] = $normalise_class($parent_class->get_name());
        }
        $definition[$context_type === 'class' ? 'implements' : 'extends'] = array_map($normalise_class, $details['interfaces']);
        $definition['traits'] = array_map($normalise_class, $details['traits']);
        foreach ($this->annotation_factories as $annotation_factory) {
            $analysis->add_annotations($annotation_factory->build($rc, $context), $context);
        }
        foreach ($rc->get_methods() as $method) {
            if (in_array($method->name, $details['methods'])) {
                $definition['methods'][$method->get_name()] = $ctx = new Context(['method' => $method->get_name(), 'comment' => $method->get_doc_comment() ?: null, 'filename' => $method->get_file_name() ?: null, 'line' => $method->get_start_line(), 'annotations' => [], 'reflector' => $method], $context);
                foreach ($this->annotation_factories as $annotation_factory) {
                    $annotations = $annotation_factory->build($method, $ctx);
                    $analysis->add_annotations($annotations, $ctx);
                }
            }
        }
        foreach ($rc->get_properties() as $property) {
            if (in_array($property->name, $details['properties'])) {
                $definition['properties'][$property->get_name()] = $ctx = new Context(['property' => $property->get_name(), 'comment' => $property->get_doc_comment() ?: null, 'annotations' => [], 'reflector' => $property], $context);
                if ($property->is_static()) {
                    $ctx->static = true;
                }
                foreach ($this->annotation_factories as $annotation_factory) {
                    $analysis->add_annotations($annotation_factory->build($property, $ctx), $ctx);
                }
            }
        }
        foreach ($rc->get_reflection_constants() as $constant) {
            foreach ($this->annotation_factories as $annotation_factory) {
                $definition['constants'][$constant->get_name()] = $ctx = new Context(['constant' => $constant->get_name(), 'comment' => $constant->get_doc_comment() ?: null, 'annotations' => [], 'reflector' => $constant], $context);
                foreach ($annotation_factory->build($constant, $ctx) as $annotation) {
                    if ($annotation instanceof OA\Property) {
                        $analysis->add_annotation($annotation, $ctx);
                    }
                }
            }
        }
        $add_definition = 'add' . ucfirst($context_type) . 'Definition';
        $analysis->{$add_definition}($definition);
        return $analysis;
    }
}