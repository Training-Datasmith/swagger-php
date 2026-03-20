<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Analysers;

use Open_Api\Annotations as OA;
use Open_Api\Context;
use Open_Api\Generator;
use Open_Api\Generator_Aware_Trait;
class Doc_Block_Annotation_Factory implements Annotation_Factory_Interface
{
    use Generator_Aware_Trait;
    protected ?Doc_Block_Parser $doc_block_parser = null;
    public function __construct(?Doc_Block_Parser $doc_block_parser = null)
    {
        $this->doc_block_parser = $doc_block_parser ?: new Doc_Block_Parser();
    }
    public function is_supported(): bool
    {
        return Doc_Block_Parser::is_enabled();
    }
    public function set_generator(Generator $generator): self
    {
        $this->generator = $generator;
        $this->doc_block_parser->set_aliases($generator->get_aliases());
        return $this;
    }
    public function build(\Reflector $reflector, Context $context): array
    {
        $aliases = $this->generator ? $this->generator->get_aliases() : [];
        if (method_exists($reflector, 'getShortName') && method_exists($reflector, 'getName')) {
            $aliases[strtolower((string) $reflector->get_short_name())] = $reflector->get_name();
        }
        if ($context->with('scanned')) {
            $details = $context->scanned;
            foreach ($details['uses'] as $alias => $name) {
                $alias_key = strtolower((string) $alias);
                if ($name != $alias && !array_key_exists($alias_key, $aliases)) {
                    // real aliases only
                    $aliases[strtolower((string) $alias)] = $name;
                }
            }
        }
        $this->doc_block_parser->set_aliases($aliases);
        if (method_exists($reflector, 'getDocComment') && $comment = $reflector->get_doc_comment()) {
            $annotations = [];
            foreach ($this->doc_block_parser->from_comment($comment, $context) as $instance) {
                if ($instance instanceof OA\Abstract_Annotation) {
                    $annotations[] = $instance;
                } else {
                    if ($context->is('other') === false) {
                        $context->other = [];
                    }
                    $context->other[] = $instance;
                }
            }
            return $annotations;
        }
        return [];
    }
}