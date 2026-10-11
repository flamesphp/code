<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\AgentDetector;
use function sprintf;
#[AutowiredService('errorFormatter.raw')]
final class RawErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
{
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        foreach ($analysisResult->notFileSpecificErrors as $notFileSpecificError) {
            $output->writeRaw(sprintf('?:?:%s', $notFileSpecificError));
            $output->writeLineFormatted('');
        }
        $outputIdentifiers = $output->isVerbose() || AgentDetector::isRunningInAgent();
        foreach ($analysisResult->fileSpecificErrors as $fileSpecificError) {
            $identifier = '';
            if ($outputIdentifiers && $fileSpecificError->identifier !== null) {
                $identifier = sprintf(' [identifier=%s]', $fileSpecificError->identifier);
            }
            $output->writeRaw(sprintf('%s:%s:%s%s', $fileSpecificError->file, $fileSpecificError->line ?? '?', $fileSpecificError->message, $identifier));
            $output->writeLineFormatted('');
        }
        foreach ($analysisResult->warnings as $warning) {
            $output->writeRaw(sprintf('?:?:%s', $warning));
            $output->writeLineFormatted('');
        }
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
}
