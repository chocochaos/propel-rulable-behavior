<?php

namespace Chocochaos\Rulable;

use LogicException;
use Propel\Generator\Model\Behavior;
use ReflectionClass;
use ReflectionMethod;
use ReflectionType;

/**
 * Class RulableBehavior
 *
 * @package Chocochaos\Rulable
 */
class RulableBehavior extends Behavior
{
    /**
     * @return string
     */
    public function objectMethods(): string
    {
        return $this->generateMethods('ObjectMeetsRule');
    }

    /**
     * @return string
     */
    public function queryMethods(): string
    {
        return $this->generateMethods('FilterByMeetsRule')
            . $this->generateMethods('FilterByFailsRule');
    }

    /**
     * @param string $templateName
     *
     * @return string
     */
    private function generateMethods(string $templateName): string
    {
        $generatedCode = '';

        foreach ($this->getParameters() as $phpName => $className) {
            $generatedCode .= $this->generateMethod(
                $templateName,
                $phpName,
                $className
            );
        }

        return $generatedCode;
    }

    /**
     * @param string $templateName
     * @param string $phpName
     * @param string $className
     *
     * @return string
     */
    private function generateMethod(
        string $templateName,
        string $phpName,
        string $className
    ): string {
        if (!preg_match('/^[a-zA-Z0-9_\x7f-\xff]*$/', $phpName)) {
            throw new LogicException(
                sprintf('Invalid php name for rule: %s', $phpName)
            );
        }

        if (strpos($className, '\\') === 0) {
            $fullClassName = $className;
        } else {
            $fullClassName = $this->getTable()->getNamespace()
                . '\\Rules\\' . $className;
        }

        if (!class_exists($fullClassName)) {
            throw new LogicException(
                sprintf('Cannot find class: %s', $className)
            );
        }

        $reflect = new ReflectionClass($fullClassName);

        $templateData = [
            'phpName'         => $phpName,
            'shortClassName'  => $reflect->getShortName(),
            'fullClassName'   => $fullClassName,
            'methodParams'    => $this->getMethodParams($reflect),
            'callParams'      => $this->getCallParams($reflect),
            'docBlockParams'  => $this->getDocblockParams($reflect),
            'childQueryClass' => 'Child'
                . $this->getTable()->getPhpName() . 'Query'
        ];
        $generatedFunction = $this->renderTemplate(
            $templateName,
            $templateData
        );

        return $generatedFunction;
    }

    /**
     * @param ReflectionClass $reflect
     *
     * @return string
     */
    private function getMethodParams(ReflectionClass $reflect): string
    {
        $methodParams = '';
        $constructor = $reflect->getConstructor();
        if ($constructor instanceof ReflectionMethod) {
            $constructorParams = $constructor->getParameters();

            foreach ($constructorParams as $constructorParam) {
                if (!empty($methodParams)) {
                    $methodParams .= ', ';
                }

                $type = $constructorParam->getType();
                if ($type instanceof ReflectionType) {
                    if ($constructorParam->isOptional()) {
                        $methodParams .= '?';
                    }
                    $typeName = $type->getName();
                    // If it contains a backslah, but does not start with one,
                    // add a backslah to the strat to get the fully qualified
                    // class name.
                    if (strpos($typeName, '\\') > 1) {
                        $typeName = '\\' . $typeName;
                    }
                    $methodParams .= $typeName . ' ';
                }

                $methodParams .= '$' . $constructorParam->getName();

                try {
                    $defaultValue = $constructorParam->getDefaultValue();
                    $methodParams .= ' = ' . var_export($defaultValue, true);
                } catch (\Throwable $t) {
                    // do nothing, no default
                }
            }
        }

        return $methodParams;
    }

    /**
     * @param ReflectionClass $reflect
     *
     * @return string
     */
    private function getCallParams(ReflectionClass $reflect): string
    {
        $callParams = '';
        $constructor = $reflect->getConstructor();
        if ($constructor instanceof ReflectionMethod) {
            $constructorParams = $constructor->getParameters();

            foreach ($constructorParams as $constructorParam) {
                if (!empty($callParams)) {
                    $callParams .= ', ';
                }

                $callParams .= '$' . $constructorParam->getName();
            }
        }

        return $callParams;
    }

    /**
     * @param ReflectionClass $reflect
     *
     * @return string
     */
    private function getDocblockParams(ReflectionClass $reflect): string
    {
        $docblockParams = '';
        $constructor = $reflect->getConstructor();
        if ($constructor instanceof ReflectionMethod) {
            $constructorParams = $constructor->getParameters();

            foreach ($constructorParams as $constructorParam) {
                $docblockParams .= "\n * @param ";

                $type = $constructorParam->getType();
                if ($type instanceof ReflectionType) {
                    $typeName = $type->getName();
                    // If it contains a backslah, but does not start with one,
                    // add a backslah to the strat to get the fully qualified
                    // class name.
                    if (strpos($typeName, '\\') > 1) {
                        $typeName = '\\' . $typeName;
                    }
                    $docblockParams .= $typeName;

                    if ($constructorParam->isOptional()) {
                        $docblockParams .= '|null';
                    }

                    $docblockParams .= ' ';
                }

                $docblockParams .= '$' . $constructorParam->getName();
            }

            if (!empty($docblockParams)) {
                $docblockParams .= "\n *";
            }
        }

        return $docblockParams;
    }
}
