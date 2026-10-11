<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * Checks whether the name of a dynamically accessed variable or member
 * (`$$name`, `$obj->{$name}`, `$obj->{$name}()`, `Foo::{$name}()`,
 * `Foo::${$name}`, `Foo::{$name}`) can actually be used as a name at runtime.
 *
 * Gated behind the `checkNonStringableDynamicAccess` feature toggle.
 */
#[AutowiredService]
final class NonStringableDynamicAccessCheck
{
    private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;
    private bool $checkNonStringableDynamicAccess;
    public function __construct(
        \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper,
        #[AutowiredParameter('%featureToggles.checkNonStringableDynamicAccess%')]
        bool $checkNonStringableDynamicAccess
    )
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
        $this->checkNonStringableDynamicAccess = $checkNonStringableDynamicAccess;
    }
    /**
     * For names that PHP casts to string at runtime (variable variables,
     * property and static property names) objects implementing __toString are
     * accepted.
     *
     * @param Name|Expr|null $classNode the node whose name/type fills the leading `%s` placeholder, or null when the message has none
     * @param 'property.nameNotString'|'staticProperty.nameNotString'|'variable.nameNotString' $identifier
     * @return list<IdentifierRuleError>
     */
    public function checkStringCastableName(Scope $scope, Expr $name, string $messageFormat, $classNode, string $identifier): array
    {
        if (!$this->checkNonStringableDynamicAccess) {
            return [];
        }
        $nameType = $this->ruleLevelHelper->findTypeToCheck($scope, $name, '', static fn(\PHPStan\Type\Type $type) => !$type->toString() instanceof \PHPStan\Type\ErrorType && $type->toString()->isString()->yes())->type;
        if (!$nameType instanceof ErrorType && ($nameType->toString() instanceof ErrorType || !$nameType->toString()->isString()->yes())) {
            return [$this->buildError($scope, $name, $scope->getType($name), $messageFormat, $classNode, $identifier)];
        }
        return [];
    }
    /**
     * For names that must be actual strings (method, static method and class
     * constant names) objects implementing __toString are not accepted.
     *
     * @param Name|Expr|null $classNode the node whose name/type fills the leading `%s` placeholder, or null when the message has none
     * @param 'classConstant.nameNotString'|'method.nameNotString'|'staticMethod.nameNotString' $identifier
     * @return list<IdentifierRuleError>
     */
    public function checkStringName(Scope $scope, Expr $name, string $messageFormat, $classNode, string $identifier): array
    {
        if (!$this->checkNonStringableDynamicAccess) {
            return [];
        }
        $nameType = $this->ruleLevelHelper->findTypeToCheck($scope, $name, '', static fn(\PHPStan\Type\Type $type) => $type->isString()->yes())->type;
        if (!$nameType instanceof ErrorType && !$nameType->isString()->yes()) {
            return [$this->buildError($scope, $name, $nameType, $messageFormat, $classNode, $identifier)];
        }
        return [];
    }
    /**
     * @param Name|Expr|null $classNode
     * @param 'classConstant.nameNotString'|'method.nameNotString'|'property.nameNotString'|'staticMethod.nameNotString'|'staticProperty.nameNotString'|'variable.nameNotString' $identifier
     */
    private function buildError(Scope $scope, Expr $name, Type $nameType, string $messageFormat, $classNode, string $identifier): \PHPStan\Rules\IdentifierRuleError
    {
        $messageArgs = [];
        if ($classNode !== null) {
            $messageArgs[] = $classNode instanceof Name ? $scope->resolveName($classNode) : $scope->getType($classNode)->describe(VerbosityLevel::typeOnly());
        }
        $messageArgs[] = $nameType->describe(VerbosityLevel::precise());
        return \PHPStan\Rules\RuleErrorBuilder::message(sprintf($messageFormat, ...$messageArgs))->line($name->getStartLine())->identifier($identifier)->build();
    }
}
