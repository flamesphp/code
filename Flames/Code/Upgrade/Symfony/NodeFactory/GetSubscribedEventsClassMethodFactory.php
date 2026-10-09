<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
use Flames\Code\Upgrade\Rules\Privatization\NodeManipulator\VisibilityManipulator;
use Flames\Code\Upgrade\ThirdParty\Symfony\Contract\EventReferenceToMethodNameInterface;
use Flames\Code\Upgrade\ThirdParty\Symfony\Contract\Tag\TagInterface;
use Flames\Code\Upgrade\Symfony\ValueObject\EventNameToClassAndConstant;
use Flames\Code\Upgrade\Symfony\ValueObject\EventReferenceToMethodNameWithPriority;
use Flames\Code\Upgrade\Symfony\ValueObject\ServiceDefinition;
use Flames\Code\Upgrade\Symfony\ValueObject\Tag;
use Flames\Code\Upgrade\Symfony\ValueObject\Tag\EventListenerTag;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
final readonly class GetSubscribedEventsClassMethodFactory
{
    private const string GET_SUBSCRIBED_EVENTS_METHOD_NAME = 'getSubscribedEvents';
    public function __construct(private NodeFactory $nodeFactory, private VisibilityManipulator $visibilityManipulator, private PhpVersionProvider $phpVersionProvider, private PhpDocInfoFactory $phpDocInfoFactory, private PhpDocTypeChanger $phpDocTypeChanger, private \Flames\Code\Upgrade\Symfony\NodeFactory\EventReferenceFactory $eventReferenceFactory)
    {
    }
    /**
     * @param EventReferenceToMethodNameInterface[] $eventReferencesToMethodNames
     */
    public function create(array $eventReferencesToMethodNames): ClassMethod
    {
        $getSubscribersClassMethod = $this->createClassMethod();
        $eventsToMethodsArray = new Array_();
        foreach ($eventReferencesToMethodNames as $eventReferenceToMethodName) {
            $priority = $eventReferenceToMethodName instanceof EventReferenceToMethodNameWithPriority ? $eventReferenceToMethodName->getPriority() : null;
            $eventsToMethodsArray->items[] = $this->createArrayItemFromMethodAndPriority($priority, $eventReferenceToMethodName->getMethodName(), $eventReferenceToMethodName->getClassConstFetch());
        }
        $getSubscribersClassMethod->stmts[] = new Return_($eventsToMethodsArray);
        $this->decorateClassMethodWithReturnType($getSubscribersClassMethod);
        return $getSubscribersClassMethod;
    }
    /**
     * @param array<string, ServiceDefinition[]> $eventsToMethods
     * @param EventNameToClassAndConstant[] $eventNamesToClassConstants
     */
    public function createFromServiceDefinitionsAndEventsToMethods(array $eventsToMethods, array $eventNamesToClassConstants): ClassMethod
    {
        $getSubscribersClassMethod = $this->createClassMethod();
        $eventsToMethodsArray = new Array_();
        foreach ($eventsToMethods as $eventName => $methodNamesWithPriorities) {
            $eventNameExpr = $this->eventReferenceFactory->createEventName($eventName, $eventNamesToClassConstants);
            if (count($methodNamesWithPriorities) === 1) {
                $this->createSingleMethod($methodNamesWithPriorities, $eventName, $eventNameExpr, $eventsToMethodsArray);
            } else {
                $this->createMultipleMethods($methodNamesWithPriorities, $eventNameExpr, $eventsToMethodsArray, $eventName);
            }
        }
        $getSubscribersClassMethod->stmts[] = new Return_($eventsToMethodsArray);
        $this->decorateClassMethodWithReturnType($getSubscribersClassMethod);
        return $getSubscribersClassMethod;
    }
    private function createClassMethod(): ClassMethod
    {
        $classMethod = $this->nodeFactory->createPublicMethod(self::GET_SUBSCRIBED_EVENTS_METHOD_NAME);
        $this->visibilityManipulator->makeStatic($classMethod);
        return $classMethod;
    }
    private function createArrayItemFromMethodAndPriority(?int $priority, string $methodName, Expr $expr): ArrayItem
    {
        if ($priority !== null && $priority !== 0) {
            $methodNameWithPriorityArray = new Array_();
            $methodNameWithPriorityArray->items[] = new ArrayItem(new String_($methodName));
            $methodNameWithPriorityArray->items[] = new ArrayItem(new Int_($priority));
            return new ArrayItem($methodNameWithPriorityArray, $expr);
        }
        return new ArrayItem(new String_($methodName), $expr);
    }
    private function decorateClassMethodWithReturnType(ClassMethod $classMethod): void
    {
        if ($this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::SCALAR_TYPES)) {
            $classMethod->returnType = new Identifier('array');
        }
        $returnType = new ArrayType(new StringType(), new MixedType(\true));
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
        $this->phpDocTypeChanger->changeReturnType($classMethod, $phpDocInfo, $returnType);
    }
    /**
     * @param ServiceDefinition[] $methodNamesWithPriorities
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_ $expr
     */
    private function createSingleMethod(array $methodNamesWithPriorities, string $eventName, $expr, Array_ $eventsToMethodsArray): void
    {
        $methodName = $this->resolveMethodName($methodNamesWithPriorities[0], $eventName);
        $priority = $this->resolvePriority($methodNamesWithPriorities[0], $eventName);
        if ($methodName === null) {
            return;
        }
        $eventsToMethodsArray->items[] = $this->createArrayItemFromMethodAndPriority($priority, $methodName, $expr);
    }
    /**
     * @param ServiceDefinition[] $methodNamesWithPriorities
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_ $expr
     */
    private function createMultipleMethods(array $methodNamesWithPriorities, $expr, Array_ $eventsToMethodsArray, string $eventName): void
    {
        $eventItems = [];
        $alreadyUsedTags = [];
        foreach ($methodNamesWithPriorities as $methodNameWithPriority) {
            foreach ($methodNameWithPriority->getTags() as $tag) {
                if (!$tag instanceof EventListenerTag) {
                    continue;
                }
                if ($this->shouldSkip($eventName, $tag, $alreadyUsedTags)) {
                    continue;
                }
                $eventItems[] = $this->createEventItem($tag);
                $alreadyUsedTags[] = $tag;
            }
        }
        $multipleMethodsArray = new Array_($eventItems);
        $eventsToMethodsArray->items[] = new ArrayItem($multipleMethodsArray, $expr);
    }
    private function resolveMethodName(ServiceDefinition $serviceDefinition, string $eventName): ?string
    {
        /** @var EventListenerTag[]|Tag[] $eventTags */
        $eventTags = $serviceDefinition->getTags();
        foreach ($eventTags as $eventTag) {
            if (!$eventTag instanceof EventListenerTag) {
                continue;
            }
            if ($eventTag->getEvent() !== $eventName) {
                continue;
            }
            return $eventTag->getMethod();
        }
        return null;
    }
    private function resolvePriority(ServiceDefinition $serviceDefinition, string $eventName): ?int
    {
        /** @var EventListenerTag[]|Tag[] $eventTags */
        $eventTags = $serviceDefinition->getTags();
        foreach ($eventTags as $eventTag) {
            if (!$eventTag instanceof EventListenerTag) {
                continue;
            }
            if ($eventTag->getEvent() !== $eventName) {
                continue;
            }
            return $eventTag->getPriority();
        }
        return null;
    }
    /**
     * @param TagInterface[] $alreadyUsedTags
     */
    private function shouldSkip(string $eventName, EventListenerTag $eventListenerTag, array $alreadyUsedTags): bool
    {
        if ($eventName !== $eventListenerTag->getEvent()) {
            return \true;
        }
        return in_array($eventListenerTag, $alreadyUsedTags, \true);
    }
    private function createEventItem(EventListenerTag $eventListenerTag): ArrayItem
    {
        if ($eventListenerTag->getPriority() !== 0) {
            $methodNameWithPriorityArray = new Array_();
            $methodNameWithPriorityArray->items[] = new ArrayItem(new String_($eventListenerTag->getMethod()));
            $methodNameWithPriorityArray->items[] = new ArrayItem(new Int_($eventListenerTag->getPriority()));
            return new ArrayItem($methodNameWithPriorityArray);
        }
        return new ArrayItem(new String_($eventListenerTag->getMethod()));
    }
}
