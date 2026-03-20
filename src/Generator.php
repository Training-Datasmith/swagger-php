<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api;

use Open_Api\Analysers\Analyser_Interface;
use Open_Api\Analysers\Attribute_Annotation_Factory;
use Open_Api\Analysers\Doc_Block_Annotation_Factory;
use Open_Api\Analysers\Reflection_Analyser;
use Open_Api\Annotations as OA;
use Open_Api\Loggers\Default_Logger;
use Open_Api\Type\Type_Info_Type_Resolver;
use Psr\Log\Logger_Interface;
/**
 * OpenApi spec generator.
 *
 * Scans PHP source code and generates OpenApi specifications from the found OpenApi annotations.
 */
class Generator
{
    /**
     * Allows Annotation classes to know the context of the annotation that is being processed.
     */
    public static ?Context $context = null;
    /** @var string Magic value to differentiate between null and undefined. */
    public const UNDEFINED = '@OA\Generator::UNDEFINED🙈';
    /** @var array<string,string> */
    public const DEFAULT_ALIASES = ['oa' => 'OpenApi\Annotations'];
    /** @var list<string> */
    public const DEFAULT_NAMESPACES = ['OpenApi\Annotations\\'];
    /** @var array<string,string> Map of namespace aliases to be supported by doctrine. */
    protected array $aliases;
    /** @var array<string>|null List of annotation namespaces to be autoloaded by doctrine. */
    protected ?array $namespaces;
    protected ?Analyser_Interface $analyser = null;
    /** @var array<string,mixed> */
    protected array $config = [];
    protected ?Pipeline $processor_pipeline = null;
    protected ?Type_Resolver_Interface $type_resolver = null;
    /**
     * OpenApi version override.
     *
     * If set, it will override the version set in the <code>OpenApi</code> annotation.
     *
     * Due to the order of processing, any conditional code using this (via <code>Context::$version</code>)
     * must come only after the analysis is finished.
     */
    protected ?string $version = null;
    public function __construct(protected ?Logger_Interface $logger = null)
    {
        $this->set_aliases(self::DEFAULT_ALIASES);
        $this->set_namespaces(self::DEFAULT_NAMESPACES);
    }
    public static function is_default(...$value): bool
    {
        foreach ($value as $v) {
            if ($v !== Generator::UNDEFINED) {
                return false;
            }
        }
        return true;
    }
    /**
     * @return array<string, string>
     */
    public function get_aliases(): array
    {
        return $this->aliases;
    }
    public function add_alias(string $alias, string $namespace): Generator
    {
        $this->aliases[$alias] = $namespace;
        return $this;
    }
    public function set_aliases(array $aliases): Generator
    {
        $this->aliases = $aliases;
        return $this;
    }
    /**
     * @return list<string>|null
     */
    public function get_namespaces(): ?array
    {
        return $this->namespaces;
    }
    public function add_namespace(string $namespace): Generator
    {
        $namespaces = (array) $this->get_namespaces();
        $namespaces[] = $namespace;
        return $this->set_namespaces(array_unique($namespaces));
    }
    public function set_namespaces(?array $namespaces): Generator
    {
        $this->namespaces = $namespaces;
        return $this;
    }
    public function get_analyser(): Analyser_Interface
    {
        $generator_config = $this->get_config()['generator'];
        $this->analyser = $this->analyser ?: new Reflection_Analyser([new Attribute_Annotation_Factory($generator_config['ignoreOtherAttributes']), new Doc_Block_Annotation_Factory()]);
        $this->analyser->set_generator($this);
        return $this->analyser;
    }
    public function set_analyser(?Analyser_Interface $analyser): Generator
    {
        $this->analyser = $analyser;
        return $this;
    }
    public function get_default_config(): array
    {
        return ['generator' => ['ignoreOtherAttributes' => false], 'mergeIntoOpenApi' => ['mergeComponents' => false], 'expandEnums' => ['enumNames' => null], 'augmentParameters' => ['augmentOperationParameters' => true], 'pathFilter' => ['tags' => [], 'paths' => [], 'recurseCleanup' => false], 'cleanUnusedComponents' => ['enabled' => false], 'augmentTags' => ['whitelist' => [], 'withDescription' => true], 'operationId' => ['hash' => true]];
    }
    public function get_config(): array
    {
        return $this->config + $this->get_default_config();
    }
    protected function normalise_config(array $config): array
    {
        $normalised = [];
        foreach ($config as $key => $value) {
            if (is_numeric($key)) {
                $token = explode('=', (string) $value);
                if (2 === count($token)) {
                    // 'operationId.hash=false'
                    [$key, $value] = $token;
                }
            }
            if (in_array($value, ['true', 'false'])) {
                $value = 'true' == $value;
            }
            if ($is_list = str_ends_with((string) $key, '[]')) {
                $key = substr((string) $key, 0, -2);
            }
            $token = explode('.', (string) $key);
            if (2 === count($token)) {
                // 'operationId.hash' => false
                // namespaced / processor
                if ($is_list) {
                    $normalised[$token[0]][$token[1]][] = $value;
                } else {
                    $normalised[$token[0]][$token[1]] = $value;
                }
            } else if ($is_list) {
                $normalised[$key][] = $value;
            } else {
                $normalised[$key] = $value;
            }
        }
        return $normalised;
    }
    /**
     * Set generator and/or processor config.
     *
     * @param array<string,mixed> $config
     */
    public function set_config(array $config): Generator
    {
        $this->config = $this->normalise_config($config) + $this->config;
        return $this;
    }
    public function get_processor_pipeline(): Pipeline
    {
        if (!$this->processor_pipeline instanceof Pipeline) {
            $this->processor_pipeline = new Pipeline([new Processors\Doc_Block_Descriptions(), new Processors\Merge_Into_Open_Api(), new Processors\Merge_Into_Components(), new Processors\Expand_Classes(), new Processors\Expand_Interfaces(), new Processors\Expand_Traits(), new Processors\Expand_Enums(), new Processors\Augment_Schemas(), new Processors\Augment_Request_Body(), new Processors\Augment_Properties(), new Processors\Augment_Discriminators(), new Processors\Build_Paths(), new Processors\Augment_Parameters(), new Processors\Augment_Refs(), new Processors\Augment_Items(), new Processors\Merge_Json_Content(), new Processors\Merge_Xml_Content(), new Processors\Augment_Media_Type(), new Processors\Operation_Id(), new Processors\Clean_Unmerged(), new Processors\Path_Filter(), new Processors\Clean_Unused_Components(), new Processors\Augment_Tags()]);
        }
        $config = $this->get_config();
        $walker = function (callable $pipe) use ($config): void {
            $rc = new \ReflectionClass($pipe);
            // apply config
            $processor_key = lcfirst($rc->get_short_name());
            if (array_key_exists($processor_key, $config)) {
                foreach ($config[$processor_key] as $name => $value) {
                    $setter = 'set' . ucfirst($name);
                    if (method_exists($pipe, $setter)) {
                        $pipe->{$setter}($value);
                    }
                }
            }
            if (is_a($pipe, Generator_Aware_Interface::class)) {
                $pipe->set_generator($this);
            }
        };
        return $this->processor_pipeline->walk($walker);
    }
    public function set_processor_pipeline(?Pipeline $processor): Generator
    {
        $this->processor_pipeline = $processor;
        $walker = function (callable $pipe): void {
            if (is_a($pipe, Generator_Aware_Interface::class)) {
                $pipe->set_generator($this);
            }
        };
        if ($this->processor_pipeline) {
            $this->processor_pipeline->walk($walker);
        }
        return $this;
    }
    /**
     * Chainable method that allows to modify the processor pipeline.
     *
     * @param callable $with callable with the current processor pipeline passed in
     */
    public function with_processor_pipeline(callable $with): Generator
    {
        $with($this->get_processor_pipeline());
        return $this;
    }
    public function set_type_resolver(?Type_Resolver_Interface $type_resolver): Generator
    {
        $this->type_resolver = $type_resolver;
        return $this;
    }
    public function get_type_resolver(): Type_Resolver_Interface
    {
        $this->type_resolver ??= new Type_Info_Type_Resolver();
        return $this->type_resolver;
    }
    public function get_logger(): ?Logger_Interface
    {
        $this->logger ??= new Default_Logger();
        return $this->logger;
    }
    public function get_version(): ?string
    {
        return $this->version;
    }
    public function set_version(?string $version): Generator
    {
        $this->version = $version;
        return $this;
    }
    /**
     * Run code in the context of this generator.
     *
     * @param callable $callable Callable in the form of
     *                           <code>function(Generator $generator, Analysis $analysis, Context $context): mixed</code>
     *
     * @return mixed the result of the <code>callable</code>
     */
    public function with_context(callable $callable)
    {
        $root_context = new Context(['version' => $this->get_version(), 'logger' => $this->get_logger()]);
        $analysis = new Analysis([], $root_context);
        return $callable($this, $analysis, $root_context);
    }
    /**
     * Generate OpenAPI spec by scanning the given source files.
     *
     * @param iterable      $sources  PHP source files to scan.
     *                                Supported sources:
     *                                * string - file / directory name
     *                                * \SplFileInfo
     *                                * \Symfony\Component\Finder\Finder
     * @param null|Analysis $analysis custom analysis instance
     * @param bool          $validate flag to enable/disable validation of the returned spec
     */
    public function generate(iterable $sources, ?Analysis $analysis = null, bool $validate = true): ?OA\Open_Api
    {
        $root_context = new Context(['version' => $this->get_version(), 'logger' => $this->get_logger()]);
        $analysis = $analysis ?: new Analysis([], $root_context);
        $analysis->context = $analysis->context ?: $root_context;
        $this->scan_sources($sources, $analysis, $root_context);
        // post-processing
        $this->get_processor_pipeline()->process($analysis);
        if ($analysis->openapi) {
            // overwrite default/annotated version
            $analysis->openapi->openapi = $this->get_version() ?: $analysis->openapi->openapi;
            // update context to provide the same to validation/serialisation code
            $root_context->version = $analysis->openapi->openapi;
        }
        // validation
        if ($validate) {
            $analysis->validate();
        }
        return $analysis->openapi;
    }
    protected function scan_sources(iterable $sources, Analysis $analysis, Context $root_context): void
    {
        $analyser = $this->get_analyser();
        foreach ($sources as $source) {
            if (is_iterable($source)) {
                $this->scan_sources($source, $analysis, $root_context);
            } else {
                $resolved_source = $source instanceof \Spl_File_Info ? $source->get_pathname() : realpath($source);
                if (!$resolved_source) {
                    $root_context->logger->warning(sprintf('Skipping invalid source: %s', $source));
                    continue;
                }
                if (is_dir($resolved_source)) {
                    $this->scan_sources(new Source_Finder($resolved_source), $analysis, $root_context);
                } else {
                    $root_context->logger->debug(sprintf('Analysing source: %s', $resolved_source));
                    $analysis->add_analysis($analyser->from_file($resolved_source, $root_context));
                }
            }
        }
    }
}