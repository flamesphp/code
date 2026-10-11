<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalyseCommand;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\CommandHelper;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\RelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Formatter\OutputFormatter;
use function array_map;
use function array_slice;
use function count;
use function explode;
use function getenv;
use function implode;
use function in_array;
use function is_string;
use function ltrim;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_replace;
#[AutowiredService('errorFormatter.table')]
final class TableErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
{
    private RelativePathHelper $relativePathHelper;
    private SimpleRelativePathHelper $simpleRelativePathHelper;
    private \PHPStan\Command\ErrorFormatter\CiDetectedErrorFormatter $ciDetectedErrorFormatter;
    private bool $showTipsOfTheDay;
    private ?string $editorUrl;
    private ?string $editorUrlTitle;
    private string $usedLevel;
    private ?int $errorsBudget;
    public const ERRORS_LIMIT = 1000;
    private const FORCE_SHOW_ALL_ERRORS = 'PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS';
    public function __construct(
        RelativePathHelper $relativePathHelper,
        #[AutowiredParameter('@simpleRelativePathHelper')]
        SimpleRelativePathHelper $simpleRelativePathHelper,
        \PHPStan\Command\ErrorFormatter\CiDetectedErrorFormatter $ciDetectedErrorFormatter,
        #[AutowiredParameter('%tipsOfTheDay%')]
        bool $showTipsOfTheDay,
        #[AutowiredParameter]
        ?string $editorUrl,
        #[AutowiredParameter]
        ?string $editorUrlTitle,
        #[AutowiredParameter]
        string $usedLevel,
        ?int $errorsBudget = null
    )
    {
        $this->relativePathHelper = $relativePathHelper;
        $this->simpleRelativePathHelper = $simpleRelativePathHelper;
        $this->ciDetectedErrorFormatter = $ciDetectedErrorFormatter;
        $this->showTipsOfTheDay = $showTipsOfTheDay;
        $this->editorUrl = $editorUrl;
        $this->editorUrlTitle = $editorUrlTitle;
        $this->usedLevel = $usedLevel;
        $this->errorsBudget = $errorsBudget;
        if ($this->errorsBudget !== null) {
            return;
        }
        $forceShowAll = getenv(self::FORCE_SHOW_ALL_ERRORS);
        if (!in_array($forceShowAll, [\false, '0'], \true)) {
            return;
        }
        $this->errorsBudget = self::ERRORS_LIMIT;
    }
    /** @api */
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $this->ciDetectedErrorFormatter->formatErrors($analysisResult, $output);
        $projectConfigFile = 'phpstan.neon';
        if ($analysisResult->projectConfigFile !== null) {
            $projectConfigFile = $this->relativePathHelper->getRelativePath($analysisResult->projectConfigFile);
        }
        $style = $output->getStyle();
        if (!($analysisResult->getTotalErrorsCount() > 0) && !(\count($analysisResult->warnings) > 0)) {
            $style->success('No errors');
            if ($this->showTipsOfTheDay) {
                if ($analysisResult->defaultLevelUsed) {
                    $output->writeLineFormatted('💡 Tip of the Day:');
                    $output->writeLineFormatted(sprintf("PHPStan is performing only the most basic checks.\nYou can pass a higher rule level through the <fg=cyan>--%s</> option\n(the default and current level is %d) to analyse code more thoroughly.", AnalyseCommand::OPTION_LEVEL, (int) AnalyseCommand::DEFAULT_LEVEL));
                    $output->writeLineFormatted('');
                }
            }
            return 0;
        }
        /** @var array<string, Error[]> $fileErrors */
        $fileErrors = [];
        foreach ($analysisResult->fileSpecificErrors as $fileSpecificError) {
            if (!isset($fileErrors[$fileSpecificError->file])) {
                $fileErrors[$fileSpecificError->file] = [];
            }
            $fileErrors[$fileSpecificError->file][] = $fileSpecificError;
        }
        $errorsBudget = $this->errorsBudget;
        $printedErrors = 0;
        foreach ($fileErrors as $file => $errors) {
            $rows = [];
            foreach ($errors as $error) {
                $message = $error->message;
                $filePath = $error->traitFilePath ?? $error->getFilePath();
                if ($error->identifier !== null && in_array($error->identifier, ['phpstan.type', 'phpstan.nativeType', 'phpstan.variable', 'phpstan.dumpType', 'phpstan.unknownExpectation'], \true)) {
                    $message = '<fg=red>' . OutputFormatter::escape($message) . '</>';
                }
                if ($error->identifier !== null) {
                    $message .= "\n";
                    $message .= '🪪  ' . $error->identifier;
                    if (!($error->canBeIgnored === \true)) {
                        $message .= ' <fg=red>(non-ignorable)</>';
                    }
                }
                if ($error->tip !== null) {
                    $tip = $error->tip;
                    $tip = str_replace('%configurationFile%', $projectConfigFile, $tip);
                    $message .= "\n";
                    if (str_contains($tip, "\n")) {
                        $lines = explode("\n", $tip);
                        foreach ($lines as $line) {
                            $message .= '💡  ' . ltrim($line, ' •') . "\n";
                        }
                        $message = rtrim($message, "\n");
                    } else {
                        $message .= '💡  ' . $tip;
                    }
                }
                if (getenv('TERMINAL_EMULATOR') === 'JetBrains-JediTerm') {
                    $title = $this->simpleRelativePathHelper->getRelativePath($filePath);
                    $message .= sprintf("\nat %s:%d", $title, $error->line ?? 0);
                } elseif (is_string($this->editorUrl)) {
                    $url = str_replace(['%file%', '%relFile%', '%line%'], [$filePath, $this->simpleRelativePathHelper->getRelativePath($filePath), (string) $error->line], $this->editorUrl);
                    if (is_string($this->editorUrlTitle)) {
                        $title = str_replace(['%file%', '%relFile%', '%line%'], [$filePath, $this->simpleRelativePathHelper->getRelativePath($filePath), (string) $error->line], $this->editorUrlTitle);
                    } else {
                        $title = $this->relativePathHelper->getRelativePath($filePath);
                    }
                    $message .= "\n✏️  <href=" . OutputFormatter::escape($url) . '>' . $title . '</>';
                }
                $rows[] = [$this->formatLineNumber($error->line), $message];
            }
            $printedErrors += count($rows);
            if ($errorsBudget !== null && $printedErrors > $errorsBudget) {
                $rows = array_slice($rows, 0, $errorsBudget - ($printedErrors - count($rows)));
                $style->table(['Line', $this->relativePathHelper->getRelativePath($file)], $rows);
                break;
            }
            $style->table(['Line', $this->relativePathHelper->getRelativePath($file)], $rows);
        }
        if (count($analysisResult->notFileSpecificErrors) > 0) {
            $style->table(['', 'Error'], array_map(static fn(string $error): array => ['', OutputFormatter::escape($error)], $analysisResult->notFileSpecificErrors));
        }
        $warningsCount = count($analysisResult->warnings);
        if ($warningsCount > 0) {
            $style->table(['', 'Warning'], array_map(static fn(string $warning): array => ['', OutputFormatter::escape($warning)], $analysisResult->warnings));
        }
        if ($errorsBudget !== null && $printedErrors > $errorsBudget) {
            $style->error(sprintf('Found %s+ errors', $errorsBudget));
            $note = [];
            $note[] = sprintf('Result is limited to the first %d errors', $errorsBudget);
            if ($this->usedLevel !== CommandHelper::DEFAULT_LEVEL) {
                $note[] = '- Consider lowering the PHPStan level';
            }
            $note[] = sprintf('- Pass %s=1 environment variable to show all errors', self::FORCE_SHOW_ALL_ERRORS);
            $note[] = '- Consider using PHPStan Pro for more comfortable error browsing';
            $note[] = '  Learn more: https://phpstan.com';
            $style->note(implode("\n", $note));
        } else {
            $finalMessage = sprintf(\count($analysisResult->fileSpecificErrors) + \count($analysisResult->notFileSpecificErrors) === 1 ? 'Found %d error' : 'Found %d errors', \count($analysisResult->fileSpecificErrors) + \count($analysisResult->notFileSpecificErrors));
            if ($warningsCount > 0) {
                $finalMessage .= sprintf($warningsCount === 1 ? ' and %d warning' : ' and %d warnings', $warningsCount);
            }
            if (\count($analysisResult->fileSpecificErrors) + \count($analysisResult->notFileSpecificErrors) > 0) {
                $style->error($finalMessage);
            } else {
                $style->warning($finalMessage);
            }
        }
        return \count($analysisResult->fileSpecificErrors) + \count($analysisResult->notFileSpecificErrors) > 0 ? 1 : 0;
    }
    private function formatLineNumber(?int $lineNumber): string
    {
        if ($lineNumber === null) {
            return '';
        }
        $isRunningInVSCodeTerminal = getenv('TERM_PROGRAM') === 'vscode';
        if ($isRunningInVSCodeTerminal) {
            return ':' . $lineNumber;
        }
        return (string) $lineNumber;
    }
}
