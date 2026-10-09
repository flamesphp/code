<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\Instanceof_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\DowngradePhp81\NodeManipulator\ObjectToResourceReturn;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.resource2object
 *
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\Instanceof_\DowngradePhp80ResourceReturnToObjectRectorTest
 */
final class DowngradePhp80ResourceReturnToObjectRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private const array COLLECTION_OBJECT_TO_RESOURCE = [
        // curl
        'CurlHandle',
        'CurlMultiHandle',
        'CurlShareHandle',
        // socket
        'Socket',
        // GD
        'GdImage',
        // XMLWriter
        'XMLWriter',
        // XMLParser
        'XMLParser',
        // Broker
        'EnchantBroker',
        'EnchantDictionary',
        // OpenSSL
        'OpenSSLCertificate',
        'OpenSSLCertificateSigningRequest',
        // Shmop
        'Shmop',
        // MessageQueue
        'SysvMessageQueue',
        'SysvSemaphore',
        'SysvSharedMemory',
        // Inflate Deflate
        'InflateContext',
        'DeflateContext',
    ];
    public function __construct(private readonly ObjectToResourceReturn $objectToResourceReturn)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('change instanceof Object to is_resource', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($obj)
    {
        $obj instanceof \CurlHandle;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($obj)
    {
        is_resource($obj) || $obj instanceof \CurlHandle;
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
        return [BinaryOp::class, Instanceof_::class];
    }
    /**
     * @param BinaryOp|Instanceof_ $node
     */
    public function refactor(Node $node): ?Node
    {
        return $this->objectToResourceReturn->refactor($node, self::COLLECTION_OBJECT_TO_RESOURCE);
    }
}
