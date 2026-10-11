<?php

declare (strict_types=1);
namespace PHPStan\Rules\Exceptions;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_diff;
use function array_map;
use function count;
use function implode;
use function sprintf;
/**
 * @implements Rule<MethodReturnStatementsNode>
 */
final class TooWideMethodThrowTypeRule implements Rule
{
    private FileTypeMapper $fileTypeMapper;
    private \PHPStan\Rules\Exceptions\TooWideThrowTypeCheck $check;
    private bool $checkProtectedAndPublicMethods;
    private bool $tooWideImplicitThrows;
    public function __construct(FileTypeMapper $fileTypeMapper, \PHPStan\Rules\Exceptions\TooWideThrowTypeCheck $check, bool $checkProtectedAndPublicMethods, bool $tooWideImplicitThrows)
    {
        $this->fileTypeMapper = $fileTypeMapper;
        $this->check = $check;
        $this->checkProtectedAndPublicMethods = $checkProtectedAndPublicMethods;
        $this->tooWideImplicitThrows = $tooWideImplicitThrows;
    }
    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $statementResult = $node->statementResult;
        $method = $node->methodReflection;
        $isFirstDeclaration = $method->getPrototype()->getDeclaringClass() === $method->declaringClass;
        if (!$method->isPrivate()) {
            if (!$method->declaringClass->isFinal() && !$method->isFinal()->yes()) {
                if (!$this->checkProtectedAndPublicMethods) {
                    return [];
                }
                if ($isFirstDeclaration) {
                    return [];
                }
            }
        }
        $throwType = $method->getThrowType();
        if ($throwType === null) {
            return [];
        }
        $unusedThrowClasses = $this->check->check($throwType, $statementResult->getThrowPoints());
        if (count($unusedThrowClasses) === 0) {
            return [];
        }
        $isThrowTypeExplicit = $this->isThrowTypeExplicit($node->getDocComment(), $scope, $node->classReflection, $method->getName());
        if (!$isThrowTypeExplicit && !$this->tooWideImplicitThrows) {
            return [];
        }
        $throwClasses = array_map(static fn($type) => $type->describe(VerbosityLevel::typeOnly()), TypeUtils::flattenTypes($throwType));
        $usedClasses = array_diff($throwClasses, $unusedThrowClasses);
        $errors = [];
        foreach ($unusedThrowClasses as $throwClass) {
            $builder = RuleErrorBuilder::message(sprintf('Method %s::%s() has %s in PHPDoc @throws tag but it\'s not thrown.', $method->declaringClass->getDisplayName(), $method->getName(), $throwClass))->identifier('throws.unusedType');
            if (!$isThrowTypeExplicit) {
                $builder->tip(sprintf('You can narrow the thrown type with PHPDoc tag @throws %s.', count($usedClasses) === 0 ? 'void' : implode('|', $usedClasses)));
            }
            $errors[] = $builder->build();
        }
        return $errors;
    }
    private function isThrowTypeExplicit(?Doc $docComment, Scope $scope, ClassReflection $classReflection, string $methodName): bool
    {
        if ($docComment === null) {
            return \false;
        }
        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($scope->getFile(), $classReflection->getName(), $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null, $methodName, $docComment->getText());
        return $resolvedPhpDoc->getThrowsTag() !== null;
    }
}
