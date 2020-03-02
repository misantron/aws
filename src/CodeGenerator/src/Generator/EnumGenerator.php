<?php

declare(strict_types=1);

namespace AsyncAws\CodeGenerator\Generator;

use AsyncAws\CodeGenerator\Definition\Shape;
use AsyncAws\CodeGenerator\File\FileWriter;
use AsyncAws\CodeGenerator\Generator\Naming\ClassName;
use AsyncAws\CodeGenerator\Generator\Naming\NamespaceRegistry;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;

/**
 * Generate Enum shapeused by Input and Result classes.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @internal
 */
class EnumGenerator
{
    /**
     * @var NamespaceRegistry
     */
    private $namespaceRegistry;

    /**
     * @var FileWriter
     */
    private $fileWriter;

    /**
     * @var ClassName[]
     */
    private $generated = [];

    public function __construct(NamespaceRegistry $namespaceRegistry, FileWriter $fileWriter)
    {
        $this->namespaceRegistry = $namespaceRegistry;
        $this->fileWriter = $fileWriter;
    }

    /**
     * Generate classes for the input. Ie, the request of the API call.
     */
    public function generate(Shape $shape): ClassName
    {
        if (isset($this->generated[$shape->getName()])) {
            return $this->generated[$shape->getName()];
        }

        $this->generated[$shape->getName()] = $className = $this->namespaceRegistry->getEnum($shape);

        $namespace = new PhpNamespace($className->getNamespace());
        $class = $namespace->addClass($className->getName());

        $consts = [];
        foreach ($shape->getEnum() as $value) {
            $consts[self::canonicalizeName($value)] = $value;
        }
        \ksort($consts);
        $availableCode = '';
        foreach ($consts as $constName => $constValue) {
            $class->addConstant($constName, $constValue)->setVisibility(ClassType::VISIBILITY_PUBLIC);
            $availableCode .= 'self::' . $constName . ' => true,' . "\n";
        }
        $class->addConstant('AVAILABLE_' . \strtoupper($className->getName()), null)->setVisibility(ClassType::VISIBILITY_PUBLIC)
        ->setValue(new Literal("[\n$availableCode]"));

        $this->fileWriter->write($namespace);

        return $className;
    }

    public static function canonicalizeName(string $name): string
    {
        return  preg_replace('/[^A-Z\d ]+/', '_', strtoupper(\preg_replace('/([a-z])([A-Z\d])/', '\\1_\\2', $name)));
    }
}