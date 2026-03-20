<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Analysers;

use Doctrine\Common\Annotations\Doc_Parser;
use Open_Api\Annotations as OA;
use Open_Api\Context;
use Open_Api\Generator;
/**
 * Extract swagger-php annotations from a [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) using Doctrine's DocParser.
 */
class Doc_Block_Parser
{
    protected Doc_Parser $doc_parser;
    /**
     * @param array<string, class-string> $aliases
     */
    public function __construct(array $aliases = [])
    {
        if (Doc_Block_Parser::is_enabled()) {
            $doc_parser = new Doc_Parser();
            $doc_parser->set_ignore_not_imported_annotations(true);
            $doc_parser->set_imports($aliases);
            $this->doc_parser = $doc_parser;
        }
    }
    /**
     * Check if we can process annotations.
     */
    public static function is_enabled(): bool
    {
        return class_exists('Doctrine\Common\Annotations\DocParser');
    }
    /**
     * @param array<string, class-string> $aliases
     */
    public function set_aliases(array $aliases): void
    {
        $this->doc_parser->set_imports($aliases);
    }
    /**
     * Use doctrine to parse the comment block and return the detected annotations.
     *
     * @param string $comment a T_DOC_COMMENT
     *
     * @return list<OA\AbstractAnnotation|object>
     */
    public function from_comment(string $comment, Context $context): array
    {
        $context->comment = $comment;
        try {
            Generator::$context = $context;
            if ($context->is('annotations') === false) {
                $context->annotations = [];
            }
            return $this->doc_parser->parse($comment, $context->get_debug_location());
        } catch (\Exception $exception) {
            if (preg_match('/^(.+) at position ([0-9]+) in ' . preg_quote((string) $context, '/') . '\.$/', $exception->get_message(), $matches)) {
                $error_message = $matches[1];
                $error_pos = (int) $matches[2];
                $at_pos = strpos($comment, '@');
                $context->line -= substr_count($comment, "\n", $at_pos + $error_pos) + 1;
                $lines = explode("\n", substr($comment, $at_pos, $error_pos));
                $context->character = strlen(array_pop($lines)) + 1;
                // position starts at 0 character starts at 1
                $context->logger->error($error_message . ' in ' . $context, ['exception' => $exception]);
            } else {
                $context->logger->error($exception->get_message() . ($context->filename ? '; file=' . $context->filename : ''), ['exception' => $exception]);
            }
            return [];
        } finally {
            Generator::$context = null;
        }
    }
}