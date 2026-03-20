<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Analysers;

use Php_Parser\Error;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Parser\Node\Stmt\Enum_;
use Php_Parser\Node\Stmt\Interface_;
use Php_Parser\Node\Stmt\Namespace_;
use Php_Parser\Node\Stmt\Trait_;
use Php_Parser\Node\Stmt\Use_;
use Php_Parser\Parser_Factory;
/**
 * High-level, PHP-token-based, scanner.
 */
class Token_Scanner
{
    /**
     * Scan a given file for all classes, interfaces, and traits.
     *
     * @return array{
     *     'uses': array<string, class-string>,
     *     'interfaces': list<class-string>,
     *     'traits': list<class-string>,
     *     'enums': list<class-string>,
     *     'methods': list<string>,
     *     'properties': list<string>,
     * } File details
     */
    public function scan_file(string $filename): array
    {
        $parser = (new Parser_Factory())->create_for_newest_supported_version();
        try {
            $stmts = $parser->parse(file_get_contents($filename));
        } catch (Error $error) {
            throw new \RuntimeException($error->get_message(), $error->get_code(), $error);
        }
        $result = [];
        $result += $this->collect_stmts($stmts, '');
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                $namespace = (string) $stmt->name;
                $result += $this->collect_stmts($stmt->stmts, $namespace);
            }
        }
        return $result;
    }
    protected function collect_stmts(array $stmts, string $namespace): array
    {
        /** @var array $uses */
        $uses = [];
        $resolve = static function (string $name) use ($namespace, &$uses) {
            if (array_key_exists($name, $uses)) {
                return $uses[$name];
            }
            return $namespace . '\\' . $name;
        };
        $details = static function () use (&$uses): array {
            return ['uses' => $uses, 'interfaces' => [], 'traits' => [], 'enums' => [], 'methods' => [], 'properties' => []];
        };
        $result = [];
        foreach ($stmts as $stmt) {
            switch ($stmt::class) {
                case Use_::class:
                    $uses += $this->collect_uses($stmt);
                    break;
                case Class_::class:
                    $result += $this->collect_class($stmt, $details(), $resolve);
                    break;
                case Interface_::class:
                    $result += $this->collect_interface($stmt, $details(), $resolve);
                    break;
                case Trait_::class:
                case Enum_::class:
                    $result += $this->collect_classlike($stmt, $details(), $resolve);
                    break;
            }
        }
        return $result;
    }
    protected function collect_uses(Use_ $stmt): array
    {
        $uses = [];
        foreach ($stmt->uses as $use) {
            $uses[(string) $use->get_alias()] = (string) $use->name;
        }
        return $uses;
    }
    protected function collect_classlike(Class_Like $stmt, array $details, callable $resolve): array
    {
        foreach ($stmt->get_properties() as $properties) {
            foreach ($properties->props as $prop) {
                $details['properties'][] = (string) $prop->name;
            }
        }
        foreach ($stmt->get_methods() as $method) {
            $details['methods'][] = (string) $method->name;
        }
        foreach ($stmt->get_trait_uses() as $trait_use) {
            foreach ($trait_use->traits as $trait) {
                $details['traits'][] = $resolve((string) $trait);
            }
        }
        return [$resolve($stmt->name->name) => $details];
    }
    protected function collect_class(Class_ $stmt, array $details, callable $resolve): array
    {
        foreach ($stmt->implements as $implement) {
            $details['interfaces'][] = $resolve((string) $implement);
        }
        // promoted properties
        if ($ctor = $stmt->get_method('__construct')) {
            foreach ($ctor->get_params() as $param) {
                if ($param->flags) {
                    $details['properties'][] = $param->var->name;
                }
            }
        }
        return $this->collect_classlike($stmt, $details, $resolve);
    }
    protected function collect_interface(Interface_ $stmt, array $details, callable $resolve): array
    {
        foreach ($stmt->extends as $extend) {
            $details['interfaces'][] = $resolve((string) $extend);
        }
        return $this->collect_classlike($stmt, $details, $resolve);
    }
}