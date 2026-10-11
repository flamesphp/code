<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use Flames\Code\Upgrade\ThirdParty\Nette\Json;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use stdClass;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Formatter\OutputFormatter;
use function count;
use function property_exists;
final class JsonErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
{
    private bool $pretty;
    public function __construct(bool $pretty)
    {
        $this->pretty = $pretty;
    }
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $errorsArray = ['totals' => ['errors' => count($analysisResult->notFileSpecificErrors), 'file_errors' => count($analysisResult->fileSpecificErrors)], 'files' => new stdClass(), 'errors' => []];
        $tipFormatter = new OutputFormatter(\false);
        foreach ($analysisResult->fileSpecificErrors as $fileSpecificError) {
            $file = $fileSpecificError->file;
            if (!property_exists($errorsArray['files'], $file)) {
                $errorsArray['files']->{$file} = ['errors' => 0, 'messages' => []];
            }
            $errorsArray['files']->{$file}['errors']++;
            $message = ['message' => $fileSpecificError->message, 'line' => $fileSpecificError->line, 'ignorable' => $fileSpecificError->canBeIgnored === \true];
            if ($fileSpecificError->tip !== null) {
                $message['tip'] = $tipFormatter->format($fileSpecificError->tip);
            }
            if ($fileSpecificError->identifier !== null) {
                $message['identifier'] = $fileSpecificError->identifier;
            }
            $errorsArray['files']->{$file}['messages'][] = $message;
        }
        foreach ($analysisResult->notFileSpecificErrors as $notFileSpecificError) {
            $errorsArray['errors'][] = $notFileSpecificError;
        }
        $json = Json::encode($errorsArray, $this->pretty ? Json::PRETTY : 0);
        $output->writeRaw($json);
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
}
