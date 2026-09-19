<?php

declare(strict_types=1);

namespace App\Support\Curriculum\Pipeline;

use App\Services\Document\TextChunker;
use RuntimeException;
use Smalot\PdfParser\Parser;

/**
 * Extracts UTF-8 Arabic text from curriculum PDFs and splits into lesson/unit chunks.
 */
class CurriculumPdfChunker
{
    public function __construct(
        private readonly TextChunker $textChunker = new TextChunker,
        private readonly Parser $parser = new Parser,
    ) {}

    /**
     * @return list<array{index: int, title: string, content: string}>
     */
    public function chunkFromPdf(string $absoluteOrRelativePath): array
    {
        $absolute = $this->resolvePath($absoluteOrRelativePath);

        if (! is_file($absolute)) {
            throw new RuntimeException("Curriculum PDF not found: {$absolute}");
        }

        $pdf = $this->parser->parseFile($absolute);
        $text = $this->normalizeArabicText((string) $pdf->getText());

        if ($text === '') {
            throw new RuntimeException('No extractable text found in curriculum PDF.');
        }

        return $this->chunkFromText($text);
    }

    /**
     * @return list<array{index: int, title: string, content: string}>
     */
    public function chunkFromText(string $text, int $maxLength = 1800, int $overlap = 120): array
    {
        $text = $this->normalizeArabicText($text);
        if ($text === '') {
            return [];
        }

        $sections = $this->splitByHeadings($text);

        if ($sections === []) {
            return $this->sizeBasedChunks($text, $maxLength, $overlap);
        }

        $chunks = [];
        $index = 0;
        foreach ($sections as $section) {
            $title = $section['title'];
            $content = trim($section['content']);
            if ($content === '') {
                continue;
            }

            if (mb_strlen($content) <= $maxLength) {
                $chunks[] = [
                    'index' => $index++,
                    'title' => $title,
                    'content' => $content,
                ];

                continue;
            }

            foreach ($this->textChunker->chunk($content, $maxLength, $overlap) as $partIndex => $part) {
                $chunks[] = [
                    'index' => $index++,
                    'title' => $partIndex === 0 ? $title : $title.' (تتمة '.($partIndex + 1).')',
                    'content' => $part,
                ];
            }
        }

        return $chunks;
    }

    /**
     * @return list<array{title: string, content: string}>
     */
    private function splitByHeadings(string $text): array
    {
        $pattern = '/(?=(?:^|\n)\s*(?:الوحدة\s+(?:الأولى|الثانية|الثالثة|الرابعة|الخامسة|السادسة|السابعة|الثامنة|التاسعة|العاشرة|\d+)|الدرس\s+\d+)\s*[:：\-–]?\s*[^\n]*)/u';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false || count($parts) < 2) {
            // Try capturing headings explicitly
            if (! preg_match_all(
                '/((?:الوحدة\s+(?:الأولى|الثانية|الثالثة|الرابعة|الخامسة|السادسة|السابعة|الثامنة|التاسعة|العاشرة|\d+)|الدرس\s+\d+)\s*[:：\-–]?\s*[^\n]*)([\s\S]*?)(?=(?:الوحدة\s+|الدرس\s+\d+)|\z)/u',
                $text,
                $matches,
                PREG_SET_ORDER,
            )) {
                return [];
            }

            $sections = [];
            foreach ($matches as $match) {
                $title = trim($match[1]);
                $body = trim($match[2]);
                if ($title === '') {
                    continue;
                }
                $sections[] = [
                    'title' => $title,
                    'content' => trim($title."\n".$body),
                ];
            }

            return $sections;
        }

        $sections = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $lines = preg_split('/\R/u', $part) ?: [$part];
            $title = trim((string) ($lines[0] ?? 'قسم'));
            $sections[] = [
                'title' => $title,
                'content' => $part,
            ];
        }

        return $sections;
    }

    /**
     * @return list<array{index: int, title: string, content: string}>
     */
    private function sizeBasedChunks(string $text, int $maxLength, int $overlap): array
    {
        $chunks = [];
        foreach ($this->textChunker->chunk($text, $maxLength, $overlap) as $index => $content) {
            $chunks[] = [
                'index' => $index,
                'title' => 'مقطع '.($index + 1),
                'content' => $content,
            ];
        }

        return $chunks;
    }

    public function normalizeArabicText(string $text): string
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        // Strip control chars except newlines/tabs
        $text = (string) preg_replace('/[^\P{C}\n\r\t]+/u', '', $text);
        // Normalize Arabic presentation forms / tatweel noise lightly
        $text = str_replace("\u{0640}", '', $text);
        $text = (string) preg_replace("/[ \t]+/u", ' ', $text);
        $text = (string) preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text);
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        $fromCurriculum = storage_path('curriculum/'.$path);
        if (is_file($fromCurriculum)) {
            return $fromCurriculum;
        }

        $fromBase = base_path($path);
        if (is_file($fromBase)) {
            return $fromBase;
        }

        return $path;
    }
}
