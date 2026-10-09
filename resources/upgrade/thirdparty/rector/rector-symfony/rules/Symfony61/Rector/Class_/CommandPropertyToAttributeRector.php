<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony61\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Doctrine\NodeAnalyzer\AttributeFinder;
use Flames\Code\Upgrade\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyAttribute;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyClass;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @changelog https://symfony.com/doc/current/console.html#registering-the-command
 *
 * @see \Flames\Code\Upgrade\Symfony61\Rector\Class_\CommandPropertyToAttributeRectorTest
 */
final class CommandPropertyToAttributeRector extends AbstractRector implements MinPhpVersionInterface, ComposerPackageConstraintInterface
{
    public function __construct(private readonly PhpAttributeGroupFactory $phpAttributeGroupFactory, private readonly ReflectionProvider $reflectionProvider, private readonly AttributeFinder $attributeFinder)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('symfony/console', '>=6.1');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add Symfony\Component\Console\Attribute\AsCommand to Symfony Commands and remove the deprecated properties', [new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\Console\Command\Command;

final class SunshineCommand extends Command
{
    public static $defaultName = 'sunshine';

    public static $defaultDescription = 'some description';
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'sunshine', description: 'some description')]
final class SunshineCommand extends Command
{
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
        if (!$this->isObjectType($node, new ObjectType(SymfonyClass::COMMAND))) {
            return null;
        }
        // does attribute already exist?
        if (!$this->reflectionProvider->hasClass(SymfonyAttribute::AS_COMMAND)) {
            return null;
        }
        $defaultNameExpr = $this->resolvePropertyExpr($node, 'defaultName');
        if (!$defaultNameExpr instanceof Expr) {
            return null;
        }
        $defaultDescriptionExpr = $this->resolvePropertyExpr($node, 'defaultDescription');
        $existingAsCommandAttribute = $this->attributeFinder->findAttributeByClass($node, SymfonyAttribute::AS_COMMAND);
        $attributeArgs = $this->createAttributeArgs($defaultNameExpr, $defaultDescriptionExpr);
        // already has attribute, only add "name" and optionally "description"
        if ($existingAsCommandAttribute instanceof Attribute) {
            $existingAsCommandAttribute->args = array_merge($attributeArgs, $existingAsCommandAttribute->args);
            return $node;
        }
        $node->attrGroups[] = $this->createAttributeGroupAsCommand($attributeArgs);
        return $node;
    }
    /**
     * @param Arg[] $args
     */
    private function createAttributeGroupAsCommand(array $args): AttributeGroup
    {
        Assert::allIsInstanceOf($args, Arg::class);
        $attributeGroup = $this->phpAttributeGroupFactory->createFromClass(SymfonyAttribute::AS_COMMAND);
        $attributeGroup->attrs[0]->args = $args;
        return $attributeGroup;
    }
    private function resolvePropertyExpr(Class_ $class, string $propertyName): ?Expr
    {
        foreach ($class->stmts as $key => $stmt) {
            if (!$stmt instanceof Property) {
                continue;
            }
            if (!$this->isName($stmt, $propertyName)) {
                continue;
            }
            $defaultExpr = $stmt->props[0]->default;
            if ($defaultExpr instanceof Expr) {
                // remove property
                unset($class->stmts[$key]);
                return $defaultExpr;
            }
        }
        return null;
    }
    private function createNamedArg(string $name, Expr $expr): Arg
    {
        return new Arg($expr, \false, \false, [], new Identifier($name));
    }
    /**
     * @return Arg[]
     */
    private function createAttributeArgs(Expr $defaultNameExpr, ?Expr $defaultDescriptionExpr): array
    {
        // already has the attribute, add description and name to the front
        $attributeArgs = [];
        $attributeArgs[] = $this->createNamedArg('name', $defaultNameExpr);
        if ($defaultDescriptionExpr instanceof Expr) {
            $attributeArgs[] = $this->createNamedArg('description', $defaultDescriptionExpr);
        }
        return $attributeArgs;
    }
}
