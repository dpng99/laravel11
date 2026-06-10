<?php

namespace App\Services;

use RuntimeException;

class CustomLkjipTemplateProcessor
{
    private string $documentXml;

    public function __construct(private readonly string $templatePath)
    {
        $archive = new \PharData($templatePath);
        $this->documentXml = $this->fixBrokenMacros($archive['word/document.xml']->getContent());
        unset($archive);
    }

    public function replacePerformanceTable(string $tableXml): void
    {
        $macroPosition = strpos($this->documentXml, '${tabel_kinerja}');
        if ($macroPosition !== false) {
            $start = $this->blockStartBefore('w:p', $macroPosition);
            $end = $start === false ? false : $this->blockEnd('w:p', $start);

            if ($start !== false && $end !== false) {
                $this->documentXml = substr_replace($this->documentXml, $tableXml, $start, $end - $start);

                return;
            }
        }

        $offset = 0;
        while (($start = $this->nextBlockStart('w:tbl', $offset)) !== false) {
            $end = $this->blockEnd('w:tbl', $start);
            if ($end === false) {
                break;
            }

            $table = substr($this->documentXml, $start, $end - $start);
            if ($this->isPerformanceTable($table)) {
                $this->documentXml = substr_replace(
                    $this->documentXml,
                    $tableXml,
                    $start,
                    $end - $start
                );

                return;
            }

            $offset = $end;
        }

        throw new RuntimeException('Penanda atau tabel kinerja tidak ditemukan pada template LKJiP.');
    }

    public function replaceTableByMarker(string $marker, string $tableXml): bool
    {
        $macro = str_starts_with($marker, '${') ? $marker : '${'.$marker.'}';
        $macroPosition = strpos($this->documentXml, $macro);

        if ($macroPosition === false) {
            return false;
        }

        $start = $this->blockStartBefore('w:p', $macroPosition);
        $end = $start === false ? false : $this->blockEnd('w:p', $start);

        if ($start === false || $end === false) {
            return false;
        }

        $this->documentXml = substr_replace($this->documentXml, $tableXml, $start, $end - $start);

        return true;
    }

    public function replaceTableByAnchor(string $anchor, string $tableXml): bool
    {
        $needle = $this->normalizeSearchText($anchor);
        $offset = 0;

        while (($start = $this->nextBlockStart('w:tbl', $offset)) !== false) {
            $end = $this->blockEnd('w:tbl', $start);
            if ($end === false) {
                break;
            }

            $table = substr($this->documentXml, $start, $end - $start);
            if (str_contains($this->normalizeSearchText($this->normalizedText($table)), $needle)) {
                $this->documentXml = substr_replace($this->documentXml, $tableXml, $start, $end - $start);

                return true;
            }

            $offset = $end;
        }

        return false;
    }

    public function replacePerformanceHeading(string $satker, string $quarter, string $year): void
    {
        $offset = 0;
        while (($start = $this->nextBlockStart('w:p', $offset)) !== false) {
            $end = $this->blockEnd('w:p', $start);
            if ($end === false) {
                break;
            }

            $paragraph = substr($this->documentXml, $start, $end - $start);
            $text = strtolower($this->normalizedText($paragraph));
            if (! str_contains($text, 'perjanjian kinerja kepala kejaksaan')
                || ! str_contains($text, 'triwulan')) {
                $offset = $end;

                continue;
            }

            $values = [
                'Perjanjian Kinerja ',
                $satker,
                " Triwulan {$quarter}",
                " Tahun {$year}",
                '',
            ];

            $breakPosition = strpos($paragraph, '<w:br');
            $breakEnd = $breakPosition === false ? false : strpos($paragraph, '>', $breakPosition);
            if ($breakEnd === false) {
                $offset = $end;

                continue;
            }

            $prefix = substr($paragraph, 0, $breakEnd + 1);
            $content = substr($paragraph, $breakEnd + 1);
            $index = 0;
            $content = preg_replace_callback(
                '/(<w:t(?:\s[^>]*)?>)(.*?)(<\/w:t>)/s',
                function (array $match) use (&$index, $values) {
                    $value = htmlspecialchars($values[$index] ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $index++;

                    return $match[1].$value.$match[3];
                },
                $content
            );
            $paragraph = $prefix.$content;
            $this->documentXml = substr_replace(
                $this->documentXml,
                $paragraph,
                $start,
                $end - $start
            );

            return;
        }
    }

    public function replaceReportingPeriod(string $quarter, string $year): void
    {
        $quarter = strtoupper(trim($quarter));
        $year = trim($year);

        if (! in_array($quarter, ['I', 'II', 'III', 'IV'], true) || ! preg_match('/^\d{4}$/', $year)) {
            throw new RuntimeException('Periode laporan LKJiP tidak valid.');
        }

        $previousYear = (string) ((int) $year - 1);
        $quarterEndMonth = [
            'I' => 'Maret',
            'II' => 'Juni',
            'III' => 'September',
            'IV' => 'Desember',
        ][$quarter];
        [$signatureMonth, $signatureYear] = match ($quarter) {
            'I' => ['April', $year],
            'II' => ['Juli', $year],
            'III' => ['Oktober', $year],
            'IV' => ['Januari', (string) ((int) $year + 1)],
        };

        $this->documentXml = $this->transformVisibleParagraphText(
            $this->documentXml,
            function (string $text) use (
                $quarter,
                $year,
                $previousYear,
                $quarterEndMonth,
                $signatureMonth,
                $signatureYear
            ): string {
                // The supplied templates are reporting-year 2026 templates.
                // Older years in regulations and the 2025-2029 strategic plan
                // remain unchanged.
                $text = str_replace('2026', $year, $text);
                $text = str_replace([
                    'Triwulan I',
                    'TRIWULAN I',
                    'TRIWUAN I',
                    'triwulan I',
                    'triwulan i',
                ], [
                    "Triwulan {$quarter}",
                    "TRIWULAN {$quarter}",
                    "TRIWUAN {$quarter}",
                    "triwulan {$quarter}",
                    'triwulan '.strtolower($quarter),
                ], $text);
                $text = str_replace('Maret', $quarterEndMonth, $text);
                $text = str_replace(
                    "April {$year}",
                    "{$signatureMonth} {$signatureYear}",
                    $text
                );

                // These labels describe the previous reporting year.
                $text = preg_replace_callback(
                    '/(Sisa(?:\s+Permohonan)?\s+Tahun?\s*)2025/iu',
                    fn (array $match) => $match[1].$previousYear,
                    $text
                );
                $text = str_replace(
                    'hasil evaluasi AKIP Tahun 2025',
                    "hasil evaluasi AKIP Tahun {$previousYear}",
                    $text
                );

                // These captions are part of the active report and were left
                // at 2025 in the source templates.
                return str_replace([
                    'CAPAIAN KINERJA KEJAKSAAN RI TAHUN 2025',
                    'Realisasi Anggaran Kejaksaan RI Tahun 2025 per Program',
                    'Lampiran 1 Perjanjian Kinerja Jaksa Agung Tahun 2025',
                    'Lampiran 2 Rincian Penanganan Perkara Tindak Pidana Umum Tahun 2025',
                ], [
                    "CAPAIAN KINERJA KEJAKSAAN RI TAHUN {$year}",
                    "Realisasi Anggaran Kejaksaan RI Tahun {$year} per Program",
                    "Lampiran 1 Perjanjian Kinerja Jaksa Agung Tahun {$year}",
                    "Lampiran 2 Rincian Penanganan Perkara Tindak Pidana Umum Tahun {$year}",
                ], $text);
            }
        );
    }

    public function replaceValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->documentXml = str_replace(
                '${'.$key.'}',
                htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                $this->documentXml
            );
        }
    }

    public function replaceValueByAnchor(
        string $anchor,
        string $value,
        int $minimumDots = 3,
        string $prefix = '',
        ?string $afterText = null
    ): bool {
        $needle = $this->normalizeSearchText($anchor);

        foreach (['w:p', 'w:tbl'] as $tag) {
            $offset = 0;
            while (($start = $this->nextBlockStart($tag, $offset)) !== false) {
                $end = $this->blockEnd($tag, $start);
                if ($end === false) {
                    break;
                }

                $block = substr($this->documentXml, $start, $end - $start);
                if (! str_contains($this->normalizeSearchText($this->normalizedText($block)), $needle)) {
                    $offset = $end;

                    continue;
                }

                $pattern = '/[.…]{'.max(2, $minimumDots).',}/u';
                $updated = $this->replaceDottedTextInBlock($block, $pattern, $prefix.$value, $afterText);

                if ($updated !== null) {
                    $this->documentXml = substr_replace($this->documentXml, $updated, $start, $end - $start);

                    return true;
                }

                $offset = $end;
            }
        }

        return false;
    }

    private function replaceDottedTextInBlock(
        string $block,
        string $pattern,
        string $replacement,
        ?string $afterText
    ): ?string {
        preg_match_all(
            '/(<w:t(?:\s[^>]*)?>)(.*?)(<\/w:t>)/s',
            $block,
            $textRuns,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        $plainText = collect($textRuns)
            ->map(fn ($run) => html_entity_decode($run[2][0], ENT_XML1 | ENT_QUOTES, 'UTF-8'))
            ->implode('');
        $minimumOffset = 0;

        if ($afterText !== null) {
            $afterOffset = stripos($plainText, $afterText);
            if ($afterOffset === false) {
                return null;
            }

            $minimumOffset = $afterOffset + strlen($afterText);
        }

        $plainOffset = 0;
        foreach ($textRuns as $run) {
            $decoded = html_entity_decode($run[2][0], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            preg_match_all($pattern, $decoded, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] ?? [] as [$placeholder, $placeholderOffset]) {
                if (($plainOffset + $placeholderOffset) < $minimumOffset) {
                    continue;
                }

                $updatedText = substr_replace($decoded, $replacement, $placeholderOffset, strlen($placeholder));

                return substr_replace(
                    $block,
                    htmlspecialchars($updatedText, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                    $run[2][1],
                    strlen($run[2][0])
                );
            }

            $plainOffset += strlen($decoded);
        }

        return null;
    }

    public function saveAs(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }

        if (! copy($this->templatePath, $path)) {
            throw new RuntimeException('Template LKJiP tidak dapat disalin.');
        }

        $archive = new \PharData($path);
        $archive['word/document.xml'] = $this->documentXml;
        $archive['word/document.xml']->compress(\Phar::GZ);
        unset($archive);
    }

    private function isPerformanceTable(string $table): bool
    {
        $text = preg_replace('/\s+/', '', strtoupper($this->normalizedText($table)));

        return str_starts_with(
            (string) $text,
            'NOSASARANSTRATEGISINDIKATORKINERJATARGET'
        );
    }

    private function normalizedText(string $xml): string
    {
        $text = html_entity_decode(strip_tags($xml), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function normalizeSearchText(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        return (string) preg_replace('/[^\pL\pN]+/u', '', $text);
    }

    private function transformVisibleParagraphText(string $xml, callable $transform): string
    {
        $document = new \DOMDocument;
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('Isi template LKJiP tidak dapat dibaca.');
        }

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        foreach ($xpath->query('//w:p') as $paragraph) {
            $nodes = iterator_to_array($xpath->query('.//w:t', $paragraph));
            if ($nodes === []) {
                continue;
            }

            $original = collect($nodes)->map(fn (\DOMNode $node) => $node->nodeValue)->implode('');
            $updated = $transform($original);

            if ($updated === $original) {
                continue;
            }

            $offset = 0;
            $lastIndex = count($nodes) - 1;
            foreach ($nodes as $index => $node) {
                $length = mb_strlen($node->nodeValue, 'UTF-8');
                $node->nodeValue = $index === $lastIndex
                    ? mb_substr($updated, $offset, null, 'UTF-8')
                    : mb_substr($updated, $offset, $length, 'UTF-8');
                $offset += $length;
            }
        }

        return (string) $document->saveXML();
    }

    private function nextBlockStart(string $tag, int $offset): int|false
    {
        $plain = strpos($this->documentXml, "<{$tag}>", $offset);
        $withAttributes = strpos($this->documentXml, "<{$tag} ", $offset);

        if ($plain === false) {
            return $withAttributes;
        }

        if ($withAttributes === false) {
            return $plain;
        }

        return min($plain, $withAttributes);
    }

    private function blockEnd(string $tag, int $start): int|false
    {
        $end = strpos($this->documentXml, "</{$tag}>", $start);

        return $end === false ? false : $end + strlen("</{$tag}>");
    }

    private function blockStartBefore(string $tag, int $offset): int|false
    {
        $reverseOffset = ($offset - strlen($this->documentXml));
        $plain = strrpos($this->documentXml, "<{$tag}>", $reverseOffset);
        $withAttributes = strrpos($this->documentXml, "<{$tag} ", $reverseOffset);

        if ($plain === false) {
            return $withAttributes;
        }

        if ($withAttributes === false) {
            return $plain;
        }

        return max($plain, $withAttributes);
    }

    private function fixBrokenMacros(string $documentXml): string
    {
        return preg_replace_callback(
            '/\$(?:\{|[^{$]*\>\{)[^}$]*\}/U',
            fn (array $match) => strip_tags($match[0]),
            $documentXml
        );
    }
}
