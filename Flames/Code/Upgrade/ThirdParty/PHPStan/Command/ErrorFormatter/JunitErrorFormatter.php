<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\RelativePathHelper;
use function htmlspecialchars;
use function sprintf;
use const ENT_COMPAT;
use const ENT_XML1;
#[AutowiredService('errorFormatter.junit')]
final class JunitErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
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
        $totalFailuresCount = \count($analysisResult->fileSpecificErrors) + \count($analysisResult->notFileSpecificErrors);
        $totalTestsCount = $analysisResult->getTotalErrorsCount() > 0 ? $totalFailuresCount : 1;
        $result = '<?xml version="1.0" encoding="UTF-8"?>';
        $result .= sprintf('<testsuite failures="%d" name="phpstan" tests="%d" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.5.1/platform-tests/src/test/resources/jenkins-junit.xsd">', $totalFailuresCount, $totalTestsCount);
        foreach ($analysisResult->fileSpecificErrors as $fileSpecificError) {
            $fileName = $this->relativePathHelper->getRelativePath($fileSpecificError->file);
            $result .= $this->createTestCase(sprintf('%s:%s', $fileName, (string) $fileSpecificError->line), 'ERROR', $fileSpecificError->message);
        }
        foreach ($analysisResult->notFileSpecificErrors as $notFileSpecificError) {
            $result .= $this->createTestCase('General error', 'ERROR', $notFileSpecificError);
        }
        foreach ($analysisResult->warnings as $warning) {
            $result .= $this->createTestCase('Warning', 'WARNING', $warning);
        }
        if (!($analysisResult->getTotalErrorsCount() > 0)) {
            $result .= $this->createTestCase('phpstan', '');
        }
        $result .= '</testsuite>';
        $output->writeRaw($result);
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
    /**
     * Format a single test case
     *
     *
     */
    private function createTestCase(string $reference, string $type, ?string $message = null): string
    {
        $result = sprintf('<testcase name="%s">', \htmlspecialchars($reference, \ENT_XML1 | \ENT_COMPAT, 'UTF-8'));
        if ($message !== null) {
            $result .= sprintf('<failure type="%s" message="%s" />', \htmlspecialchars($type, \ENT_XML1 | \ENT_COMPAT, 'UTF-8'), \htmlspecialchars($message, \ENT_XML1 | \ENT_COMPAT, 'UTF-8'));
        }
        $result .= '</testcase>';
        return $result;
    }
    /**
     * Escapes values for using in XML
     *
     */
    private function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
