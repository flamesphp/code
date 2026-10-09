<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ChangesReporting\Output\Factory;

use FlamesPrefix202610\Nette\Utils\Json;
use Flames\Code\Upgrade\Parallel\ValueObject\Bridge;
use Flames\Code\Upgrade\ValueObject\Configuration;
use Flames\Code\Upgrade\ValueObject\Error\SystemError;
use Flames\Code\Upgrade\ValueObject\ProcessResult;
/**
 * @see \Flames\Code\Upgrade\Tests\ChangesReporting\Output\Factory\JsonOutputFactoryTest
 */
final class JsonOutputFactory
{
    /**
     * @param string[] $unusedSkips
     */
    public static function create(ProcessResult $processResult, Configuration $configuration, array $unusedSkips = []): string
    {
        $errorsJson = ['totals' => ['changed_files' => $processResult->getTotalChanged()]];
        // We need onlyWithChanges: false to include all file diffs
        $fileDiffs = $processResult->getFileDiffs(\false);
        ksort($fileDiffs);
        foreach ($fileDiffs as $fileDiff) {
            $filePath = $configuration->isReportingWithRealPath() ? $fileDiff->getAbsoluteFilePath() ?? '' : $fileDiff->getRelativeFilePath();
            if ($configuration->shouldShowDiffs() && $fileDiff->getDiff() !== '') {
                $changes = [];
                foreach ($fileDiff->getRectorChanges() as $rectorWithLineChange) {
                    $changes[] = ['rector' => $rectorWithLineChange->getRectorClass(), 'line' => $rectorWithLineChange->getLine()];
                }
                $errorsJson[Bridge::FILE_DIFFS][] = ['file' => $filePath, 'diff' => $fileDiff->getDiff(), 'applied_rectors' => $fileDiff->getRectorClasses(), 'changes' => $changes];
            }
            // for Upgrade CI
            $errorsJson['changed_files'][] = $filePath;
        }
        $systemErrors = $processResult->getSystemErrors();
        $errorsJson['totals']['errors'] = count($systemErrors);
        $errorsData = self::createErrorsData($systemErrors, $configuration->isReportingWithRealPath());
        if ($errorsData !== []) {
            $errorsJson['errors'] = $errorsData;
        }
        if ($unusedSkips !== []) {
            $errorsJson['unused_skips'] = $unusedSkips;
        }
        return Json::encode($errorsJson, \true);
    }
    /**
     * @param SystemError[] $errors
     * @return mixed[]
     */
    private static function createErrorsData(array $errors, bool $absoluteFilePath): array
    {
        $errorsData = [];
        foreach ($errors as $error) {
            $errorDataJson = ['message' => $error->getMessage(), 'file' => $absoluteFilePath ? $error->getAbsoluteFilePath() : $error->getRelativeFilePath()];
            if ($error->getRectorClass() !== null) {
                $errorDataJson['caused_by'] = $error->getRectorClass();
            }
            if ($error->getLine() !== null) {
                $errorDataJson['line'] = $error->getLine();
            }
            $errorsData[] = $errorDataJson;
        }
        return $errorsData;
    }
}
