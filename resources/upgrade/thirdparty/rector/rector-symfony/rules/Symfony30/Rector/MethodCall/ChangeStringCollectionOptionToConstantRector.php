<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony30\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Symfony\FormHelper\FormTypeStringToTypeProvider;
use Flames\Code\Upgrade\Symfony\NodeAnalyzer\FormAddMethodCallAnalyzer;
use Flames\Code\Upgrade\Symfony\NodeAnalyzer\FormCollectionAnalyzer;
use Flames\Code\Upgrade\Symfony\NodeAnalyzer\FormOptionsArrayMatcher;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://symfony.com/doc/2.8/form/form_collections.html
 * @changelog https://symfony.com/doc/3.0/form/form_collections.html
 * @changelog https://symfony2-document.readthedocs.io/en/latest/reference/forms/types/collection.html#type
 *
 * @see \Flames\Code\Upgrade\Symfony30\Rector\MethodCall\ChangeStringCollectionOptionToConstantRectorTest
 */
final class ChangeStringCollectionOptionToConstantRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly FormAddMethodCallAnalyzer $formAddMethodCallAnalyzer, private readonly FormOptionsArrayMatcher $formOptionsArrayMatcher, private readonly FormTypeStringToTypeProvider $formTypeStringToTypeProvider, private readonly FormCollectionAnalyzer $formCollectionAnalyzer, private readonly ValueResolver $valueResolver)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('symfony/form', '>=3.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change type in CollectionType from alias string to class reference', [new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tags', CollectionType::class, [
            'type' => 'choice',
        ]);

        $builder->add('tags', 'collection', [
            'type' => 'choice',
        ]);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tags', CollectionType::class, [
            'type' => \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class,
        ]);

        $builder->add('tags', 'collection', [
            'type' => \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class,
        ]);
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
        if (!$this->formCollectionAnalyzer->isCollectionType($node)) {
            return null;
        }
        $optionsArray = $this->formOptionsArrayMatcher->match($node);
        if (!$optionsArray instanceof Array_) {
            return null;
        }
        return $this->processChangeToConstant($optionsArray, $node);
    }
    private function processChangeToConstant(Array_ $optionsArray, MethodCall $methodCall): ?Node
    {
        foreach ($optionsArray->items as $optionsArrayItem) {
            if (!$optionsArrayItem instanceof ArrayItem) {
                continue;
            }
            if (!$optionsArrayItem->key instanceof Expr) {
                continue;
            }
            if (!$this->valueResolver->isValues($optionsArrayItem->key, ['type', 'entry_type'])) {
                continue;
            }
            // already ::class reference
            if (!$optionsArrayItem->value instanceof String_) {
                return null;
            }
            $stringValue = $optionsArrayItem->value->value;
            $formClass = $this->formTypeStringToTypeProvider->matchClassForNameWithPrefix($stringValue);
            if ($formClass === null) {
                return null;
            }
            $optionsArrayItem->value = $this->nodeFactory->createClassConstReference($formClass);
        }
        return $methodCall;
    }
}
