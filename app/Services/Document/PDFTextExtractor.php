<?php

declare(strict_types=1);

namespace App\Services\Document;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Parser;

class PDFTextExtractor
{
    public function extract(string $filePath): string
    {
        $absolutePath = Storage::disk('materials')->path($filePath);

        if (! is_file($absolutePath)) {
            throw new RuntimeException(sprintf('PDF file not found at [%s].', $filePath));
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($absolutePath);
        $text = $pdf->getText();
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = (string) preg_replace('/[^\P{C}\n\r\t]+/u', '', $text);
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('No extractable text found in PDF.');
        }

        return $text;
    }
}
