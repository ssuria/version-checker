<?php

namespace PhpMigrationAnalyzer\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Extract function calls from AST
 */
class FunctionCallExtractor extends NodeVisitorAbstract
{
    private array $functionCalls = [];
    private string $currentFile = '';

    /**
     * Set current file being processed
     */
    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
        $this->functionCalls = [];
    }

    /**
     * Visit node
     */
    public function enterNode(Node $node)
    {
        // Function calls
        if ($node instanceof Node\Expr\FuncCall) {
            if ($node->name instanceof Node\Name) {
                $functionName = $node->name->toString();
                $this->addFunctionCall($functionName, $node);
            }
        }

        // Method calls
        if ($node instanceof Node\Expr\MethodCall) {
            if ($node->name instanceof Node\Identifier) {
                $methodName = $node->name->toString();
                $this->addFunctionCall($methodName, $node, 'method');
            }
        }

        // Static method calls
        if ($node instanceof Node\Expr\StaticCall) {
            if ($node->name instanceof Node\Identifier) {
                $className = '';
                if ($node->class instanceof Node\Name) {
                    $className = $node->class->toString();
                }
                $methodName = $node->name->toString();
                $fullName = $className ? "{$className}::{$methodName}" : $methodName;
                $this->addFunctionCall($fullName, $node, 'static_method');
            }
        }

        return null;
    }

    /**
     * Add function call to the list
     */
    private function addFunctionCall(string $name, Node $node, string $type = 'function'): void
    {
        $this->functionCalls[] = [
            'name' => $name,
            'type' => $type,
            'line' => $node->getStartLine(),
            'file' => $this->currentFile,
        ];
    }

    /**
     * Get all function calls
     */
    public function getFunctionCalls(): array
    {
        return $this->functionCalls;
    }

    /**
     * Get unique function names
     */
    public function getUniqueFunctionNames(): array
    {
        $names = array_map(function ($call) {
            return $call['name'];
        }, $this->functionCalls);

        return array_unique($names);
    }

    /**
     * Get calls by function name
     */
    public function getCallsByName(string $name): array
    {
        return array_filter($this->functionCalls, function ($call) use ($name) {
            return $call['name'] === $name;
        });
    }

    /**
     * Reset extractor
     */
    public function reset(): void
    {
        $this->functionCalls = [];
        $this->currentFile = '';
    }
}
