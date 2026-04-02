<?php

declare(strict_types=1);

final class ImportController extends Controller
{
    private function service(): ImportService
    {
        $db = Database::getInstance(db_config());
        return new ImportService(new ImportModel($db), new JournalModel($db), $db);
    }

    public function index(): void
    {
        $target = strtolower(trim((string) get_query('target', '')));
        if ($target === 'coa') {
            $this->redirect('/coa');
        }
        if ($target === 'journal') {
            $this->redirect('/journals');
        }

        try {
            $this->view('imports/views/index', [
                'title' => 'Import Excel',
                'importErrors' => Session::pull('import_errors', []),
                'importSuccess' => Session::pull('import_success', ''),
                'importResult' => Session::pull('import_result', []),
                'unitOptions' => business_unit_options(),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            render_error_page(500, 'Halaman import Excel belum dapat dibuka. Pastikan modul import sudah terpasang dengan lengkap.', $e);
        }
    }

    private function redirectTarget(string $fallback): string
    {
        $value = trim((string) post('redirect_to', $fallback));
        if ($value === '' || $value[0] !== '/') {
            return $fallback;
        }

        return $value;
    }

    private function resolveJournalBusinessUnit(): array
    {
        $rawValue = trim((string) post('journal_business_unit_id', ''));
        if ($rawValue === '' || $rawValue === '0') {
            return ['id' => null, 'label' => 'Global / Semua unit'];
        }

        $unitId = (int) $rawValue;
        if ($unitId <= 0) {
            throw new RuntimeException('Pilihan unit usaha tujuan import jurnal tidak valid.');
        }

        $unit = find_business_unit($unitId);
        if (!$unit) {
            throw new RuntimeException('Unit usaha tujuan import jurnal tidak ditemukan.');
        }
        if ((int) ($unit['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Unit usaha tujuan import jurnal sedang nonaktif.');
        }

        return [
            'id' => (int) ($unit['id'] ?? 0),
            'label' => business_unit_label($unit, false),
        ];
    }

    public function template(): void
    {
        try {
            $type = strtolower(trim((string) get_query('type', '')));
            $map = [
                'coa' => [
                    'path' => ROOT_PATH . '/public/templates/import_coa_template.xlsx',
                    'name' => 'import_coa_template.xlsx',
                ],
                'journal' => [
                    'path' => ROOT_PATH . '/public/templates/import_journal_template.xlsx',
                    'name' => 'import_journal_template.xlsx',
                ],
            ];

            if (!isset($map[$type])) {
                http_response_code(404);
                render_error_page(404, 'Template import yang diminta tidak ditemukan.');
                return;
            }

            $file = $map[$type]['path'];
            if (!is_file($file)) {
                http_response_code(404);
                render_error_page(404, 'File template import tidak tersedia di server.');
                return;
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $map[$type]['name'] . '"');
            header('Content-Length: ' . (string) filesize($file));
            readfile($file);
            exit;
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Template import belum dapat diunduh. Silakan coba lagi.', $e);
        }
    }

    public function importCoa(): void
    {
        $this->guardCsrf();
        $file = $_FILES['coa_file'] ?? [];
        [$valid, $message] = import_validate_upload($file);
        if (!$valid) {
            Session::set('import_errors', [$message]);
            Session::set('import_feedback_url', '');
            $this->redirect($this->redirectTarget('/coa'));
        }

        $tempFile = null;
        try {
            $tempFile = import_store_temp_file($file, 'coa');
            $reader = new XlsxReader();
            $rows = $reader->readFirstSheet($tempFile);
            $overwriteExisting = (string) post('coa_overwrite', '0') === '1';
            $result = $this->service()->importCoa($rows, $overwriteExisting);
            if (!$result['success']) {
                Session::set('import_errors', $result['errors']);
                Session::set('import_result', ['type' => 'COA', 'imported' => 0, 'updated' => 0]);
                $this->redirect($this->redirectTarget('/coa'));
            }

            $successParts = [];
            if ((int) ($result['imported'] ?? 0) > 0) {
                $successParts[] = 'ditambahkan: ' . (int) $result['imported'];
            }
            if ((int) ($result['updated'] ?? 0) > 0) {
                $successParts[] = 'ditimpa/diperbarui: ' . (int) $result['updated'];
            }
            if ($successParts === []) {
                $successParts[] = 'tidak ada perubahan';
            }
            Session::set('import_success', 'Import COA berhasil (' . implode(', ', $successParts) . ').');
            Session::set('import_result', ['type' => 'COA', 'imported' => (int) ($result['imported'] ?? 0), 'updated' => (int) ($result['updated'] ?? 0)]);
            $this->redirect($this->redirectTarget('/coa'));
        } catch (Throwable $e) {
            log_error($e);
            Session::set('import_errors', ['Import COA gagal diproses: ' . $e->getMessage()]);
            Session::set('import_feedback_url', '');
            $this->redirect($this->redirectTarget('/coa'));
        } finally {
            import_cleanup_temp_file($tempFile);
        }
    }

    public function importJournal(): void
    {
        $this->guardCsrf();
        $redirectTarget = $this->redirectTarget('/journals');
        $rawBusinessUnitId = trim((string) post('journal_business_unit_id', ''));
        with_old_input(['journal_business_unit_id' => $rawBusinessUnitId]);

        $file = $_FILES['journal_file'] ?? [];
        [$valid, $message] = import_validate_upload($file);
        if (!$valid) {
            Session::set('import_errors', [$message]);
            Session::set('import_feedback_url', '');
            $this->redirect($redirectTarget);
        }

        $tempFile = null;
        try {
            $targetUnit = $this->resolveJournalBusinessUnit();
            $tempFile = import_store_temp_file($file, 'journal');
            $reader = new XlsxReader();
            $rows = $reader->readFirstSheet($tempFile);
            $userId = (int) (Auth::user()['id'] ?? 0);
            $result = $this->service()->importJournal($rows, $userId, $targetUnit['id']);
            if (!$result['success']) {
                $errors = $result['errors'] ?? ['Import jurnal gagal tanpa rincian.'];
                if (($result['feedback_rows'] ?? []) !== []) {
                    $feedbackUrl = $this->storeJournalFeedbackFile((array) ($result['feedback_headers'] ?? []), (array) $result['feedback_rows']);
                    if ($feedbackUrl !== '') {
                        $errors[] = 'Unduh file audit perbaikan: ' . $feedbackUrl;
                        Session::set('import_feedback_url', $feedbackUrl);
                    }
                }
                Session::set('import_errors', $errors);
                Session::set('import_result', ['type' => 'JURNAL', 'imported' => 0]);
                $this->redirect($redirectTarget);
            }

            clear_old_input();
            Session::set('import_success', 'Import jurnal berhasil. Total jurnal yang ditambahkan: ' . (int) $result['imported'] . '. Tujuan unit: ' . (string) $targetUnit['label'] . '.');
            Session::set('import_result', ['type' => 'JURNAL', 'imported' => (int) $result['imported']]);
            Session::set('import_feedback_url', '');
            $this->redirect($redirectTarget);
        } catch (Throwable $e) {
            log_error($e);
            Session::set('import_errors', ['Import jurnal gagal diproses: ' . $e->getMessage()]);
            Session::set('import_feedback_url', '');
            $this->redirect($redirectTarget);
        } finally {
            import_cleanup_temp_file($tempFile);
        }
    }

    private function storeJournalFeedbackFile(array $headers, array $rows): string
    {
        try {
            $relativeDir = '/uploads/import-feedback';
            $publicDir = ROOT_PATH . '/public' . $relativeDir;
            if (!is_dir($publicDir) && !mkdir($publicDir, 0775, true) && !is_dir($publicDir)) {
                return '';
            }

            $filename = 'journal_import_feedback_' . date('Ymd_His') . '.xls';
            $fullPath = $publicDir . '/' . $filename;
            file_put_contents($fullPath, $this->buildSpreadsheetXml('Audit Import Jurnal', $headers, $rows, 'Periksa kolom catatan lalu perbaiki file sumber sebelum import ulang.'));

            return base_url($relativeDir . '/' . $filename);
        } catch (Throwable $e) {
            log_error($e);
            return '';
        }
    }

    private function buildSpreadsheetXml(string $worksheetName, array $headers, array $rows, string $subtitle): string
    {
        $worksheetName = $this->truncateSheetName($worksheetName);
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<?mso-application progid="Excel.Sheet"?>';
        $xml[] = '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
        $xml[] = '<Styles>';
        $xml[] = '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Borders/><Font ss:FontName="Calibri" ss:Size="11"/><Interior/><NumberFormat/><Protection/></Style>';
        $xml[] = '<Style ss:ID="title"><Font ss:Bold="1" ss:Size="14"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        $xml[] = '<Style ss:ID="sub"><Font ss:Italic="1" ss:Size="10"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/></Style>';
        $xml[] = '<Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#D9E2F3" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        $xml[] = '<Style ss:ID="text"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        $xml[] = '</Styles>';
        $xml[] = '<Worksheet ss:Name="' . $this->xml($worksheetName) . '"><Table>';
        $columnCount = max(1, count($headers));
        foreach (range(1, $columnCount) as $_) {
            $xml[] = '<Column ss:AutoFitWidth="1" ss:Width="140"/>';
        }
        $xml[] = '<Row ss:Height="22"><Cell ss:MergeAcross="' . ($columnCount - 1) . '" ss:StyleID="title"><Data ss:Type="String">' . $this->xml($worksheetName) . '</Data></Cell></Row>';
        $xml[] = '<Row ss:Height="18"><Cell ss:MergeAcross="' . ($columnCount - 1) . '" ss:StyleID="sub"><Data ss:Type="String">' . $this->xml($subtitle) . '</Data></Cell></Row>';
        $xml[] = '<Row/>';
        $xml[] = '<Row>';
        foreach ($headers as $header) {
            $xml[] = '<Cell ss:StyleID="header"><Data ss:Type="String">' . $this->xml((string) $header) . '</Data></Cell>';
        }
        $xml[] = '</Row>';
        foreach ($rows as $row) {
            $xml[] = '<Row>';
            foreach ($headers as $index => $_header) {
                $value = (string) ($row[$index] ?? '');
                $xml[] = '<Cell ss:StyleID="text"><Data ss:Type="String">' . $this->xml($value) . '</Data></Cell>';
            }
            $xml[] = '</Row>';
        }
        $xml[] = '</Table></Worksheet></Workbook>';
        return implode('', $xml);
    }

    private function truncateSheetName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            $name = 'Sheet1';
        }
        $name = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name) ?: 'Sheet1';
        return function_exists('mb_substr') ? mb_substr($name, 0, 31) : substr($name, 0, 31);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function guardCsrf(): void
    {
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            exit;
        }
    }
}
