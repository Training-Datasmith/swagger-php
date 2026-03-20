<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors\Concerns;

use Open_Api\Context;
trait Ref_Trait
{
    protected function to_ref_key(Context $context, ?string $name): string
    {
        $fqn = strtolower($context->fully_qualified_name($name) ?? '');
        return ltrim($fqn, '\\');
    }
    protected function is_ref(?string $ref): bool
    {
        return $ref && str_starts_with($ref, '#/');
    }
}