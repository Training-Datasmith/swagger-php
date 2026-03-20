<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Processors;

use Open_Api\Analysis;
use Open_Api\Annotations as OA;
use Open_Api\Context;
use Open_Api\Generator;
use Open_Api\Generator_Aware_Interface;
use Open_Api\Generator_Aware_Trait;
/**
 * Use the RequestBody context to extract useful information and inject that into the annotation.
 */
class Augment_Request_Body implements Generator_Aware_Interface
{
    use Generator_Aware_Trait;
    public function __invoke(Analysis $analysis): void
    {
        $request_bodies = $analysis->get_annotations_of_type(OA\Request_Body::class);
        $this->augment_request_body($analysis, $request_bodies);
    }
    /**
     * @param array<OA\RequestBody> $requestBodies
     */
    protected function augment_request_body(Analysis $analysis, array $request_bodies): void
    {
        foreach ($request_bodies as $request_body) {
            if (!$request_body->is_root(OA\Request_Body::class)) {
                continue;
            }
            $context = $request_body->_context;
            if (Generator::is_default($request_body->request)) {
                if ($context->is('class')) {
                    $request_body->request = $request_body->_context->class;
                } elseif ($context->is('interface')) {
                    $request_body->request = $request_body->_context->interface;
                } elseif ($context->is('trait')) {
                    $request_body->request = $request_body->_context->trait;
                } elseif ($context->is('enum')) {
                    $request_body->request = $request_body->_context->enum;
                }
            }
            if ($context->reflector instanceof \ReflectionParameter) {
                $schema = new OA\Schema(['_context' => new Context(['reflector' => $context->reflector], $context)]);
                $this->generator->get_type_resolver()->augment_schema_type($analysis, $schema, OA\Request_Body::class);
                if (Generator::is_default($request_body->ref)) {
                    $request_body->ref = $schema->ref;
                }
                if (Generator::is_default($request_body->required)) {
                    $request_body->required = !$schema->is_nullable();
                }
            }
        }
    }
}