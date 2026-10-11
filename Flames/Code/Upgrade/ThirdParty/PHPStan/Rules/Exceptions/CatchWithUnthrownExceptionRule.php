<?php

declare (strict_types=1);
namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CatchWithUnthrownExceptionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<CatchWithUnthrownExceptionNode>
 */
#[RegisteredRule(4)]
final class CatchWithUnthrownExceptionRule implements Rule
{
    private \PHPStan\Rules\Exceptions\ExceptionTypeResolver $exceptionTypeResolver;
    private bool $reportUncheckedExceptionDeadCatch;
    public function __construct(
        #[AutowiredParameter('@exceptionTypeResolver')]
        \PHPStan\Rules\Exceptions\ExceptionTypeResolver $exceptionTypeResolver,
        #[AutowiredParameter('%exceptions.reportUncheckedExceptionDeadCatch%')]
        bool $reportUncheckedExceptionDeadCatch
    )
    {
        $this->exceptionTypeResolver = $exceptionTypeResolver;
        $this->reportUncheckedExceptionDeadCatch = $reportUncheckedExceptionDeadCatch;
    }
    public function getNodeType(): string
    {
        return CatchWithUnthrownExceptionNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->caughtType instanceof NeverType) {
            return [RuleErrorBuilder::message(sprintf('Dead catch - %s is already caught above.', $node->originalCaughtType->describe(VerbosityLevel::typeOnly())))->line($node->getStartLine())->identifier('catch.alreadyCaught')->build()];
        }
        if (!$this->reportUncheckedExceptionDeadCatch) {
            $isCheckedException = \false;
            foreach ($node->caughtType->getObjectClassNames() as $objectClassName) {
                if ($this->exceptionTypeResolver->isCheckedException($objectClassName, $scope)) {
                    $isCheckedException = \true;
                    break;
                }
            }
            if (!$isCheckedException) {
                return [];
            }
        }
        return [RuleErrorBuilder::message(sprintf('Dead catch - %s is never thrown in the try block.', $node->caughtType->describe(VerbosityLevel::typeOnly())))->line($node->getStartLine())->identifier('catch.neverThrown')->build()];
    }
}
