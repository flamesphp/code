<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\Helper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\PhpAttribute\AttributeArrayNameInliner;
use Flames\Code\Upgrade\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use Flames\Code\Upgrade\Symfony\DataProvider\ServiceMapProvider;
use Flames\Code\Upgrade\Symfony\ValueObject\ServiceDefinition;
final class MessengerHelper
{
    public const string MESSAGE_HANDLER_INTERFACE = 'Symfony\Component\Messenger\Handler\MessageHandlerInterface';
    public const string MESSAGE_SUBSCRIBER_INTERFACE = 'Symfony\Component\Messenger\Handler\MessageSubscriberInterface';
    public const string AS_MESSAGE_HANDLER_ATTRIBUTE = 'Symfony\Component\Messenger\Attribute\AsMessageHandler';
    private string $messengerTagName = 'messenger.message_handler';
    /**
     * @var ServiceDefinition[]
     */
    private array $handlersFromServices = [];
    public function __construct(private readonly PhpAttributeGroupFactory $phpAttributeGroupFactory, private readonly AttributeArrayNameInliner $attributeArrayNameInliner, private readonly ServiceMapProvider $serviceMapProvider)
    {
    }
    /**
     * @return array<string, mixed>
     */
    public function extractOptionsFromServiceDefinition(ServiceDefinition $serviceDefinition): array
    {
        $options = [];
        foreach ($serviceDefinition->getTags() as $tag) {
            if ($this->messengerTagName === $tag->getName()) {
                $options = $tag->getData();
            }
        }
        if ($options['from_transport']) {
            $options['fromTransport'] = $options['from_transport'];
            unset($options['from_transport']);
        }
        return $options;
    }
    /**
     * @return ServiceDefinition[]
     */
    public function getHandlersFromServices(): array
    {
        if ($this->handlersFromServices !== []) {
            return $this->handlersFromServices;
        }
        $serviceMap = $this->serviceMapProvider->provide();
        $this->handlersFromServices = $serviceMap->getServicesByTag($this->messengerTagName);
        return $this->handlersFromServices;
    }
    /**
     * @param array<string, mixed> $options
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod $node
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod
     */
    public function addAttribute($node, array $options = [])
    {
        $args = $this->phpAttributeGroupFactory->createArgsFromItems($options, self::AS_MESSAGE_HANDLER_ATTRIBUTE);
        $args = $this->attributeArrayNameInliner->inlineArrayToArgs($args);
        $node->attrGroups = array_merge($node->attrGroups, [new AttributeGroup([new Attribute(new FullyQualified(self::AS_MESSAGE_HANDLER_ATTRIBUTE), $args)])]);
        return $node;
    }
}
