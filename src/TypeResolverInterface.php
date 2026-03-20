<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api;

use Open_Api\Annotations as OA;
interface Type_Resolver_Interface
{
    public const NATIVE_TYPE_MAP = ['mixed' => 'mixed', 'string' => 'string', 'array' => 'array', 'byte' => ['string', 'byte'], 'boolean' => 'boolean', 'bool' => 'boolean', 'int' => 'integer', 'integer' => 'integer', 'long' => ['integer', 'long'], 'float' => ['number', 'float'], 'double' => ['number', 'double'], 'date' => ['string', 'date'], 'datetime' => ['string', 'date-time'], '\datetime' => ['string', 'date-time'], 'datetimeimmutable' => ['string', 'date-time'], '\datetimeimmutable' => ['string', 'date-time'], 'datetimeinterface' => ['string', 'date-time'], '\datetimeinterface' => ['string', 'date-time'], 'number' => 'number', 'object' => 'object'];
    public function map_native_type(OA\Schema $schema, $type): bool;
    public function native2spec(string $type): string;
    /**
     * @param class-string<OA\AbstractAnnotation> $sourceClass optional source class type hint for resolving references to
     *                                                         other types as `OA\Schema`
     */
    public function augment_schema_type(Analysis $analysis, OA\Schema $schema, string $source_class = OA\Schema::class): void;
}