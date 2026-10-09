<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Application;

use DateTime;
use Flames\Code\Upgrade\Exception\VersionException;
/**
 * @api
 *
 * Inspired by https://github.com/composer/composer/blob/master/src/Composer/Composer.php
 * See https://github.com/composer/composer/blob/6587715d0f8cae0cd39073b3bc5f018d0e6b84fe/src/Composer/Compiler.php#L208
 *
 * @see \Flames\Code\Upgrade\Tests\Application\VersionResolverTest
 */
final class VersionResolver
{
    /**
     * @api
     */
    public const string PACKAGE_VERSION = 'd439618c7186b93603e6bd8d8e3d82dd3ceba0a4';
    /**
     * @api
     */
    public const string RELEASE_DATE = '2026-10-07 14:12:55';
    private const int SUCCESS_CODE = 0;
    public static function resolvePackageVersion(): string
    {
        // resolve current tag
        exec('git tag --points-at', $tagExecOutput, $tagExecResultCode);
        if ($tagExecResultCode !== self::SUCCESS_CODE) {
            throw new VersionException('Ensure to run compile from composer git repository clone and that git binary is available.');
        }
        if ($tagExecOutput !== []) {
            $tag = $tagExecOutput[0];
            if ($tag !== '') {
                return $tag;
            }
        }
        exec('git log --pretty="%H" -n1 HEAD', $commitHashExecOutput, $commitHashResultCode);
        if ($commitHashResultCode !== 0) {
            throw new VersionException('Ensure to run compile from composer git repository clone and that git binary is available.');
        }
        $version = trim($commitHashExecOutput[0]);
        return trim($version, '"');
    }
    public static function resolverReleaseDateTime(): DateTime
    {
        exec('git log -n1 --pretty=%ci HEAD', $output, $resultCode);
        if ($resultCode !== self::SUCCESS_CODE) {
            throw new VersionException('You must ensure to run compile from composer git repository clone and that git binary is available.');
        }
        return new DateTime(trim($output[0]));
    }
}
