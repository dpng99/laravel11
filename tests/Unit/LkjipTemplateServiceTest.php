<?php

namespace Tests\Unit;

use App\Services\LkjipTemplateService;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\ZipArchive;
use PHPUnit\Framework\TestCase;

class LkjipTemplateServiceTest extends TestCase
{
    public function test_it_assigns_status_and_narrative_from_ikss_value(): void
    {
        $service = new LkjipTemplateService;

        $rows = $service->prepareRows([
            ['nama_ss' => 'SS Pertama', 'nama_ikss' => 'IKSS A', 'target' => 90, 'capaian' => 94.5, 'nilai_ikss' => 105],
            ['nama_ss' => 'SS Kedua', 'nama_ikss' => 'IKSS B', 'nilai_ikss' => 85],
            ['nama_ss' => 'SS Ketiga', 'nama_ikss' => 'IKSS C', 'nilai_ikss' => 79.5],
        ]);

        $this->assertSame('Target tercapai', $rows[0]['status']);
        $this->assertSame('Perlu optimalisasi', $rows[1]['status']);
        $this->assertSame('Perlu perhatian', $rows[2]['status']);
        $this->assertStringContainsString('IKSS C', $rows[2]['narasi']);
        $this->assertSame('79,50%', $rows[2]['nilai_ikss_label']);
        $this->assertSame('90,00%', $rows[0]['target_label']);
        $this->assertSame('94,50%', $rows[0]['capaian_label']);
    }

    public function test_it_generates_a_word_document_with_ss_and_ikss_content(): void
    {
        $service = new LkjipTemplateService;
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('lkjip_test_', true).'.docx';

        $service->generate($path, [
            'satker' => 'Satker Pengujian',
            'tahun' => '2026',
            'triwulan' => 'TW 2',
            'tanggal_cetak' => '9 Juni 2026',
        ], [
            ['nama_ss' => 'SS Pengujian', 'nama_ikss' => 'IKSS Pengujian A', 'target' => 90, 'nilai_ikss' => 101.25],
            ['nama_ss' => 'SS Pengujian', 'nama_ikss' => 'IKSS Pengujian B', 'target' => 95, 'nilai_ikss' => 99.5],
        ]);

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));

        if (! class_exists(\ZipArchive::class)) {
            Settings::setZipClass(Settings::PCLZIP);
        }

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('SS Pengujian', $documentXml);
        $this->assertStringContainsString('IKSS Pengujian A', $documentXml);
        $this->assertStringContainsString('IKSS Pengujian B', $documentXml);
        $this->assertStringContainsString('SASARAN STRATEGIS', $documentXml);
        $this->assertStringContainsString('INDIKATOR KINERJA', $documentXml);
        $this->assertStringContainsString('Perjanjian Kinerja Satker Pengujian Triwulan II Tahun 2026', $documentXml);
        $this->assertStringContainsString('<w:vMerge w:val="restart"', $documentXml);
        $this->assertStringContainsString('<w:vMerge w:val="continue"', $documentXml);
        $this->assertStringContainsString('target tercapai', $documentXml);
    }

    public function test_it_fills_the_kejati_custom_template(): void
    {
        $this->assertCustomTemplateIsFilled(2);
    }

    public function test_it_fills_the_kejari_cabjari_custom_template(): void
    {
        $this->assertCustomTemplateIsFilled(3);
    }

    public function test_it_replaces_an_existing_custom_template_table_from_report_data(): void
    {
        ini_set('memory_limit', '256M');

        $service = new LkjipTemplateService;
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('lkjip_report_data_', true).'.docx';

        $service->generate($path, [
            'satker' => 'KEJATI PENGUJIAN',
            'tahun' => '2026',
            'triwulan' => 'TW 1',
            'tanggal_cetak' => '9 Juni 2026',
            'level' => 2,
        ], [
            ['ss_id' => 'SS1', 'nama_ss' => 'Sasaran Pengujian', 'nama_ikss' => 'IKSS Pengujian', 'target' => 90, 'nilai_ikss' => 100],
        ], [
            'tables' => [
                'table:ikss1.skm.services' => [
                    'columns' => ['No', 'Nama Pelayanan', 'Nilai SKM'],
                    'rows' => [['1', 'Layanan Pengujian Otomatis', '88,50']],
                    'anchors' => ['No Nama Pelayanan Nilai Survei Kepuasan Masyarakat'],
                ],
            ],
        ]);

        $archive = new \PharData($path);
        $documentXml = $archive['word/document.xml']->getContent();
        unset($archive);
        @unlink($path);

        $this->assertStringContainsString('Layanan Pengujian Otomatis', $documentXml);
        $this->assertStringContainsString('88,50', $documentXml);
    }

    public function test_it_replaces_a_dotted_placeholder_using_a_scalar_anchor(): void
    {
        ini_set('memory_limit', '256M');

        $service = new LkjipTemplateService;
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('lkjip_anchor_', true).'.docx';

        $service->generate($path, [
            'satker' => 'KEJAKSAAN PENGUJIAN',
            'tahun' => '2026',
            'triwulan' => 'TW 1',
            'tanggal_cetak' => '9 Juni 2026',
            'level' => 3,
        ], [
            ['ss_id' => 'SS1', 'nama_ss' => 'Sasaran Pengujian', 'nama_ikss' => 'IKSS Pengujian', 'target' => 90, 'nilai_ikss' => 100],
        ], [
            'anchored_scalars' => [
                'param:skm.target_pk' => [
                    'value' => '87,65',
                    'anchors' => ['Mengacu pada target Perjanjian Kinerja Kepala Kejaksaan'],
                    'minimum_dots' => 3,
                    'after_text' => 'sebesar',
                ],
            ],
        ]);

        $archive = new \PharData($path);
        $documentXml = $archive['word/document.xml']->getContent();
        unset($archive);
        @unlink($path);

        $this->assertTrue(str_contains($documentXml, '87,65'), 'Nilai anchor tidak ditemukan pada document.xml.');
    }

    private function assertCustomTemplateIsFilled(int $level): void
    {
        ini_set('memory_limit', '256M');

        $service = new LkjipTemplateService;
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid("lkjip_custom_{$level}_", true).'.docx';

        $service->generate($path, [
            'satker' => 'KEJAKSAAN PENGUJIAN',
            'tahun' => '2027',
            'triwulan' => 'TW 3',
            'tanggal_cetak' => '9 Juni 2026',
            'level' => $level,
        ], [
            ['ss_id' => 'SS-UJI', 'nama_ss' => 'Sasaran Strategis Otomatis', 'nama_ikss' => 'Indikator Kinerja Otomatis A', 'target' => 90, 'nilai_ikss' => 101.25],
            ['ss_id' => 'SS-UJI', 'nama_ss' => 'Sasaran Strategis Otomatis', 'nama_ikss' => 'Indikator Kinerja Otomatis B', 'target' => 95, 'nilai_ikss' => 99.5],
        ]);

        $this->assertFileExists($path);
        $this->assertGreaterThan(5_000_000, filesize($path));

        $archive = new \PharData($path);
        $documentXml = $archive['word/document.xml']->getContent();
        unset($archive);
        @unlink($path);
        $documentText = html_entity_decode(strip_tags($documentXml), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $this->assertStringContainsString('Sasaran Strategis Otomatis', $documentXml);
        $this->assertStringContainsString('Indikator Kinerja Otomatis A', $documentXml);
        $this->assertStringContainsString('Indikator Kinerja Otomatis B', $documentXml);
        $this->assertStringContainsString('KEJAKSAAN PENGUJIAN', $documentXml);
        $this->assertStringContainsString('Triwulan III', $documentText);
        $this->assertStringContainsString('Tahun 2027', $documentText);
        $this->assertStringContainsString('Sisa tahun 2026', $documentText);
        $this->assertStringContainsString('Realisasi Anggaran Kejaksaan RI Tahun 2027 per Program', $documentText);
        $this->assertStringContainsString('September 2027', $documentText);
        $this->assertStringContainsString('Oktober 2027', $documentText);
        $this->assertStringNotContainsString('Triwulan I Tahun 2027', $documentText);
        $this->assertStringNotContainsString('triwulan I tahun 2027', $documentText);
        $this->assertStringNotContainsString('Maret 2027', $documentText);
        $this->assertStringNotContainsString('April 2027', $documentText);
        $this->assertStringNotContainsString('PK Tahun 2026', $documentText);
        $this->assertStringNotContainsString('Jumlah Jaksa tahun 2026', $documentText);
        $this->assertStringNotContainsString('PAGU Anggaran Tahun 2026', $documentText);
        $this->assertStringNotContainsString('${satker}', $documentXml);
        $this->assertStringNotContainsString('${SATKER}', $documentXml);
        $this->assertStringNotContainsString('${tabel_kinerja}', $documentXml);

        unset($documentXml);
        gc_collect_cycles();
    }
}
