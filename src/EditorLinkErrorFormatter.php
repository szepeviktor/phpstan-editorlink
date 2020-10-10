<?php declare(strict_types = 1);

namespace SzepeViktor\PHPStan\EditorLink;

use PHPStan\Command\AnalyseCommand;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

class EditorLinkErrorFormatter implements ErrorFormatter
{

	/** @var RelativePathHelper */
	private $relativePathHelper;

	public function __construct(
		RelativePathHelper $relativePathHelper
	)
	{
		$this->relativePathHelper = $relativePathHelper;
	}

	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output
	): int
	{
		$projectConfigFile = 'phpstan.neon';
		if ($analysisResult->getProjectConfigFile() !== null) {
			$projectConfigFile = $this->relativePathHelper->getRelativePath($analysisResult->getProjectConfigFile());
		}

		$style = $output->getStyle();

		if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
			$style->success('No errors');

			return 0;
		}

		/** @var array<string, \PHPStan\Analyser\Error[]> $fileErrors */
		$fileErrors = [];
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!isset($fileErrors[$fileSpecificError->getFile()])) {
				$fileErrors[$fileSpecificError->getFile()] = [];
			}

			$fileErrors[$fileSpecificError->getFile()][] = $fileSpecificError;
		}

		foreach ($fileErrors as $file => $errors) {
			$rows = [];
			$relativeFilePath = $this->relativePathHelper->getRelativePath($file);

			foreach ($errors as $error) {
				$rows[] = [
					(string) $error->getLine(),
					$error->getMessage() . "\n" . $relativeFilePath . ':' . (string) $error->getLine(),
				];
			}

			$style->table(['Line', $relativeFilePath], $rows);
		}

		if (count($analysisResult->getNotFileSpecificErrors()) > 0) {
			$style->table(['', 'Error'], array_map(static function (string $error): array {
				return ['', $error];
			}, $analysisResult->getNotFileSpecificErrors()));
		}

		$warningsCount = count($analysisResult->getWarnings());
		if ($warningsCount > 0) {
			$style->table(['', 'Warning'], array_map(static function (string $warning): array {
				return ['', $warning];
			}, $analysisResult->getWarnings()));
		}

		$finalMessage = sprintf($analysisResult->getTotalErrorsCount() === 1 ? 'Found %d error' : 'Found %d errors', $analysisResult->getTotalErrorsCount());
		if ($warningsCount > 0) {
			$finalMessage .= sprintf($warningsCount === 1 ? ' and %d warning' : ' and %d warnings', $warningsCount);
		}

		if ($analysisResult->getTotalErrorsCount() > 0) {
			$style->error($finalMessage);
		} else {
			$style->warning($finalMessage);
		}

		return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
	}

}
