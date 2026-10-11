<?php

declare (strict_types=1);
namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<Node\Expr\ArrayDimFetch>
 */
#[RegisteredRule(3)]
final class InvalidKeyInArrayDimFetchRule implements Rule
{
    private RuleLevelHelper $ruleLevelHelper;
    private PhpVersion $phpVersion;
    private bool $reportMaybes;
    private bool $reportNonIntStringArrayKey;
    public function __construct(
        RuleLevelHelper $ruleLevelHelper,
        PhpVersion $phpVersion,
        #[AutowiredParameter]
        bool $reportMaybes,
        #[AutowiredParameter]
        bool $reportNonIntStringArrayKey
    )
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
        $this->phpVersion = $phpVersion;
        $this->reportMaybes = $reportMaybes;
        $this->reportNonIntStringArrayKey = $reportNonIntStringArrayKey;
    }
    public function getNodeType(): string
    {
        return Node\Expr\ArrayDimFetch::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->dim === null) {
            return [];
        }
        $varType = $this->ruleLevelHelper->findTypeToCheck($scope, $node->var, '', static fn(\PHPStan\Type\Type $varType): bool => $varType->isArray()->no())->type;
        if ($varType instanceof ErrorType) {
            return [];
        }
        $isArray = $varType->isArray();
        if ($isArray->no() || $isArray->maybe() && !$this->reportMaybes) {
            return [];
        }
        $allowedArrayKeys = \PHPStan\Rules\Arrays\AllowedArrayKeysTypes::getType($this->phpVersion, $this->reportNonIntStringArrayKey);
        $dimensionType = $this->ruleLevelHelper->findTypeToCheck($scope, $node->dim, '', static fn(\PHPStan\Type\Type $dimType): bool => $allowedArrayKeys->isSuperTypeOf($dimType)->yes())->type;
        if ($dimensionType instanceof ErrorType) {
            return [];
        }
        $isSuperType = $allowedArrayKeys->isSuperTypeOf($dimensionType);
        if ($isSuperType->yes() || $isSuperType->maybe() && !$this->reportMaybes) {
            return [];
        }
        return [RuleErrorBuilder::message(sprintf('%s array key type %s.', $isArray->yes() && $isSuperType->no() ? 'Invalid' : 'Possibly invalid', $dimensionType->describe(VerbosityLevel::typeOnly())))->identifier('offsetAccess.invalidOffset')->build()];
    }
}
