<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\ZipArchive;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use RuntimeException;

class LkjipTemplateService
{
    public function prepareRows(array $rows): array
    {
        return collect($rows)
            ->values()
            ->map(function (array $row, int $index) {
                $nilai = (float) $row['nilai_ikss'];
                $status = $this->status($nilai);

                return [
                    'no' => $index + 1,
                    'ss_id' => trim((string) ($row['ss_id'] ?? $row['nama_ss'])),
                    'nama_ss' => trim((string) $row['nama_ss']),
                    'nama_ikss' => trim((string) $row['nama_ikss']),
                    'target_label' => $this->formatNullablePercentage($row['target'] ?? null),
                    'capaian_label' => $this->formatNullablePercentage($row['capaian'] ?? null),
                    'nilai_ikss' => $nilai,
                    'nilai_ikss_label' => $this->formatPercentage($nilai),
                    'status' => $status,
                    'narasi' => $this->narrative(
                        trim((string) $row['nama_ss']),
                        trim((string) $row['nama_ikss']),
                        $nilai,
                        $status
                    ),
                ];
            })
            ->all();
    }

    public function generate(string $path, array $metadata, array $rows, array $reportData = []): void
    {
        $this->ensureTemplateMemoryLimit();

        if (! class_exists(\ZipArchive::class)) {
            Settings::setZipClass(Settings::PCLZIP);
        }

        $rows = $this->prepareRows($rows);
        $customTemplate = $this->customTemplatePath((int) ($metadata['level'] ?? 0));

        if ($customTemplate !== null) {
            $this->generateFromCustomTemplate($path, $customTemplate, $metadata, $rows, $reportData);

            return;
        }

        $average = collect($rows)->avg('nilai_ikss') ?? 0;
        $achieved = collect($rows)->where('nilai_ikss', '>=', 100)->count();

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'marginTop' => 900,
            'marginRight' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
        ]);

        $section->addText(
            'TEMPLATE LAPORAN KINERJA INSTANSI PEMERINTAH (LKJiP)',
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );
        $section->addText(
            (string) ($metadata['satker'] ?? ''),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 240]
        );

        $metadataTable = $section->addTable(['cellMargin' => 80]);
        $this->addMetadataRow($metadataTable, 'Tahun', (string) ($metadata['tahun'] ?? ''));
        $this->addMetadataRow($metadataTable, 'Triwulan', (string) ($metadata['triwulan'] ?? ''));
        $this->addMetadataRow($metadataTable, 'Tanggal Cetak', (string) ($metadata['tanggal_cetak'] ?? ''));
        $section->addTextBreak();

        $section->addText(
            'Tabel 2',
            ['italic' => true, 'size' => 9],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $section->addText(
            sprintf(
                'Perjanjian Kinerja %s Triwulan %s Tahun %s',
                (string) ($metadata['satker'] ?? ''),
                $this->romanQuarter((string) ($metadata['triwulan'] ?? '')),
                (string) ($metadata['tahun'] ?? '')
            ),
            ['italic' => true, 'size' => 9],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 80]
        );

        $this->addPerformanceTable($section, $rows);

        $section->addTextBreak();
        $section->addText('Ringkasan Capaian', ['bold' => true, 'size' => 12]);
        $section->addText(
            sprintf(
                'Rata-rata nilai IKSS sebesar %s. Sebanyak %d dari %d IKSS telah mencapai target.',
                $this->formatPercentage((float) $average),
                $achieved,
                count($rows)
            )
        );

        $section->addTextBreak();
        $section->addText('Narasi Capaian per IKSS', ['bold' => true, 'size' => 12]);
        foreach ($rows as $row) {
            $section->addText($row['no'].'. '.$row['narasi'], null, ['spaceAfter' => 100]);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($path);
    }

    public function status(float $nilai): string
    {
        if ($nilai >= 100) {
            return 'Target tercapai';
        }

        if ($nilai >= 80) {
            return 'Perlu optimalisasi';
        }

        return 'Perlu perhatian';
    }

    private function generateFromCustomTemplate(
        string $path,
        string $templatePath,
        array $metadata,
        array $rows,
        array $reportData = []
    ): void {
        $processor = new CustomLkjipTemplateProcessor($templatePath);
        $processor->replaceValues(array_merge([
            'satker' => (string) ($metadata['satker'] ?? ''),
            'SATKER' => strtoupper((string) ($metadata['satker'] ?? '')),
            'tahun' => (string) ($metadata['tahun'] ?? ''),
            'triwulan' => (string) ($metadata['triwulan'] ?? ''),
            'triwulan_romawi' => $this->romanQuarter((string) ($metadata['triwulan'] ?? '')),
        ], $reportData['scalars'] ?? []));
        $processor->replaceReportingPeriod(
            $this->romanQuarter((string) ($metadata['triwulan'] ?? '')),
            (string) ($metadata['tahun'] ?? '')
        );
        $processor->replacePerformanceTable($this->performanceTableXml($rows));
        $processor->replacePerformanceHeading(
            (string) ($metadata['satker'] ?? ''),
            $this->romanQuarter((string) ($metadata['triwulan'] ?? '')),
            (string) ($metadata['tahun'] ?? '')
        );
        foreach ($reportData['tables'] ?? [] as $marker => $table) {
            $tableXml = $this->reportTableXml($table);
            $replaced = $processor->replaceTableByMarker($marker, $tableXml);

            if (! $replaced) {
                foreach ($table['anchors'] ?? [] as $anchor) {
                    if ($processor->replaceTableByAnchor((string) $anchor, $tableXml)) {
                        break;
                    }
                }
            }
        }
        foreach ($reportData['anchored_scalars'] ?? [] as $definition) {
            foreach ($definition['anchors'] ?? [] as $anchor) {
                if ($processor->replaceValueByAnchor(
                    (string) $anchor,
                    (string) ($definition['value'] ?? ''),
                    (int) ($definition['minimum_dots'] ?? 3),
                    (string) ($definition['prefix'] ?? ''),
                    isset($definition['after_text']) ? (string) $definition['after_text'] : null
                )) {
                    break;
                }
            }
        }
        $processor->saveAs($path);
    }

    private function reportTableXml(array $definition): string
    {
        $columns = array_values($definition['columns'] ?? []);
        $rows = array_values($definition['rows'] ?? []);
        $temporaryPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('lkjip_report_table_', true).'.docx';
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);
        $section = $phpWord->addSection();
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'layout' => 'fixed',
            'width' => 8650,
            'unit' => TblWidth::TWIP,
        ]);
        $width = count($columns) > 0 ? (int) floor(8650 / count($columns)) : 8650;

        if ($columns !== []) {
            $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
            foreach ($columns as $column) {
                $table->addCell($width, ['bgColor' => 'D9D9D9', 'valign' => 'center'])
                    ->addText((string) $column, ['bold' => true, 'name' => 'Arial', 'size' => 9], ['alignment' => Jc::CENTER]);
            }
        }

        foreach ($rows as $row) {
            $table->addRow(null, ['cantSplit' => true]);
            foreach (array_values($row) as $index => $cell) {
                $table->addCell($width, ['valign' => 'center'])->addText(
                    (string) $cell,
                    ['name' => 'Arial', 'size' => 9],
                    ['alignment' => $index === 1 ? Jc::START : Jc::CENTER]
                );
            }
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($temporaryPath);

        return $this->firstTableXml($temporaryPath);
    }

    private function performanceTableXml(array $rows): string
    {
        $temporaryPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('lkjip_table_', true).'.docx';
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);
        $section = $phpWord->addSection();

        $this->addPerformanceTable($section, $rows);
        IOFactory::createWriter($phpWord, 'Word2007')->save($temporaryPath);

        return $this->firstTableXml($temporaryPath);
    }

    private function firstTableXml(string $temporaryPath): string
    {
        $zip = new ZipArchive;
        if (! $zip->open($temporaryPath)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Tabel kinerja sementara tidak dapat dibaca.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($temporaryPath);

        if (! is_string($documentXml) || $documentXml === '') {
            throw new RuntimeException('Isi tabel kinerja sementara tidak dapat dibaca.');
        }

        $document = new \DOMDocument;
        $document->loadXML($documentXml);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $table = $xpath->query('//w:tbl')->item(0);

        if ($table === null) {
            throw new RuntimeException('Tabel kinerja sementara tidak ditemukan.');
        }

        return $document->saveXML($table);
    }

    private function addPerformanceTable($section, array $rows): void
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'layout' => 'fixed',
            'width' => 8650,
            'unit' => TblWidth::TWIP,
        ]);
        $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ([
            ['NO', 650],
            ['SASARAN STRATEGIS', 3000],
            ['INDIKATOR KINERJA', 3800],
            ['TARGET', 1200],
        ] as [$label, $width]) {
            $table->addCell($width, ['bgColor' => 'FFF200', 'noWrap' => false, 'valign' => 'center'])
                ->addText($label, ['bold' => true, 'name' => 'Arial', 'size' => 9], ['alignment' => Jc::CENTER]);
        }

        foreach (collect($rows)->groupBy('ss_id')->values() as $objectiveIndex => $objectiveRows) {
            foreach ($objectiveRows->values() as $indicatorIndex => $row) {
                $table->addRow(null, ['cantSplit' => true]);
                $merge = $indicatorIndex === 0 ? 'restart' : 'continue';

                $numberCell = $table->addCell(650, ['vMerge' => $merge, 'valign' => 'top', 'noWrap' => false]);
                $objectiveCell = $table->addCell(3000, ['vMerge' => $merge, 'valign' => 'top', 'noWrap' => false]);

                if ($indicatorIndex === 0) {
                    $numberCell->addText((string) ($objectiveIndex + 1).'.', ['bold' => true, 'name' => 'Arial', 'size' => 9], ['alignment' => Jc::CENTER]);
                    $objectiveCell->addText($row['nama_ss'], ['name' => 'Arial', 'size' => 9]);
                }

                $table->addCell(3800, ['valign' => 'top', 'noWrap' => false])
                    ->addText($row['nama_ikss'], ['name' => 'Arial', 'size' => 9]);
                $table->addCell(1200, ['valign' => 'top', 'noWrap' => false])->addText(
                    $row['target_label'],
                    ['name' => 'Arial', 'size' => 9],
                    ['alignment' => Jc::CENTER]
                );
            }
        }
    }

    private function narrative(string $ss, string $ikss, float $nilai, string $status): string
    {
        $followUp = match ($status) {
            'Target tercapai' => 'Kinerja perlu dipertahankan dan praktik baik yang mendukung capaian dapat dilanjutkan.',
            'Perlu optimalisasi' => 'Diperlukan optimalisasi pelaksanaan kegiatan agar target dapat tercapai pada periode berikutnya.',
            default => 'Diperlukan evaluasi faktor penghambat dan tindak lanjut prioritas untuk meningkatkan capaian.',
        };

        return sprintf(
            'Pada SS "%s", IKSS "%s" memperoleh nilai %s dengan status %s. %s',
            $ss,
            $ikss,
            $this->formatPercentage($nilai),
            strtolower($status),
            $followUp
        );
    }

    private function formatPercentage(float $value): string
    {
        return number_format($value, 2, ',', '.').'%';
    }

    private function formatNullablePercentage(mixed $value): string
    {
        return $value === null ? '-' : $this->formatPercentage((float) $value);
    }

    private function romanQuarter(string $triwulan): string
    {
        return match ((int) preg_replace('/\D/', '', $triwulan)) {
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            default => '',
        };
    }

    private function customTemplatePath(int $level): ?string
    {
        $filename = match ($level) {
            2 => 'lkjip-kejati.docx',
            3, 4 => 'lkjip-kejari-cabjari.docx',
            default => null,
        };

        if ($filename === null) {
            return null;
        }

        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR
            .'templates'.DIRECTORY_SEPARATOR.'lkjip'.DIRECTORY_SEPARATOR.$filename;

        return is_file($path) ? $path : null;
    }

    private function ensureTemplateMemoryLimit(): void
    {
        $current = trim((string) ini_get('memory_limit'));

        if ($current === '-1') {
            return;
        }

        $unit = strtolower(substr($current, -1));
        $value = (int) $current;
        $bytes = match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };

        if ($bytes < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }
    }

    private function addMetadataRow($table, string $label, string $value): void
    {
        $table->addRow();
        $table->addCell(1800)->addText($label, ['bold' => true]);
        $table->addCell(300)->addText(':');
        $table->addCell(7200)->addText($value);
    }
}
