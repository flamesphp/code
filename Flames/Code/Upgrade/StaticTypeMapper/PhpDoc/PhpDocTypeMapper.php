<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\StaticTypeMapper\Contract\PhpDocParser\PhpDocTypeMapperInterface;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Tests\StaticTypeMapper\PhpDoc\PhpDocTypeMapperTest
 */
final readonly class PhpDocTypeMapper
{
    /**
     * @param PhpDocTypeMapperInterface[] $phpDocTypeMappers
     */
    public function __construct(private array $phpDocTypeMappers, private TypeNodeResolver $typeNodeResolver)
    {
        Assert::notEmpty($this->phpDocTypeMappers);
    }
    public function mapToPHPStanType(TypeNode $typeNode, Node $node, NameScope $nameScope): Type
    {
        foreach ($this->phpDocTypeMappers as $phpDocTypeMapper) {
            if (!is_a($typeNode, $phpDocTypeMapper->getNodeType())) {
                continue;
            }
            return $phpDocTypeMapper->mapToPHPStanType($typeNode, $node, $nameScope);
        }
        // fallback to PHPStan resolver
        return $this->typeNodeResolver->resolve($typeNode, $nameScope);
    }
}
