<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\RelativePathHelper;
use function array_keys;
use function array_values;
use function count;
use function is_string;
use function preg_replace;
use function sprintf;
use const PHP_EOL;
/**
 * @see https://www.jetbrains.com/help/teamcity/build-script-interaction-with-teamcity.html#Reporting+Inspections
 */
#[AutowiredService('errorFormatter.teamcity')]
final class TeamcityErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
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
        $result = '';
        $fileSpecificErrors = $analysisResult->fileSpecificErrors;
        $notFileSpecificErrors = $analysisResult->notFileSpecificErrors;
        $warnings = $analysisResult->warnings;
        if (count($fileSpecificErrors) === 0 && count($notFileSpecificErrors) === 0 && count($warnings) === 0) {
            return 0;
        }
        $result .= $this->createTeamcityLine('inspectionType', ['id' => 'phpstan', 'name' => 'phpstan', 'category' => 'phpstan', 'description' => 'phpstan Inspection']);
        foreach ($fileSpecificErrors as $fileSpecificError) {
            $message = $fileSpecificError->message;
            if ($fileSpecificError->identifier !== null && $fileSpecificError->canBeIgnored === \true) {
                $message .= sprintf(' (🪪 %s)', $fileSpecificError->identifier);
            }
            $result .= $this->createTeamcityLine('inspection', [
                'typeId' => 'phpstan',
                'message' => $message,
                'file' => $this->relativePathHelper->getRelativePath($fileSpecificError->file),
                'line' => $fileSpecificError->line,
                // additional attributes
                'SEVERITY' => 'ERROR',
                'ignorable' => $fileSpecificError->canBeIgnored === \true,
                'tip' => $fileSpecificError->tip,
            ]);
        }
        foreach ($notFileSpecificErrors as $notFileSpecificError) {
            $result .= $this->createTeamcityLine('inspection', [
                'typeId' => 'phpstan',
                'message' => $notFileSpecificError,
                // the file is required
                'file' => $analysisResult->projectConfigFile !== null ? $this->relativePathHelper->getRelativePath($analysisResult->projectConfigFile) : '.',
                'SEVERITY' => 'ERROR',
            ]);
        }
        foreach ($warnings as $warning) {
            $result .= $this->createTeamcityLine('inspection', [
                'typeId' => 'phpstan',
                'message' => $warning,
                // the file is required
                'file' => $analysisResult->projectConfigFile !== null ? $this->relativePathHelper->getRelativePath($analysisResult->projectConfigFile) : '.',
                'SEVERITY' => 'WARNING',
            ]);
        }
        $output->writeRaw($result);
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
    /**
     * Creates a Teamcity report line
     *
     * @param string $messageName The message name
     * @param mixed[] $keyValuePairs The key=>value pairs
     * @return string The Teamcity report line
     */
    private function createTeamcityLine(string $messageName, array $keyValuePairs): string
    {
        $string = '##teamcity[' . $messageName;
        foreach ($keyValuePairs as $key => $value) {
            if (is_string($value)) {
                $value = $this->escape($value);
            }
            $string .= ' ' . $key . '=\'' . $value . '\'';
        }
        return $string . ']' . PHP_EOL;
    }
    /**
     * Escapes the given string for Teamcity output
     *
     * @param string $string The string to escape
     * @return string The escaped string
     */
    private function escape(string $string): string
    {
        $replacements = ['~\n~' => '|n', '~\r~' => '|r', '~([\'\|\[\]])~' => '|$1'];
        return (string) preg_replace(array_keys($replacements), array_values($replacements), $string);
    }
}
