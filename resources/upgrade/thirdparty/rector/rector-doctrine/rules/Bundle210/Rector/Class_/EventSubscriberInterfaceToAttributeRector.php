<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Bundle210\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://github.com/doctrine/DoctrineBundle/pull/1592
 *
 * @see \Flames\Code\Upgrade\Bundle210\Rector\Class_\EventSubscriberInterfaceToAttributeRectorTest
 */
final class EventSubscriberInterfaceToAttributeRector extends AbstractRector implements MinPhpVersionInterface, ComposerPackageConstraintInterface
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('doctrine/doctrine-bundle', '>=2.8');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace EventSubscriberInterface with #[AsDoctrineListener] attribute', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Common\EventSubscriberInterface;
use Doctrine\ORM\Events;

class MyEventSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents()
    {
        return array(
            Events::postUpdate,
            Events::prePersist,
        );
    }

    public function postUpdate(PostUpdateEventArgs $args)
    {
        // ...
    }

    public function prePersist(PrePersistEventArgs $args)
    {
        // ...
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::prePersist)]
class MyEventSubscriber
{
    public function postUpdate(PostUpdateEventArgs $args)
    {
        // ...
    }

    public function prePersist(PrePersistEventArgs $args)
    {
        // ...
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
        if (!$this->reflectionProvider->hasClass(DoctrineClass::AS_DOCTRINE_LISTENER_ATTRIBUTE)) {
            return null;
        }
        if (!$this->hasImplements($node, DoctrineClass::EVENT_SUBSCRIBER) && !$this->hasImplements($node, DoctrineClass::EVENT_SUBSCRIBER_INTERFACE)) {
            return null;
        }
        foreach ($node->stmts as $key => $classStmt) {
            if (!$classStmt instanceof ClassMethod) {
                continue;
            }
            if (!$this->isName($classStmt, 'getSubscribedEvents')) {
                continue;
            }
            $getSubscribedEventsClassMethod = $classStmt;
            if ($getSubscribedEventsClassMethod->stmts === []) {
                continue;
            }
            //            $firstStmt = $getSubscribedEventsClassMethod->stmts[0];
            //            if ($firstStmt instanceof Return_ && $firstStmt->expr instanceof Array_
            //            ) {
            $this->refactorSubscriberArrayToClassAttributes($node, $getSubscribedEventsClassMethod);
            //            }
            $this->removeImplements($node, [DoctrineClass::EVENT_SUBSCRIBER, DoctrineClass::EVENT_SUBSCRIBER_INTERFACE]);
            // remove method
            unset($node->stmts[$key]);
            return $node;
        }
        return null;
    }
    private function refactorSubscriberArrayToClassAttributes(Class_ $class, ClassMethod $getSubscribedEventsClassMethod): void
    {
        foreach ((array) $getSubscribedEventsClassMethod->stmts as $stmt) {
            if (!$stmt instanceof Return_) {
                continue;
            }
            if (!$stmt->expr instanceof Array_) {
                continue;
            }
            $arguments = $this->extractArrayItemsToExprs($stmt->expr);
            $this->addClassAttribute($class, $arguments);
        }
    }
    /**
     * @return array<Expr>
     */
    private function extractArrayItemsToExprs(Array_ $array): array
    {
        $arguments = [];
        foreach ($array->items as $item) {
            $arguments[] = $item->value;
        }
        return $arguments;
    }
    /**
     * @param array<Expr> $arguments
     */
    private function addClassAttribute(Class_ $class, array $arguments): void
    {
        foreach ($arguments as $argument) {
            $class->attrGroups[] = new AttributeGroup([new Attribute(new FullyQualified(DoctrineClass::AS_DOCTRINE_LISTENER_ATTRIBUTE), [new Arg($argument, \false, \false, [], new Identifier('event'))])]);
        }
    }
    private function hasImplements(Class_ $class, string $interfaceFQN): bool
    {
        $found = array_any($class->implements, fn($name) => $this->isName($name, $interfaceFQN));
        return $found;
    }
    /**
     * @param array<string> $interfaceFQNS
     */
    private function removeImplements(Class_ $class, array $interfaceFQNS): void
    {
        foreach ($class->implements as $key => $implement) {
            if (!$this->isNames($implement, $interfaceFQNS)) {
                continue;
            }
            unset($class->implements[$key]);
        }
    }
}
