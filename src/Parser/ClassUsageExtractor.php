<?php

namespace PhpMigrationAnalyzer\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Extract class usage from AST
 */
class ClassUsageExtractor extends NodeVisitorAbstract
{
    private array $classUsages = [];
    private string $currentFile = '';

    /**
     * Set current file being processed
     */
    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
        $this->classUsages = [];
    }

    /**
     * Visit node
     */
    public function enterNode(Node $node)
    {
        // Class instantiations (new ClassName())
        if ($node instanceof Node\Expr\New_) {
            if ($node->class instanceof Node\Name) {
                $className = $node->class->toString();
                $this->addClassUsage($className, $node, 'instantiation');
            }
        }

        // Instanceof checks
        if ($node instanceof Node\Expr\Instanceof_) {
            if ($node->class instanceof Node\Name) {
                $className = $node->class->toString();
                $this->addClassUsage($className, $node, 'instanceof');
            }
        }

        // Class constant access (ClassName::CONSTANT)
        if ($node instanceof Node\Expr\ClassConstFetch) {
            if ($node->class instanceof Node\Name) {
                $className = $node->class->toString();
                $this->addClassUsage($className, $node, 'const_access');
            }
        }

        // Type hints in function parameters
        if ($node instanceof Node\Param) {
            if ($node->type instanceof Node\Name) {
                $className = $node->type->toString();
                $this->addClassUsage($className, $node, 'type_hint');
            }
        }

        // Catch blocks
        if ($node instanceof Node\Stmt\Catch_) {
            foreach ($node->types as $type) {
                if ($type instanceof Node\Name) {
                    $className = $type->toString();
                    $this->addClassUsage($className, $node, 'catch');
                }
            }
        }

        // Class extends
        if ($node instanceof Node\Stmt\Class_) {
            if ($node->extends instanceof Node\Name) {
                $className = $node->extends->toString();
                $this->addClassUsage($className, $node, 'extends');
            }

            // Class implements
            foreach ($node->implements as $interface) {
                if ($interface instanceof Node\Name) {
                    $className = $interface->toString();
                    $this->addClassUsage($className, $node, 'implements');
                }
            }
        }

        // Interface extends
        if ($node instanceof Node\Stmt\Interface_) {
            foreach ($node->extends as $interface) {
                if ($interface instanceof Node\Name) {
                    $className = $interface->toString();
                    $this->addClassUsage($className, $node, 'extends');
                }
            }
        }

        // Trait use
        if ($node instanceof Node\Stmt\TraitUse) {
            foreach ($node->traits as $trait) {
                if ($trait instanceof Node\Name) {
                    $className = $trait->toString();
                    $this->addClassUsage($className, $node, 'use_trait');
                }
            }
        }

        return null;
    }

    /**
     * Add class usage to the list
     */
    private function addClassUsage(string $name, Node $node, string $type): void
    {
        $this->classUsages[] = [
            'name' => $name,
            'type' => $type,
            'line' => $node->getStartLine(),
            'file' => $this->currentFile,
        ];
    }

    /**
     * Get all class usages
     */
    public function getClassUsages(): array
    {
        return $this->classUsages;
    }

    /**
     * Get unique class names
     */
    public function getUniqueClassNames(): array
    {
        $names = array_map(function ($usage) {
            return $usage['name'];
        }, $this->classUsages);

        return array_unique($names);
    }

    /**
     * Get usages by class name
     */
    public function getUsagesByName(string $name): array
    {
        return array_filter($this->classUsages, function ($usage) use ($name) {
            return $usage['name'] === $name;
        });
    }

    /**
     * Reset extractor
     */
    public function reset(): void
    {
        $this->classUsages = [];
        $this->currentFile = '';
    }
}
