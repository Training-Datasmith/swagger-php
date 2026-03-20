<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Analysers;

use Open_Api\Annotations as OA;
use Open_Api\Context;
use Open_Api\Generator_Aware_Interface;
interface Annotation_Factory_Interface extends Generator_Aware_Interface
{
    /**
     * Checks if this factory is supported by the current runtime.
     */
    public function is_supported(): bool;
    /**
     * @return list<OA\AbstractAnnotation> top level annotations
     */
    public function build(\Reflector $reflector, Context $context): array;
}