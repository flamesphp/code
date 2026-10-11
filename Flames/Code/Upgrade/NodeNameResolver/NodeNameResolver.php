<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeNameResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\PropertyItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Const_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\Rules\CodingStyle\Naming\ClassNaming;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\NodeAnalyzer\CallAnalyzer;
use Flames\Code\Upgrade\NodeNameResolver\Contract\NodeNameResolverInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\ValueObject\MethodName;
final class NodeNameResolver
{
    /**
     * Used to check if a string might contain a regex or fnmatch pattern
     * @var mixed[]
     */
    private const array REGEX_WILDCARD_CHARS = ['*', '#', '~', '/'];
    /**
     * @var array<string, NodeNameResolverInterface|null>
     */
    private array $nodeNameResolversByClass = [];
    /**
     * @param NodeNameResolverInterface[] $nodeNameResolvers
     */
    public function __construct(private readonly ClassNaming $classNaming, private readonly CallAnalyzer $callAnalyzer, private readonly array $nodeNameResolvers)
    {
    }
    /**
     * @param string[] $names
     */
    public function isNames(Node $node, array $names): bool
    {
        $nodeName = $this->getName($node);
        if ($nodeName === null) {
            return \false;
        }
        $found = array_any($names, fn($name) => $this->isStringName($nodeName, $name));
        return $found;
    }
    /**
     * @param Node|Node[] $node
     * @param MethodName::*|string $name
     */
    public function isName($node, string $name): bool
    {
        $nodes = is_array($node) ? $node : [$node];
        $found = array_any($nodes, fn($node) => $this->isSingleName($node, $name));
        return $found;
    }
    /**
     * Some nodes have always-known string name. This makes PHPStan smarter.
     * @see https://phpstan.org/writing-php-code/phpdoc-types#conditional-return-types
     *
     * @return ($node is Param ? string :
     *  ($node is ClassMethod ? string :
     *  ($node is Property ? string :
     *  ($node is PropertyItem ? string :
     *  ($node is Trait_ ? string :
     *  ($node is Interface_ ? string :
     *  ($node is Const_ ? string :
     *  ($node is Node\Const_ ? string :
     *  ($node is Name ? string :
     *      string|null )))))))))
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node|string $node
     */
    public function getName($node): ?string
    {
        if (is_string($node)) {
            return $node;
        }
        // useful for looped imported names
        $namespacedName = $node->getAttribute(AttributeKey::NAMESPACED_NAME);
        if (is_string($namespacedName)) {
            return $namespacedName;
        }
        if (($node instanceof MethodCall || $node instanceof StaticCall || $node instanceof NullsafeMethodCall) && $this->isCallOrIdentifier($node->name)) {
            return null;
        }
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        $resolvedName = $this->resolveNodeName($node, $scope);
        if ($resolvedName !== null) {
            return $resolvedName;
        }
        // more complex
        if (!property_exists($node, 'name')) {
            return null;
        }
        // unable to resolve
        if ($node->name instanceof Expr) {
            return null;
        }
        return (string) $node->name;
    }
    /**
     * @api
     */
    public function areNamesEqual(Node $firstNode, Node $secondNode): bool
    {
        $secondResolvedName = $this->getName($secondNode);
        if ($secondResolvedName === null) {
            return \false;
        }
        return $this->isName($firstNode, $secondResolvedName);
    }
    /**
     * @api
     *
     * @param Name[]|Node[] $nodes
     * @return string[]
     */
    public function getNames(array $nodes): array
    {
        $names = [];
        foreach ($nodes as $node) {
            $name = $this->getName($node);
            if (!is_string($name)) {
                throw new ShouldNotHappenException();
            }
            $names[] = $name;
        }
        return $names;
    }
    /**
     * @param string|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike $name
     */
    public function getShortName($name): string
    {
        return $this->classNaming->getShortName($name);
    }
    public function isStringName(string $resolvedName, string $desiredName): bool
    {
        if ($desiredName === '') {
            return \false;
        }
        // special case
        if ($desiredName === 'Object') {
            return $desiredName === $resolvedName;
        }
        if (strcasecmp($resolvedName, $desiredName) === 0) {
            return \true;
        }
        foreach (self::REGEX_WILDCARD_CHARS as $char) {
            if (str_contains($desiredName, $char)) {
                throw new ShouldNotHappenException('Matching of regular expressions is no longer supported. Use $this->getName() and compare with e.g. str_ends_with() or str_starts_with() instead.');
            }
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier $node
     */
    private function isCallOrIdentifier($node): bool
    {
        if ($node instanceof Expr) {
            return $this->callAnalyzer->isObjectCall($node);
        }
        return \true;
    }
    private function isSingleName(Node $node, string $desiredName): bool
    {
        if ($node instanceof CallLike && !$node instanceof FuncCall) {
            // method call cannot have a name, only the variable or method name
            return \false;
        }
        $resolvedName = $this->getName($node);
        if ($resolvedName === null) {
            return \false;
        }
        return $this->isStringName($resolvedName, $desiredName);
    }
    private function resolveNodeName(Node $node, ?Scope $scope): ?string
    {
        $nodeClass = $node::class;
        if (array_key_exists($nodeClass, $this->nodeNameResolversByClass)) {
            $resolver = $this->nodeNameResolversByClass[$nodeClass];
            if ($resolver instanceof NodeNameResolverInterface) {
                return $resolver->resolve($node, $scope);
            }
            return null;
        }
        foreach ($this->nodeNameResolvers as $nodeNameResolver) {
            if (!\is_a($node, $nodeNameResolver->getNode(), \true)) {
                continue;
            }
            $this->nodeNameResolversByClass[$nodeClass] = $nodeNameResolver;
            return $nodeNameResolver->resolve($node, $scope);
        }
        $this->nodeNameResolversByClass[$nodeClass] = null;
        return null;
    }
}
