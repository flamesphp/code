<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp81\Rector\Instanceof_;

use finfo;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\DowngradePhp81\NodeManipulator\ObjectToResourceReturn;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://www.php.net/manual/en/migration81.incompatible.php#migration81.incompatible.resource2object
 *
 * @see \Flames\Code\Upgrade\DowngradePhp81\Rector\Instanceof_\DowngradePhp81ResourceReturnToObjectRectorTest
 */
final class DowngradePhp81ResourceReturnToObjectRector extends AbstractRector
{
    /**
     * @var string[]|class-string<finfo>[]
     */
    private const array COLLECTION_OBJECT_TO_RESOURCE = [
        // finfo
        'finfo',
        // ftp
        \FTP\Connection::class,
        // imap_open
        \IMAP\Connection::class,
        // pspell
        \PSpell\Config::class,
        \PSpell\Dictionary::class,
        // ldap
        \LDAP\Connection::class,
        \LDAP\Result::class,
        \LDAP\ResultEntry::class,
        // psql
        \PgSql\Connection::class,
        \PgSql\Result::class,
        \PgSql\Lob::class,
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
        $obj instanceof \finfo;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($obj)
    {
        is_resource($obj) || $obj instanceof \finfo;
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
