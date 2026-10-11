<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use Flames\Code\Upgrade\ThirdParty\Nette\Json;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\RelativePathHelper;
use function hash;
use function implode;
/**
 * @see https://docs.gitlab.com/ci/testing/code_quality#code-quality-report-format
 */
#[AutowiredService('errorFormatter.gitlab')]
final class GitlabErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
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
        $errorsArray = [];
        foreach ($analysisResult->fileSpecificErrors as $fileSpecificError) {
            $error = [
                'description' => $fileSpecificError->message,
                // This field is intended to be shown in the GitLab code quality scan widget and mentioned in the GitLab docs.
                // However, due to a regression in GitLab, it is currently not shown - see https://gitlab.com/gitlab-org/gitlab/-/issues/578172.
                // There is no harm in having it here, and it allows users to work around the GitLab bug by post-processing the JSON file.
                // For example, they might prepend `check_name` to `description`, causing GitLab to show the error identifier as part of the message.
                // TODO: remove this comment once the GitLab bug is fixed
                'check_name' => $fileSpecificError->identifier,
                'fingerprint' => hash('sha256', implode([$fileSpecificError->file, $fileSpecificError->line, $fileSpecificError->message])),
                'severity' => $fileSpecificError->canBeIgnored === \true ? 'major' : 'blocker',
                'location' => ['path' => $this->relativePathHelper->getRelativePath($fileSpecificError->file), 'lines' => ['begin' => $fileSpecificError->line ?? 0]],
            ];
            $errorsArray[] = $error;
        }
        foreach ($analysisResult->notFileSpecificErrors as $notFileSpecificError) {
            $errorsArray[] = ['description' => $notFileSpecificError, 'fingerprint' => hash('sha256', $notFileSpecificError), 'severity' => 'major', 'location' => ['path' => '', 'lines' => ['begin' => 0]]];
        }
        $json = Json::encode($errorsArray, Json::PRETTY);
        $output->writeRaw($json);
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
}
