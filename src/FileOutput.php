<?php

declare(strict_types=1);

namespace noximo;

use Nette\IOException;
use Nette\Utils\FileSystem;
use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use Symfony\Component\Console\Style\OutputStyle;
use Webmozart\PathUtil\Path;

class FileOutput implements ErrorFormatter
{
    /** @var string */
    public const ERROR = 'error';

    /** @var string */
    public const LINK = 'link';

    /** @var string */
    public const LINE = 'line';

    /** @var string */
    public const FILES = 'files';

    /** @var string */
    public const UNKNOWN = 'unknown';

    /** @var string */
    public const IGNORE = 'ignore';

    /** @var string */
    private $link = 'editor://open/?file=%file&line=%line';

    /**
     * @var ErrorFormatter|null
     */
    private $defaultFormatter;

    /**
     * @var string
     */
    private $outputFile;

    /**
     * @var string
     */
    private $template;

    /**
     * FileOutput constructor.
     * @throws \Safe\Exceptions\DirException
     */
    public function __construct(string $outputFile, ?ErrorFormatter $defaultFormatterClass = null, ?string $customTemplate = null)
    {
        $this->defaultFormatter = $defaultFormatterClass;
        $cwd = \Safe\getcwd() . DIRECTORY_SEPARATOR;
        try {
            $outputFile = Strings::replace($outputFile, '{time}', (string)time());
        } catch (RegexpException $e) {
        }

        $this->outputFile = Path::canonicalize($cwd . $outputFile);

        $customTemplateFile = $customTemplate !== null ? Path::canonicalize($cwd . $customTemplate) : null;
        if ($customTemplateFile !== null) {
            $this->template = $customTemplateFile;
        } else {
            $this->template = __DIR__ . DIRECTORY_SEPARATOR . 'table.phtml';
        }
    }

    /**
     * Formats the errors and outputs them to the console.
     * @return int Error code.
     */
    public function formatErrors(AnalysisResult $analysisResult, OutputStyle $style): int
    {
        if ($this->defaultFormatter !== null) {
            $this->defaultFormatter->formatErrors($analysisResult, $style);
        }
        try {
            $this->generateFile($analysisResult);
            $style->writeln('Note: Analysis outputted into file ' . $this->outputFile . '.');
        } catch (IOException $e) {
            $style->error('Analysis could not be outputted into file. ' . $e->getMessage());
        }

        return $analysisResult->hasErrors() ? 1 : 0;
    }

    /**
     * @param mixed[] $data
     */
    public function getTable(array $data): string
    {
        ob_start(function (): void {
        });
        require $this->template;

        $output = ob_get_clean();

        return $output !== false ? $output : 'Output failed.';
    }

    private function generateFile(AnalysisResult $analysisResult): void
    {
        $output = [
            self::UNKNOWN => [],
            self::FILES => [],
        ];
        if ($analysisResult->hasErrors()) {
            foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
                $output[self::UNKNOWN][] = $notFileSpecificError;
            }

            foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
                $file = $fileSpecificError->getFile();
                $line = $fileSpecificError->getLine() ?? 1;
                $link = strtr($this->link, ['%file' => $file, '%line' => $line]);
                $output[self::FILES][$file][] = [
                    self::ERROR => self::formatMessage($fileSpecificError->getMessage()),
                    self::LINK => $link,
                    self::LINE => $line,
                    self::IGNORE => self::formatRegex($fileSpecificError->getMessage()),
                ];
            }

            foreach ($output[self::FILES] as &$file) {
                usort($file, function ($a, $b) {
                    return -1 * ($a[self::LINE] <=> $b[self::LINE]);
                });
            }
            unset($file);
        }

        FileSystem::write($this->outputFile, $this->getTable($output));
    }

    private static function formatMessage(string $message): string
    {
        $words = explode(' ', $message);
        $words = array_map(function ($word) {
            if (Strings::match($word, '/[^a-zA-Z,.]|(string)|(bool)|(boolean)|(int)|(integer)|(float)/')) {
                $word = '<b>' . $word . '</b>';
            }

            return $word;
        }, $words);

        return implode(' ', $words);
    }

    /**
     * @param string $message
     * @return string
     * @throws RegexpException
     */
    private static function formatRegex(string $message): string
    {
        $quotes = "'";
        $message = rtrim($message, '.');
        $message = preg_quote($message, '#');

        if (Strings::contains($message, "'")) {
            $quotes = '"';
            $message = Strings::replace($message, '/\\\\/', '\\\\\\');
        }

        return "- $quotes#" . $message . "#$quotes";
    }
}
