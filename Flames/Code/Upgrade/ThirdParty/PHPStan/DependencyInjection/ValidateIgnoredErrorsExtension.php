<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Flames\Code\Upgrade\ThirdParty\Flames\Code\Upgrade\ThirdParty\Hoa\Compiler\Llk\Llk;
use Flames\Code\Upgrade\ThirdParty\Flames\Code\Upgrade\ThirdParty\Hoa\File\Read;
use Flames\Code\Upgrade\ThirdParty\Nette\DI\CompilerExtension;
use Flames\Code\Upgrade\ThirdParty\Nette\RegexpException;
use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Override;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\NameScope;
use PHPStan\Command\IgnoredRegexValidator;
use PHPStan\File\FileExcluder;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\ConfiguredPhpVersionRangeHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\DirectTypeNodeResolverExtensionRegistryProvider;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MissingStaticAccessorInstanceException;
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\ReflectionProvider\DirectReflectionProviderProvider;
use PHPStan\Reflection\ReflectionProvider\DummyReflectionProvider;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Type\Constant\OversizedArrayBuilder;
use PHPStan\Type\DirectTypeAliasResolverProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;
use PHPStan\Type\Type;
use PHPStan\Type\TypeAliasResolver;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtensionRegistry;
use function array_keys;
use function array_map;
use function count;
use function gettype;
use function implode;
use function is_array;
use function is_dir;
use function is_file;
use function is_string;
use function sprintf;
use const PHP_VERSION_ID;
#[\PHPStan\DependencyInjection\ContainerExtension('validateIgnoredErrors')]
final class ValidateIgnoredErrorsExtension extends CompilerExtension
{
    /**
     * @throws InvalidIgnoredErrorPatternsException
     */
    #[Override]
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        if (!$builder->parameters['__validate']) {
            return;
        }
        $ignoreErrors = $builder->parameters['ignoreErrors'];
        if (count($ignoreErrors) === 0) {
            return;
        }
        /** @throws void */
        $parser = Llk::load(new Read(__DIR__ . '/../resources/RegexGrammar.pp'));
        $reflectionProvider = new DummyReflectionProvider();
        $reflectionProviderProvider = new DirectReflectionProviderProvider($reflectionProvider);
        try {
            $originalReflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        } catch (MissingStaticAccessorInstanceException $e) {
            $originalReflectionProvider = null;
        }
        try {
            $originalPhpVersion = PhpVersionStaticAccessor::getInstance();
        } catch (MissingStaticAccessorInstanceException $e) {
            $originalPhpVersion = null;
        }
        ReflectionProviderStaticAccessor::registerInstance($reflectionProvider);
        PhpVersionStaticAccessor::registerInstance(new PhpVersion(PHP_VERSION_ID));
        try {
            $composerPhpVersionFactory = new ComposerPhpVersionFactory([]);
            $constantResolver = new ConstantResolver($reflectionProviderProvider, [], new ConfiguredPhpVersionRangeHelper(null, $composerPhpVersionFactory), null);
            $phpDocParserConfig = new ParserConfig([]);
            $ignoredRegexValidator = new IgnoredRegexValidator($parser, new TypeStringResolver(new Lexer($phpDocParserConfig), new TypeParser($phpDocParserConfig, new ConstExprParser($phpDocParserConfig)), new TypeNodeResolver(new DirectTypeNodeResolverExtensionRegistryProvider(new class implements TypeNodeResolverExtensionRegistry
            {
                public function getExtensions(): array
                {
                    return [];
                }
            }), $reflectionProviderProvider, new DirectTypeAliasResolverProvider(new class implements TypeAliasResolver
            {
                public function hasTypeAlias(string $aliasName, ?string $classNameScope): bool
                {
                    return \false;
                }
                public function resolveTypeAlias(string $aliasName, NameScope $nameScope): ?Type
                {
                    return null;
                }
            }), $constantResolver, new InitializerExprTypeResolver($constantResolver, $reflectionProviderProvider, new PhpVersion(PHP_VERSION_ID), new OperatorTypeSpecifyingExtensionRegistry(new \PHPStan\DependencyInjection\DirectExtensionsCollection([])), new UnaryOperatorTypeSpecifyingExtensionRegistry(new \PHPStan\DependencyInjection\DirectExtensionsCollection([])), new OversizedArrayBuilder(), \true), null)));
            $errors = [];
            foreach ($ignoreErrors as $ignoreError) {
                if (is_array($ignoreError)) {
                    if (isset($ignoreError['count'])) {
                        continue;
                        // ignoreError coming from baseline will be correct
                    }
                    if (isset($ignoreError['messages'])) {
                        $ignoreMessages = $ignoreError['messages'];
                    } elseif (isset($ignoreError['message'])) {
                        $ignoreMessages = [$ignoreError['message']];
                    } else {
                        continue;
                    }
                } else {
                    $ignoreMessages = [$ignoreError];
                }
                foreach ($ignoreMessages as $ignoreMessage) {
                    $error = $this->validateMessage($ignoredRegexValidator, $ignoreMessage);
                    if ($error === null) {
                        continue;
                    }
                    $errors[] = $error;
                }
            }
            $reportUnmatched = (bool) $builder->parameters['reportUnmatchedIgnoredErrors'];
            if ($reportUnmatched) {
                foreach ($ignoreErrors as $ignoreError) {
                    if (!is_array($ignoreError)) {
                        continue;
                    }
                    if (isset($ignoreError['path'])) {
                        if (!is_string($ignoreError['path'])) {
                            $errors[] = sprintf("Key 'path' of ignoreErrors expects a string, %s given. Did you mean 'paths'?", gettype($ignoreError['path']));
                            continue;
                        }
                        $ignorePaths = [$ignoreError['path']];
                    } elseif (isset($ignoreError['paths'])) {
                        if (!is_array($ignoreError['paths'])) {
                            $errors[] = sprintf("Key 'paths' of ignoreErrors expects an array, %s given. Did you mean 'path'?", gettype($ignoreError['paths']));
                            continue;
                        }
                        $ignorePaths = $ignoreError['paths'];
                    } else {
                        continue;
                    }
                    foreach ($ignorePaths as $ignorePath) {
                        if (FileExcluder::isAbsolutePath($ignorePath)) {
                            if (is_dir($ignorePath)) {
                                continue;
                            }
                            if (is_file($ignorePath)) {
                                continue;
                            }
                        }
                        if (FileExcluder::isFnmatchPattern($ignorePath)) {
                            continue;
                        }
                        $errors[] = sprintf('Path "%s" is neither a directory, nor a file path, nor a fnmatch pattern.', $ignorePath);
                    }
                }
            }
            if (count($errors) === 0) {
                return;
            }
            throw new \PHPStan\DependencyInjection\InvalidIgnoredErrorPatternsException($errors);
        } finally {
            if ($originalReflectionProvider !== null) {
                ReflectionProviderStaticAccessor::registerInstance($originalReflectionProvider);
            }
            if ($originalPhpVersion !== null) {
                PhpVersionStaticAccessor::registerInstance($originalPhpVersion);
            }
            ObjectType::resetCaches();
        }
    }
    private function validateMessage(IgnoredRegexValidator $ignoredRegexValidator, string $ignoreMessage): ?string
    {
        try {
            Strings::match('', $ignoreMessage);
            $validationResult = $ignoredRegexValidator->validate($ignoreMessage);
            $ignoredTypes = $validationResult->ignoredTypes;
            if (count($ignoredTypes) > 0) {
                return $this->createIgnoredTypesError($ignoreMessage, $ignoredTypes);
            }
            if ($validationResult->anchorsInTheMiddle) {
                return \sprintf("Ignored error %s has an unescaped anchor '\$' in the middle. This leads to unintended behavior. Use '\\\$' instead.", $ignoreMessage);
            }
            if ($validationResult->allErrorsIgnored) {
                return sprintf("Ignored error %s has an unescaped '%s' which leads to ignoring all errors. Use '%s' instead.", $ignoreMessage, $validationResult->wrongSequence, $validationResult->escapedWrongSequence);
            }
        } catch (RegexpException $e) {
            return $e->getMessage();
        }
        return null;
    }
    /**
     * @param array<string, string> $ignoredTypes
     */
    private function createIgnoredTypesError(string $regex, array $ignoredTypes): string
    {
        return sprintf("Ignored error %s has an unescaped '|' which leads to ignoring more errors than intended. Use '\\|' instead.\n%s", $regex, sprintf("It ignores all errors containing the following types:\n%s", implode("\n", array_map(static fn(string $typeDescription): string => sprintf('* %s', $typeDescription), array_keys($ignoredTypes)))));
    }
    private function createAnchorInTheMiddleError(string $regex): string
    {
        return sprintf("Ignored error %s has an unescaped anchor '\$' in the middle. This leads to unintended behavior. Use '\\\$' instead.", $regex);
    }
}
