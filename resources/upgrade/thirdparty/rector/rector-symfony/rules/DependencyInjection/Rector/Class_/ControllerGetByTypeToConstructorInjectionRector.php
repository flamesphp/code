<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DependencyInjection\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Symfony\DependencyInjection\ContainerGetToConstructorInjectionReplacer;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyClass;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DependencyInjection\Rector\Class_\ControllerGetByTypeToConstructorInjectionRectorTest
 */
final class ControllerGetByTypeToConstructorInjectionRector extends AbstractRector
{
    public function __construct(private readonly ContainerGetToConstructorInjectionReplacer $containerGetToConstructorInjectionReplacer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('From `$container->get(SomeType::class)` in controllers to constructor injection (step 1/x)', [new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

final class SomeCommand extends Controller
{
    public function someMethod()
    {
        $someType = $this->get(SomeType::class);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

final class SomeCommand extends Controller
{
    public function __construct(private SomeType $someType)
    {
    }

    public function someMethod()
    {
        $someType = $this->someType;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkipClass($node)) {
            return null;
        }
        if (!$this->containerGetToConstructorInjectionReplacer->replace($node)) {
            return null;
        }
        return $node;
    }
    private function shouldSkipClass(Class_ $class): bool
    {
        // keep it safe
        if (!$class->isFinal()) {
            return \true;
        }
        $scope = ScopeFetcher::fetch($class);
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return \true;
        }
        return !$classReflection->is(SymfonyClass::CONTROLLER);
    }
}
