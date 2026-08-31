<?php

declare(strict_types=1);

namespace Tests\Support;

final class SamplePdfFactory
{
    public static function create(string $text): string
    {
        $escapedText = str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text,
        );

        $streamContent = 'BT /F1 12 Tf 50 750 Td ('.$escapedText.') Tj ET';
        $streamObject = '4 0 obj<< /Length '.strlen($streamContent)." >>stream\n{$streamContent}\nendstream\nendobj";

        $objects = [
            '1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj',
            '2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj',
            '3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>endobj',
            $streamObject,
            '5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object."\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref'."\n";
        $pdf .= '0 '.count($objects)."\n";
        $pdf .= sprintf("%010d %05d f \n", 0, 65535);

        for ($index = 1; $index < count($offsets); $index++) {
            $pdf .= sprintf("%010d %05d n \n", $offsets[$index], 0);
        }

        $pdf .= 'trailer<< /Size '.count($objects).' /Root 1 0 R >>'."\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    public static function createWithoutExtractableText(): string
    {
        $streamContent = 'q 0 0 0 rg 0 0 100 100 re f Q';
        $streamObject = '4 0 obj<< /Length '.strlen($streamContent)." >>stream\n{$streamContent}\nendstream\nendobj";

        $objects = [
            '1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj',
            '2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj',
            '3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>endobj',
            $streamObject,
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object."\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref'."\n";
        $pdf .= '0 '.count($objects)."\n";
        $pdf .= sprintf("%010d %05d f \n", 0, 65535);

        for ($index = 1; $index < count($offsets); $index++) {
            $pdf .= sprintf("%010d %05d n \n", $offsets[$index], 0);
        }

        $pdf .= 'trailer<< /Size '.count($objects).' /Root 1 0 R >>'."\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
