<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\ArrayKeyExistsOnPropertyRectorTest
 */
final class ArrayKeyExistsOnPropertyRector extends AbstractRector implements MinPhpVersionInterface
{
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ARRAY_KEY_EXISTS_TO_PROPERTY_EXISTS;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change array_key_exists() on property to property_exists()', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
     public $value;
}
$someClass = new SomeClass;

array_key_exists('value', $someClass);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
     public $value;
}
$someClass = new SomeClass;

property_exists($someClass, 'value');
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node, 'array_key_exists')) {
            return null;
        }
        if (!isset($node->args[1])) {
            return null;
        }
        if (!$node->args[1] instanceof Arg) {
            return null;
        }
        $firstArgStaticType = $this->getType($node->args[1]->value);
        if (!$firstArgStaticType instanceof ObjectType) {
            return null;
        }
        $node->name = new Name('property_exists');
        $node->args = array_reverse($node->args);
        return $node;
    }
}
