<?php

namespace PhpMigrationAnalyzer\Parser;

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

/**
 * PHP Parser wrapper using nikic/php-parser
 */
class PHPParser
{
    private \PhpParser\Parser $parser;

    public function __construct()
    {
        $factory = new ParserFactory();
        $this->parser = $factory->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * Parse PHP code and return AST
     *
     * @param string $code PHP code to parse
     * @return array|null
     */
    public function parse(string $code): ?array
    {
        try {
            return $this->parser->parse($code);
        } catch (Error $e) {
            // Parse error
            return null;
        }
    }

    /**
     * Parse file and return AST
     *
     * @param string $filePath Path to PHP file
     * @return array|null
     */
    public function parseFile(string $filePath): ?array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        $code = file_get_contents($filePath);
        return $this->parse($code);
    }

    /**
     * Traverse AST with visitor
     *
     * @param array $ast AST nodes
     * @param \PhpParser\NodeVisitor $visitor Visitor instance
     * @return array Modified AST
     */
    public function traverse(array $ast, \PhpParser\NodeVisitor $visitor): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);

        return $traverser->traverse($ast);
    }

    /**
     * Get parser instance
     */
    public function getParser(): \PhpParser\Parser
    {
        return $this->parser;
    }
}
