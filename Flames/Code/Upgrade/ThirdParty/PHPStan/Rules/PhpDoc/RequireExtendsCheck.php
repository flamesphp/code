<?php

declare (strict_types=1);
namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDoc\Tag\RequireExtendsTag;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;
use function strtolower;
#[AutowiredService]
final class RequireExtendsCheck
{
    private ReflectionProvider $reflectionProvider;
    private ClassNameCheck $classCheck;
    private bool $checkClassCaseSensitivity;
    private bool $discoveringSymbolsTip;
    public function __construct(
        ReflectionProvider $reflectionProvider,
        ClassNameCheck $classCheck,
        #[AutowiredParameter]
        bool $checkClassCaseSensitivity,
        #[AutowiredParameter('%tips.discoveringSymbols%')]
        bool $discoveringSymbolsTip
    )
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->classCheck = $classCheck;
        $this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
        $this->discoveringSymbolsTip = $discoveringSymbolsTip;
    }
    /**
     * @param  array<RequireExtendsTag> $extendsTags
     * @return list<IdentifierRuleError>
     */
    public function checkExtendsTags(Scope $scope, Node $node, array $extendsTags): array
    {
        $errors = [];
        if (count($extendsTags) > 1) {
            $errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends can only be used once.'))->identifier('requireExtends.duplicate')->build();
        }
        foreach ($extendsTags as $extendsTag) {
            $type = $extendsTag->type;
            $classNames = $type->getObjectClassNames();
            if (count($classNames) === 0) {
                $errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))->identifier('requireExtends.nonObject')->build();
                continue;
            }
            foreach ($classNames as $class) {
                if (!$this->reflectionProvider->hasClass($class)) {
                    $errorBuilder = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends contains unknown class %s.', $class))->identifier('class.notFound');
                    if ($this->discoveringSymbolsTip) {
                        $errorBuilder->tip('Learn more at https://phpstan.org/user-guide/discovering-symbols');
                    }
                    $errors[] = $errorBuilder->build();
                    continue;
                }
                $reflection = $this->reflectionProvider->getClass($class);
                if ($reflection->isInterface()) {
                    $errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends cannot contain an interface %s, expected a class.', $class))->tip('If you meant an interface, use @phpstan-require-implements instead.')->identifier('requireExtends.interface')->build();
                } elseif (!$reflection->isClass()) {
                    $errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends cannot contain non-class type %s.', $class))->identifier(sprintf('requireExtends.%s', strtolower($reflection->getClassTypeDescription())))->build();
                } elseif ($reflection->isFinal()) {
                    $errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends cannot contain final class %s.', $class))->identifier('requireExtends.finalClass')->build();
                } else {
                    $errors = array_merge($errors, $this->classCheck->checkClassNames($scope, [new ClassNameNodePair($class, $node)], ClassNameUsageLocation::from(ClassNameUsageLocation::PHPDOC_TAG_REQUIRE_EXTENDS), $this->checkClassCaseSensitivity));
                }
            }
        }
        return $errors;
    }
}
