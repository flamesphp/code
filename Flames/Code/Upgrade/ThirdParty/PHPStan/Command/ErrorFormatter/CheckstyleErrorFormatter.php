<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\RelativePathHelper;
use function count;
use function htmlspecialchars;
use function sprintf;
use const ENT_COMPAT;
use const ENT_XML1;
#[AutowiredService('errorFormatter.checkstyle')]
final class CheckstyleErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
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
        $output->writeRaw('<?xml version="1.0" encoding="UTF-8"?>');
        $output->writeLineFormatted('');
        $output->writeRaw('<checkstyle>');
        $output->writeLineFormatted('');
        foreach ($this->groupByFile($analysisResult) as $relativeFilePath => $errors) {
            $output->writeRaw(sprintf('<file name="%s">', \htmlspecialchars($relativeFilePath, \ENT_XML1 | \ENT_COMPAT, 'UTF-8')));
            $output->writeLineFormatted('');
            foreach ($errors as $error) {
                $output->writeRaw(sprintf('  <error line="%s" column="1" severity="error" message="%s"%s />', \htmlspecialchars((string) $error->getLine(), \ENT_XML1 | \ENT_COMPAT, 'UTF-8'), \htmlspecialchars($error->getMessage(), \ENT_XML1 | \ENT_COMPAT, 'UTF-8'), $error->identifier !== null ? sprintf(' source="%s"', \htmlspecialchars($error->getIdentifier(), \ENT_XML1 | \ENT_COMPAT, 'UTF-8')) : ''));
                $output->writeLineFormatted('');
            }
            $output->writeRaw('</file>');
            $output->writeLineFormatted('');
        }
        $notFileSpecificErrors = $analysisResult->notFileSpecificErrors;
        if (count($notFileSpecificErrors) > 0) {
            $output->writeRaw('<file>');
            $output->writeLineFormatted('');
            foreach ($notFileSpecificErrors as $error) {
                $output->writeRaw(sprintf('  <error severity="error" message="%s" />', \htmlspecialchars($error, \ENT_XML1 | \ENT_COMPAT, 'UTF-8')));
                $output->writeLineFormatted('');
            }
            $output->writeRaw('</file>');
            $output->writeLineFormatted('');
        }
        if (\count($analysisResult->warnings) > 0) {
            $output->writeRaw('<file>');
            $output->writeLineFormatted('');
            foreach ($analysisResult->warnings as $warning) {
                $output->writeRaw(sprintf('  <error severity="warning" message="%s" />', \htmlspecialchars($warning, \ENT_XML1 | \ENT_COMPAT, 'UTF-8')));
                $output->writeLineFormatted('');
            }
            $output->writeRaw('</file>');
            $output->writeLineFormatted('');
        }
        $output->writeRaw('</checkstyle>');
        $output->writeLineFormatted('');
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
    /**
     * Escapes values for using in XML
     *
     */
    private function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
    /**
     * Group errors by file
     *
     * @return array<string, array<Error>> Array that have as key the relative path of file
     * and as value an array with occurred errors.
     */
    private function groupByFile(AnalysisResult $analysisResult): array
    {
        $files = [];
        /** @var Error $fileSpecificError */
        foreach ($analysisResult->fileSpecificErrors as $fileSpecificError) {
            $absolutePath = $fileSpecificError->getFilePath();
            if ($fileSpecificError->traitFilePath !== null) {
                $absolutePath = $fileSpecificError->traitFilePath;
            }
            $relativeFilePath = $this->relativePathHelper->getRelativePath($absolutePath);
            $files[$relativeFilePath][] = $fileSpecificError;
        }
        return $files;
    }
}
