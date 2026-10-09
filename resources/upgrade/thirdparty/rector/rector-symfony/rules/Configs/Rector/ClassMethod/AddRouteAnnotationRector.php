<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Configs\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @deprecated Too complex and tightly coupled to a project-specific Symfony route setup. Write a local custom rule for this one-time job instead.
 */
final class AddRouteAnnotationRector extends AbstractRector implements DeprecatedInterface
{
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        throw new ShouldNotHappenException(sprintf('"%s" is deprecated, as too complex and tightly coupled to a project-specific Symfony route setup. Write a local custom rule for this one-time job instead', self::class));
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Collect routes from Symfony project router and add Route annotation to controller action', [new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SomeController extends AbstractController
{
    public function index()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

final class SomeController extends AbstractController
{
    /**
     * @Route(name="homepage", path="/welcome")
     */
    public function index()
    {
    }
}
CODE_SAMPLE
)]);
    }
}
