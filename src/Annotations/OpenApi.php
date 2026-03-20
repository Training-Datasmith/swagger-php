<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Annotations;

use Open_Api\Analysis;
use Open_Api\Generator;
use Open_Api\Open_Api_Exception;
/**
 * This is the root document object for the API specification.
 *
 * @see [OpenApi Object](https://spec.openapis.org/oas/v3.1.1.html#openapi-object)
 *
 * @Annotation
 */
class Open_Api extends Abstract_Annotation
{
    public const VERSION_3_0_0 = '3.0.0';
    public const VERSION_3_1_0 = '3.1.0';
    public const VERSION_3_2_0 = '3.2.0';
    public const DEFAULT_VERSION = self::VERSION_3_0_0;
    public const SUPPORTED_VERSIONS = [self::VERSION_3_0_0, '3.0.1', '3.0.2', '3.0.3', '3.0.4', self::VERSION_3_1_0, '3.1.1', '3.1.2', self::VERSION_3_2_0];
    /**
     * The semantic version number of the OpenAPI Specification version that the OpenAPI document uses.
     *
     * The openapi field should be used by tooling specifications and clients to interpret the OpenAPI document.
     *
     * A version specified via <code>Generator::setVersion()</code> will overwrite this value.
     *
     * NOTE: This is not related to the API info::version string.
     *
     * @var string
     */
    public $openapi = self::DEFAULT_VERSION;
    /**
     * Provides metadata about the API. The metadata may be used by tooling as required.
     *
     * @var Info
     */
    public $info = Generator::UNDEFINED;
    /**
     * An array of <code>@Server</code> objects, which provide connectivity information to a target server.
     *
     * If not provided, or is an empty array, the default value would be a Server Object with an url value of <code>/</code>.
     *
     * @var list<Server>
     */
    public $servers = Generator::UNDEFINED;
    /**
     * The available paths and operations for the API.
     *
     * @var array<PathItem>
     */
    public $paths = Generator::UNDEFINED;
    /**
     * An element to hold various components for the specification.
     *
     * @var Components
     */
    public $components = Generator::UNDEFINED;
    /**
     * A declaration of which security mechanisms can be used across the API.
     *
     * The list of values includes alternative security requirement objects that can be used.
     * Only one of the security requirement objects need to be satisfied to authorize a request.
     * Individual operations can override this definition.
     * To make security optional, an empty security requirement (<code>{}</code>) can be included in the array.
     *
     * @var array
     */
    public $security = Generator::UNDEFINED;
    /**
     * A list of tags used by the specification with additional metadata.
     *
     * The order of the tags can be used to reflect on their order by the parsing tools.
     * Not all tags that are used by the Operation Object must be declared.
     * The tags that are not declared may be organized randomly or based on the tools' logic.
     * Each tag name in the list must be unique.
     *
     * @var list<Tag>
     */
    public $tags = Generator::UNDEFINED;
    /**
     * Additional external documentation.
     *
     * @var ExternalDocumentation
     */
    public $external_docs = Generator::UNDEFINED;
    /**
     * The available webhooks for the API.
     *
     * @since OpenAPI 3.1.0
     * @var list<Webhook>
     */
    public $webhooks = Generator::UNDEFINED;
    /**
     * @var Analysis
     */
    public $_analysis = Generator::UNDEFINED;
    /**
     * @inheritdoc
     */
    public static $_required = ['openapi', 'info'];
    /**
     * @inheritdoc
     */
    public static $_nested = [Info::class => 'info', Server::class => ['servers'], Path_Item::class => ['paths', 'path'], Components::class => 'components', Tag::class => ['tags'], External_Documentation::class => 'externalDocs', Webhook::class => ['webhooks', 'webhook'], Attachable::class => ['attachables']];
    /**
     * @inheritdoc
     */
    public static $_types = [];
    public function __construct(array $properties)
    {
        parent::__construct($properties);
        if ($this->_context->root()->version) {
            // override via `Generator::setVersion()`
            $this->openapi = $this->_context->root()->version;
        } else {
            $this->_context->root()->version = $this->openapi;
        }
    }
    #[\Override]
    public function validate(?Analysis $analysis = null, string $version = Open_Api::DEFAULT_VERSION, ?object $context = null): bool
    {
        $is_valid = parent::validate($analysis, $version, $context);
        if (!in_array($this->openapi, Open_Api::SUPPORTED_VERSIONS)) {
            $this->_context->logger->warning('Unsupported OpenAPI version "' . $this->openapi . '". Allowed versions are: ' . implode(', ', Open_Api::SUPPORTED_VERSIONS));
            $is_valid = false;
        }
        /* paths is optional in 3.1.x */
        if (Open_Api::version_match($version, '3.0.x') && Generator::is_default($this->paths)) {
            $this->_context->logger->warning('Required @OA\PathItem() not found');
            $is_valid = false;
        }
        if (Open_Api::version_match($version, '3.1.x') && Generator::is_default($this->paths) && Generator::is_default($this->webhooks) && Generator::is_default($this->components)) {
            $this->_context->logger->warning('At least one of @OA\PathItem(), @OA\Components() or @OA\Webhook() required');
            $is_valid = false;
        }
        return $is_valid;
    }
    /**
     * Compare OpenApi version numbers.
     *
     * Allows patch version placeholder `x`; e.g. `3.1.x`.
     */
    public static function version_match(string $version1, string $version2): bool
    {
        $expand = static function (string $v): array {
            if (!str_ends_with($v, '.x')) {
                return [$v];
            }
            $minor = str_replace('.x', '', $v);
            return array_filter(self::SUPPORTED_VERSIONS, static fn(string $sv): bool => str_starts_with($sv, $minor));
        };
        $versions1 = $expand($version1);
        $versions2 = $expand($version2);
        return array_intersect($versions1, $versions2) !== [];
    }
    /**
     * Save the OpenAPI documentation to a file.
     */
    public function save_as(string $filename, string $format = 'auto'): void
    {
        if ($format === 'auto') {
            $format = strtolower(substr($filename, -5)) === '.json' ? 'json' : 'yaml';
        }
        $content = strtolower($format) === 'json' ? $this->to_json() : $this->to_yaml();
        if (file_put_contents($filename, $content) === false) {
            throw new Open_Api_Exception('Failed to saveAs("' . $filename . '", "' . $format . '")');
        }
    }
    /**
     * Look up an annotation with a $ref url.
     *
     * @param string $ref The $ref value; example: "#/components/schemas/Product"
     */
    public function ref(string $ref)
    {
        if (!str_starts_with($ref, '#/')) {
            throw new Open_Api_Exception('Unsupported $ref "' . $ref . '", it should start with "#/"');
        }
        return self::resolve_ref($ref, '#/', $this, []);
    }
    /**
     * Recursive helper for ref().
     *
     * @param array|AbstractAnnotation                                     $container
     * @param array<class-string<AbstractAnnotation>,string|array<string>> $mapping
     */
    private static function resolve_ref(string $ref, string $resolved, $container, array $mapping)
    {
        if ($ref === $resolved) {
            return $container;
        }
        $path = substr($ref, strlen($resolved));
        $slash = strpos($path, '/');
        $subpath = $slash === false ? $path : substr($path, 0, $slash);
        $property = Components::ref_decode($subpath);
        $unresolved = $slash === false ? $resolved . $subpath : $resolved . $subpath . '/';
        if (is_object($container)) {
            // support use x-* in ref
            $x_key = str_starts_with($property, 'x-') ? substr($property, 2) : null;
            if ($x_key) {
                if (!is_array($container->x) || !array_key_exists($x_key, $container->x)) {
                    $x_key = null;
                }
            }
            if (property_exists($container, $property) === false && !$x_key) {
                throw new Open_Api_Exception('$ref "' . $ref . '" not found');
            }
            $next_container = $x_key ? $container->x[$x_key] : $container->{$property};
            if ($slash === false) {
                return $next_container;
            }
            $mapping = [];
            foreach ($container::$_nested as $nested_class => $nested) {
                if (is_string($nested) === false && count($nested) === 2 && $nested[0] === $property) {
                    $mapping[$nested_class] = $nested[1];
                }
            }
            return self::resolve_ref($ref, $unresolved, $next_container, $mapping);
        }
        if (is_array($container)) {
            if (array_key_exists($property, $container)) {
                return self::resolve_ref($ref, $unresolved, $container[$property], []);
            }
            foreach ($mapping as $nested_class => $key_field) {
                foreach ($container as $key => $item) {
                    if (is_numeric($key) && is_object($item) && $item instanceof $nested_class && (string) $item->{$key_field} === $property) {
                        return self::resolve_ref($ref, $unresolved, $item, []);
                    }
                }
            }
        }
        throw new Open_Api_Exception('$ref "' . $unresolved . '" not found');
    }
    public function jsonSerialize(): \stdClass
    {
        $data = parent::jsonSerialize();
        if ($this->_context->is_version('3.0.x')) {
            unset($data->webhooks);
        }
        return $data;
    }
}