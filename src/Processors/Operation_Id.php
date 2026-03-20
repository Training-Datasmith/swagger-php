<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Generator;
/**
 * Generate the OperationId based on the context of the OpenApi annotation.
 */
class Operation_Id
{
    public function __construct(protected bool $hash = true)
    {
    }
    public function is_hash(): bool
    {
        return $this->hash;
    }
    /**
     *  If set to <code>true</code> generate ids (md5) instead of clear text operation ids.
     */
    public function set_hash(bool $hash): Operation_Id
    {
        $this->hash = $hash;
        return $this;
    }
    public function __invoke(Analysis $analysis): void
    {
        $all_operations = $analysis->get_annotations_of_type(OA\Operation::class);
        /** @var OA\Operation $operation */
        foreach ($all_operations as $operation) {
            if (null === $operation->operation_id) {
                $operation->operation_id = Generator::UNDEFINED;
            }
            if (!Generator::is_default($operation->operation_id)) {
                continue;
            }
            $context = $operation->_context;
            if ($context) {
                $source = $context->class ?? $context->interface ?? $context->trait;
                $operation_id = null;
                if ($source) {
                    $method = $context->method ? '::' . $context->method : '';
                    $operation_id = $context->namespace ? $context->namespace . '\\' . $source . $method : $source . $method;
                } elseif ($context->method) {
                    $operation_id = $context->method;
                }
                if ($operation_id) {
                    $operation_id = strtoupper($operation->method) . '::' . $operation->path . '::' . $operation_id;
                    $operation->operation_id = $this->hash ? md5($operation_id) : $operation_id;
                }
            }
        }
    }
}