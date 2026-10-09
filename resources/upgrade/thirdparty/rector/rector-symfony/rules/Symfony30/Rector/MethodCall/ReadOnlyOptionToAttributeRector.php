<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony30\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Symfony\NodeAnalyzer\FormAddMethodCallAnalyzer;
use Flames\Code\Upgrade\Symfony\NodeAnalyzer\FormOptionsArrayMatcher;
use Flames\Code\Upgrade\Symfony\NodeManipulator\ArrayManipulator;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Symfony30\Rector\MethodCall\ReadOnlyOptionToAttributeRectorTest
 */
final class ReadOnlyOptionToAttributeRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly ArrayManipulator $arrayManipulator, private readonly FormAddMethodCallAnalyzer $formAddMethodCallAnalyzer, private readonly FormOptionsArrayMatcher $formOptionsArrayMatcher)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('symfony/form', '>=3.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change "read_only" option in form to attribute', [new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\Form\FormBuilderInterface;

function buildForm(FormBuilderInterface $builder, array $options)
{
    $builder->add('cuid', TextType::class, ['read_only' => true]);
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Form\FormBuilderInterface;

function buildForm(FormBuilderInterface $builder, array $options)
{
    $builder->add('cuid', TextType::class, ['attr' => ['read_only' => true]]);
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->formAddMethodCallAnalyzer->isMatching($node)) {
            return null;
        }
        $optionsArray = $this->formOptionsArrayMatcher->match($node);
        if (!$optionsArray instanceof Array_) {
            return null;
        }
        $readOnlyArrayItem = $this->arrayManipulator->findItemInInArrayByKeyAndUnset($optionsArray, 'read_only');
        if (!$readOnlyArrayItem instanceof ArrayItem) {
            return null;
        }
        // rename string
        $readOnlyArrayItem->key = new String_('readonly');
        $this->arrayManipulator->addItemToArrayUnderKey($optionsArray, $readOnlyArrayItem, 'attr');
        return $node;
    }
}
