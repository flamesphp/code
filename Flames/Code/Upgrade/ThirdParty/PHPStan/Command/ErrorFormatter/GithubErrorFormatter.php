<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\RelativePathHelper;
use function array_walk;
use function implode;
use function preg_replace;
use function sprintf;
use function str_replace;
/**
 * Allow errors to be reported in pull-requests diff when run in a GitHub Action
 * @see https://help.github.com/en/actions/reference/workflow-commands-for-github-actions#setting-an-error-message
 */
#[AutowiredService('errorFormatter.github')]
final class GithubErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
{
    private RelativePathHelper $relativePathHelper;
    public function __construct(
        #[AutowiredParameter('@simpleRelativePathHelper')]
        RelativePathHelper $relativePathHelper
    )
    {
        $this->relativePathHelper = $relativePathHelper;
    }
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        foreach ($analysisResult->fileSpecificErrors as $fileSpecificError) {
            $metas = ['file' => $this->relativePathHelper->getRelativePath($fileSpecificError->file), 'line' => $fileSpecificError->line, 'col' => 0];
            array_walk($metas, static function (&$value, string $key): void {
                $value = sprintf('%s=%s', $key, (string) $value);
            });
            $message = $fileSpecificError->message;
            $message = $this->formatMessage($message);
            $line = sprintf('::error %s::%s', implode(',', $metas), $message);
            $output->writeRaw($line);
            $output->writeLineFormatted('');
        }
        foreach ($analysisResult->notFileSpecificErrors as $notFileSpecificError) {
            $notFileSpecificError = $this->formatMessage($notFileSpecificError);
            $line = sprintf('::error ::%s', $notFileSpecificError);
            $output->writeRaw($line);
            $output->writeLineFormatted('');
        }
        foreach ($analysisResult->warnings as $warning) {
            $warning = $this->formatMessage($warning);
            $warning = str_replace("\n", '%0A', $warning);
            $line = sprintf('::warning ::%s', $warning);
            $output->writeRaw($line);
            $output->writeLineFormatted('');
        }
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
    private function formatMessage(string $message): string
    {
        // newlines need to be encoded
        // see https://github.com/actions/starter-workflows/issues/68#issuecomment-581479448
        $message = str_replace("\n", '%0A', $message);
        return preg_replace('/(^|\s)@([a-zA-Z0-9_\-]+)(\s|$)/', '$1`@$2`$3', $message) ?? $message;
    }
}
