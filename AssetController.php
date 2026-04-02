<?php

declare(strict_types=1);

final class AssetController extends Controller
{
    private function model(): AssetModel
    {
        return new AssetModel(Database::getInstance(db_config()));
    }

    private function userId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    public function index(): void
    {
        try {
            $filters = $this->assetFilters();
            $rows = $this->model()->getAssets($filters);
            $summary = $this->buildSummary($rows);
            $this->view('assets/views/index', [
                'title' => 'Master Aset',
                'filters' => $filters,
                'rows' => $rows,
                'summary' => $summary,
                'categories' => $this->model()->getCategories(false),
                'units' => business_unit_options(true),
                'groups' => asset_groups(),
                'statuses' => asset_statuses(),
                'conditions' => asset_conditions(),
                'fundingSources' => asset_funding_sources(),
                'importErrors' => Session::pull('asset_import_errors', []),
                'importSuccess' => Session::pull('asset_import_success', ''),
                'importResult' => Session::pull('asset_import_result', []),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Modul aset belum dapat dibuka. Pastikan SQL asset_module.sql sudah dijalankan.', $e);
        }
    }

    public function create(): void
    {
        $this->showAssetForm('Tambah Aset', null);
    }

    public function edit(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID aset tidak valid.');
            $this->redirect('/assets');
        }
        try {
            $row = $this->model()->findAssetById($id);
            if (!$row) {
                flash('error', 'Aset tidak ditemukan.');
                $this->redirect('/assets');
            }
            $this->showAssetForm('Edit Aset', $row);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Data aset belum dapat dibuka.');
            $this->redirect('/assets');
        }
    }

    public function store(): void
    {
        $this->saveAsset(null);
    }

    public function update(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID aset tidak valid.');
            $this->redirect('/assets');
        }
        $this->saveAsset($id);
    }

    public function delete(): void
    {
        $id = (int) get_query('id', 0);
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        if ($id <= 0) {
            flash('error', 'ID aset tidak valid.');
            $this->redirect('/assets');
        }

        try {
            $result = $this->model()->deleteAsset($id, $this->userId());
            flash('success', 'Aset berhasil dihapus dari master aset.');
            $back = trim((string) post('back_to', '/assets'));
            $this->redirect($back !== '' ? $back : '/assets');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Aset belum dapat dihapus: ' . $e->getMessage());
            $this->redirect('/assets/detail?id=' . $id);
        }
    }

    public function detail(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID aset tidak valid.');
            $this->redirect('/assets');
        }
        try {
            $asset = $this->model()->findAssetById($id, date('Y-m-d'));
            if (!$asset) {
                flash('error', 'Aset tidak ditemukan.');
                $this->redirect('/assets');
            }
            $this->view('assets/views/detail', [
                'title' => 'Kartu Aset',
                'row' => $asset,
                'mutations' => $this->model()->getMutations($id),
                'depreciations' => $this->model()->getDepreciationSchedule($id),
                'units' => business_unit_options(true),
                'journals' => $this->model()->getJournalOptions(80),
                'mutationTypes' => asset_mutation_types(),
                'statuses' => asset_statuses(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Kartu aset belum dapat dibuka.', $e);
        }
    }

    public function cardPrint(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID aset tidak valid.');
            $this->redirect('/assets');
        }
        try {
            $asset = $this->model()->findAssetById($id, date('Y-m-d'));
            if (!$asset) {
                flash('error', 'Aset tidak ditemukan.');
                $this->redirect('/assets');
            }
            $this->view('assets/views/card_print', [
                'title' => 'Cetak Kartu Aset',
                'profile' => app_profile(),
                'row' => $asset,
                'mutations' => $this->model()->getMutations($id),
                'depreciations' => $this->model()->getDepreciationSchedule($id),
            ], 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Kartu aset belum dapat dicetak.', $e);
        }
    }


    public function template(): void
    {
        try {
            $exporter = new AssetSpreadsheetExporter();
            $categories = $this->model()->getCategories(false);
            $units = business_unit_options(true);
            $coas = $this->model()->getCoaOptions();
            $filename = 'template_import_aset_' . date('Ymd_His') . '.xlsx';

            $templateRows = [asset_template_headers()];

            $exporter->download($filename, [
                [
                    'name' => 'TEMPLATE_IMPORT',
                    'rows' => $templateRows,
                    'header_rows' => 1,
                    'column_widths' => [18, 30, 16, 18, 24, 18, 12, 12, 16, 18, 16, 22, 16, 18, 18, 18, 18, 24, 22, 18, 28, 18, 20, 16, 16, 30, 32],
                    'freeze_row' => 1,
                    'auto_filter' => false,
                ],
                [
                    'name' => 'PETUNJUK',
                    'rows' => $this->templateInstructionRows(),
                    'header_rows' => 1,
                    'column_widths' => [28, 88],
                    'freeze_row' => 1,
                    'auto_filter' => false,
                ],
                [
                    'name' => 'REFERENSI_KODE',
                    'rows' => $this->templateReferenceRows($categories, $units, $coas),
                    'header_rows' => 1,
                    'column_widths' => [22, 22, 34, 74],
                    'freeze_row' => 1,
                    'auto_filter' => true,
                ],
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Template import aset belum dapat diunduh.', $e);
        }
    }

    public function export(): void
    {
        try {
            $filters = $this->assetFilters();
            $rows = $this->model()->getAssets($filters);
            $filename = 'export_aset_' . date('Ymd_His') . '.xlsx';
            $exporter = new AssetSpreadsheetExporter();

            $exportRows = [asset_template_headers()];
            foreach ($rows as $row) {
                $exportRows[] = [
                    (string) $row['asset_code'],
                    (string) $row['asset_name'],
                    (string) ($row['entry_mode'] ?? 'ACQUISITION'),
                    (string) ($row['category_code'] ?? ''),
                    (string) ($row['subcategory_name'] ?? ''),
                    (string) ($row['unit_code'] ?? ''),
                    (string) ((int) round((float) ($row['quantity'] ?? 1))),
                    (string) (($row['unit_name'] ?? '') !== '' ? $row['unit_name'] : 'unit'),
                    (string) ($row['acquisition_date'] ?? ''),
                    number_format((float) ($row['acquisition_cost'] ?? 0), 2, '.', ''),
                    (string) ($row['opening_as_of_date'] ?? ''),
                    number_format((float) ($row['opening_accumulated_depreciation'] ?? 0), 2, '.', ''),
                    number_format((float) ($row['residual_value'] ?? 0), 2, '.', ''),
                    (string) ($row['useful_life_months'] ?? ''),
                    (string) ($row['depreciation_method'] ?? ''),
                    (string) ($row['depreciation_start_date'] ?? ''),
                    (int) ($row['depreciation_allowed'] ?? 0) === 1 ? '1' : '0',
                    (string) ($row['location'] ?? ''),
                    (string) ($row['supplier_name'] ?? ''),
                    (string) ($row['source_of_funds'] ?? ''),
                    (string) ($row['funding_source_detail'] ?? ''),
                    (string) ($row['reference_no'] ?? ''),
                    (string) ($row['offset_account_code'] ?? ''),
                    (string) ($row['condition_status'] ?? ''),
                    (string) ($row['asset_status'] ?? ''),
                    (string) ($row['description'] ?? ''),
                    (string) ($row['notes'] ?? ''),
                ];
            }

            $summaryRows = [
                ['informasi', 'nilai'],
                ['dibuat_pada', date('Y-m-d H:i:s')],
                ['jumlah_baris_data', (string) count($rows)],
                ['total_nilai_perolehan', asset_currency((float) array_sum(array_map(static fn(array $row): float => (float) ($row['acquisition_cost'] ?? 0), $rows)))],
                ['total_nilai_buku', asset_currency((float) array_sum(array_map(static fn(array $row): float => (float) (($row['current_book_value'] ?? $row['acquisition_cost']) ?: 0), $rows)))],
                ['catatan', 'Sheet DATA_ASET memakai header yang sama dengan template import agar mudah diedit lalu diimport ulang.'],
            ];
            foreach ($this->exportFilterSummaryRows($filters) as $infoRow) {
                $summaryRows[] = $infoRow;
            }

            $exporter->download($filename, [
                [
                    'name' => 'DATA_ASET',
                    'rows' => $exportRows,
                    'header_rows' => 1,
                    'column_widths' => [18, 30, 16, 18, 24, 18, 12, 12, 16, 18, 16, 22, 16, 18, 18, 18, 18, 24, 22, 18, 28, 18, 20, 16, 16, 30, 32],
                    'freeze_row' => 1,
                    'auto_filter' => true,
                ],
                [
                    'name' => 'RINGKASAN_EXPORT',
                    'rows' => $summaryRows,
                    'header_rows' => 1,
                    'column_widths' => [28, 84],
                    'freeze_row' => 1,
                    'auto_filter' => false,
                ],
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Export data aset belum dapat diproses.', $e);
        }
    }

    public function import(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }

        $file = $_FILES['asset_file'] ?? [];
        [$valid, $message] = asset_import_validate_upload($file);
        if (!$valid) {
            Session::set('asset_import_errors', [$message]);
            $this->redirect('/assets');
        }

        $tempFile = null;
        try {
            $tempFile = asset_import_store_temp_file($file, 'asset');
            $rows = $this->readImportRows($tempFile);
            $result = $this->processImportRows($rows);
            if ($result['errors'] !== []) {
                Session::set('asset_import_errors', $result['errors']);
            }
            Session::set('asset_import_result', [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
            ]);
            Session::set('asset_import_success', 'Import aset selesai. Dibuat: ' . $result['created'] . ', diperbarui: ' . $result['updated'] . ', dilewati: ' . $result['skipped'] . '.');
            $this->redirect('/assets');
        } catch (Throwable $e) {
            log_error($e);
            Session::set('asset_import_errors', ['Import aset gagal diproses: ' . $e->getMessage()]);
            $this->redirect('/assets');
        } finally {
            asset_import_cleanup_temp_file($tempFile);
        }
    }

    public function storeMutation(): void
    {
        $assetId = (int) get_query('id', 0);
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        if ($assetId <= 0) {
            flash('error', 'ID aset tidak valid.');
            $this->redirect('/assets');
        }
        try {
            $asset = $this->model()->findAssetById($assetId);
            if (!$asset) {
                flash('error', 'Aset tidak ditemukan.');
                $this->redirect('/assets');
            }
            $data = [
                'mutation_date' => trim((string) post('mutation_date')),
                'mutation_type' => trim((string) post('mutation_type')),
                'to_business_unit_id' => post('to_business_unit_id') !== null && post('to_business_unit_id') !== '' ? (int) post('to_business_unit_id') : null,
                'to_location' => trim((string) post('to_location')),
                'new_status' => trim((string) post('new_status')),
                'reference_no' => trim((string) post('reference_no')),
                'linked_journal_id' => post('linked_journal_id') !== null && post('linked_journal_id') !== '' ? (int) post('linked_journal_id') : null,
                'amount' => trim((string) post('amount')),
                'notes' => trim((string) post('notes')),
            ];
            $errors = $this->validateMutation($data, $asset);
            if ($errors !== []) {
                flash('error', implode(' ', $errors));
                $this->redirect('/assets/detail?id=' . $assetId);
            }
            $this->model()->storeMutation($assetId, $data, $this->userId());
            flash('success', 'Mutasi aset berhasil disimpan.');
            $this->redirect('/assets/detail?id=' . $assetId);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Mutasi aset gagal disimpan.');
            $this->redirect('/assets/detail?id=' . $assetId);
        }
    }

    public function categories(): void
    {
        try {
            $group = trim((string) get_query('group', ''));
            $this->view('assets/views/categories', [
                'title' => 'Kategori Aset',
                'rows' => $this->model()->getCategories(true, $group),
                'group' => $group,
                'groups' => asset_groups(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Kategori aset belum dapat dibuka.', $e);
        }
    }

    public function categoryCreate(): void
    {
        $this->showCategoryForm('Tambah Kategori Aset', null);
    }

    public function categoryEdit(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID kategori tidak valid.');
            $this->redirect('/assets/categories');
        }
        try {
            $row = $this->model()->findCategoryById($id);
            if (!$row) {
                flash('error', 'Kategori aset tidak ditemukan.');
                $this->redirect('/assets/categories');
            }
            $this->showCategoryForm('Edit Kategori Aset', $row);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Kategori aset belum dapat dibuka.');
            $this->redirect('/assets/categories');
        }
    }

    public function categoryStore(): void
    {
        $this->saveCategory(null);
    }

    public function categoryUpdate(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID kategori tidak valid.');
            $this->redirect('/assets/categories');
        }
        $this->saveCategory($id);
    }

    public function categoryToggleActive(): void
    {
        $id = (int) get_query('id', 0);
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        try {
            $row = $this->model()->findCategoryById($id);
            if (!$row) {
                flash('error', 'Kategori aset tidak ditemukan.');
                $this->redirect('/assets/categories');
            }
            $newStatus = ((int) ($row['is_active'] ?? 0)) !== 1;
            $this->model()->setCategoryActive($id, $newStatus, $this->userId());
            flash('success', $newStatus ? 'Kategori aset berhasil diaktifkan.' : 'Kategori aset berhasil dinonaktifkan.');
            $this->redirect('/assets/categories');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Status kategori aset gagal diperbarui.');
            $this->redirect('/assets/categories');
        }
    }

    public function depreciation(): void
    {
        try {
            $filters = $this->reportFilters();
            $rows = $this->model()->getDepreciationRegister($filters);
            $summary = [
                'row_count' => count($rows),
                'total_depreciation' => 0.0,
                'total_accumulated' => 0.0,
                'total_book_value' => 0.0,
            ];
            foreach ($rows as $row) {
                $summary['total_depreciation'] += (float) $row['depreciation_amount'];
                $summary['total_accumulated'] += (float) $row['accumulated_depreciation'];
                $summary['total_book_value'] += (float) $row['book_value'];
            }
            $this->view('assets/views/depreciation', [
                'title' => 'Penyusutan Aset',
                'filters' => $filters,
                'rows' => $rows,
                'summary' => $summary,
                'categories' => $this->model()->getCategories(false),
                'units' => business_unit_options(true),
                'groups' => asset_groups(),
                'statuses' => asset_statuses(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman penyusutan aset belum dapat dibuka.', $e);
        }
    }

    public function rebuildDepreciation(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        try {
            $assetId = post('asset_id') !== null && post('asset_id') !== '' ? (int) post('asset_id') : null;
            $count = $this->model()->rebuildDepreciationForAll($assetId);
            flash('success', 'Perhitungan penyusutan berhasil diperbarui untuk ' . $count . ' aset.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Perhitungan penyusutan gagal diperbarui.');
        }
        $this->redirect('/assets/depreciation');
    }

    public function postAcquisitionJournal(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID aset tidak valid.');
            $this->redirect('/assets');
        }
        try {
            $journalId = $this->model()->postAcquisitionJournal($id, $this->userId());
            flash('success', 'Jurnal perolehan aset berhasil diposting. No jurnal ID: ' . $journalId . '.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', $e->getMessage() !== '' ? $e->getMessage() : 'Jurnal perolehan aset gagal dibuat.');
        }
        $this->redirect('/assets/detail?id=' . $id);
    }

    public function postDepreciation(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        $filters = $this->reportFilters();
        $depreciationDate = trim((string) post('depreciation_date', date('Y-m-t')));
        try {
            $result = $this->model()->postDepreciationForDate($depreciationDate, $filters, $this->userId());
            flash('success', 'Posting penyusutan berhasil untuk ' . $result['count'] . ' baris aset.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', $e->getMessage() !== '' ? $e->getMessage() : 'Posting penyusutan gagal diproses.');
        }
        $this->redirect('/assets/depreciation');
    }

    public function buildSnapshot(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        $year = (int) post('snapshot_year', (int) date('Y'));
        if ($year < 2000 || $year > 2100) {
            flash('error', 'Tahun snapshot tidak valid.');
            $this->redirect('/assets/reports');
        }
        try {
            $count = $this->model()->buildYearSnapshot($year, $this->userId());
            flash('success', 'Snapshot aset tahun ' . $year . ' berhasil dibuat untuk ' . $count . ' aset.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Snapshot aset tahunan gagal dibuat.');
        }
        $this->redirect('/assets/reports');
    }

    public function reports(): void
    {
        try {
            $filters = $this->assetFilters();
            if ((string) ($filters['as_of_date'] ?? '') === '') {
                $filters['as_of_date'] = date('Y-m-d');
            }
            $report = $this->model()->getReportData($filters);
            $this->view('assets/views/reports', [
                'title' => 'Laporan Aset',
                'filters' => $filters,
                'rows' => $report['rows'],
                'summary' => $report['summary'],
                'asOfDate' => $report['as_of_date'],
                'comparisonDate' => $report['comparison_date'],
                'snapshotYear' => (int) substr((string) $report['as_of_date'], 0, 4),
                'categories' => $this->model()->getCategories(false),
                'units' => business_unit_options(true),
                'groups' => asset_groups(),
                'statuses' => asset_statuses(),
                'conditions' => asset_conditions(),
                'fundingSources' => asset_funding_sources(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Laporan aset belum dapat dibuka.', $e);
        }
    }

    public function reportPrint(): void
    {
        try {
            $filters = $this->assetFilters();
            if ((string) ($filters['as_of_date'] ?? '') === '') {
                $filters['as_of_date'] = date('Y-m-d');
            }
            $report = $this->model()->getReportData($filters);
            $this->view('assets/views/print', [
                'title' => 'Cetak Laporan Aset',
                'profile' => app_profile(),
                'filters' => $filters,
                'rows' => $report['rows'],
                'summary' => $report['summary'],
                'asOfDate' => $report['as_of_date'],
                'comparisonDate' => $report['comparison_date'],
                'selectedUnitLabel' => business_unit_label(selected_unit_from_filters($filters)),
            ], 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Laporan aset belum dapat dicetak.', $e);
        }
    }

    public function reportPdf(): void
    {
        try {
            $filters = $this->assetFilters();
            if ((string) ($filters['as_of_date'] ?? '') === '') {
                $filters['as_of_date'] = date('Y-m-d');
            }
            $report = $this->model()->getReportData($filters);
            $profile = app_profile();
            $unitLabel = business_unit_label(selected_unit_from_filters($filters));
            $subtitle = 'Per ' . format_id_date($report['as_of_date']) . ' | Pembanding: ' . format_id_date((string) ($report['comparison_date'] ?? asset_comparison_date($report['as_of_date'])));
            $pdf = new ReportPdf('L');
            report_pdf_init($pdf, $profile, 'Laporan Aset', $subtitle, $unitLabel, true);
            $widths = [20, 42, 30, 24, 18, 24, 24, 24, 24, 24, 18];
            $aligns = ['L','L','L','L','C','R','R','R','R','R','C'];
            $headerPrinter = static function (ReportPdf $pdfObj) use ($profile, $subtitle, $unitLabel, $widths, $aligns): void {
                report_pdf_init($pdfObj, $profile, 'Laporan Aset', $subtitle, $unitLabel, true);
                $pdfObj->tableRow(['Kode', 'Nama Aset', 'Kategori', 'Unit', 'Tgl', 'Perolehan', 'Akm. Susut', 'Nilai Buku', 'Pembanding', 'Selisih', 'Status'], $widths, $aligns, 7, true);
            };
            $pdf->tableRow(['Kode', 'Nama Aset', 'Kategori', 'Unit', 'Tgl', 'Perolehan', 'Akm. Susut', 'Nilai Buku', 'Pembanding', 'Selisih', 'Status'], $widths, $aligns, 7, true);
            if ($report['rows'] === []) {
                $pdf->tableRow(['-', 'Tidak ada data aset untuk filter yang dipilih.', '-', '-', '-', '-', '-', '-', '-', '-', '-'], $widths, ['C','L','C','C','C','C','C','C','C','C','C'], 7, false, $headerPrinter);
            } else {
                foreach ($report['rows'] as $row) {
                    $pdf->tableRow([
                        (string) $row['asset_code'],
                        (string) $row['asset_name'],
                        (string) $row['category_name'],
                        (string) business_unit_label($row['business_unit_id'] ? ['unit_code' => $row['business_unit_code'] ?? ($row['unit_code'] ?? ''), 'unit_name' => $row['business_unit_name'] ?? ''] : null),
                        format_id_date((string) $row['acquisition_date']),
                        asset_currency((float) $row['acquisition_cost']),
                        asset_currency((float) ($row['current_accumulated_depreciation'] ?? 0)),
                        asset_currency((float) (($row['current_book_value'] ?? $row['acquisition_cost']) ?: 0)),
                        asset_currency((float) ($row['comparison_book_value'] ?? 0)),
                        asset_currency((float) ($row['book_value_delta'] ?? 0)),
                        asset_status_label((string) $row['asset_status']),
                    ], $widths, $aligns, 7, false, $headerPrinter);
                }
            }
            $pdf->tableRow(['', 'TOTAL', '', '', '', asset_currency((float) $report['summary']['total_cost']), asset_currency((float) $report['summary']['total_accumulated_depreciation']), asset_currency((float) $report['summary']['total_book_value']), asset_currency((float) ($report['summary']['comparison_book_value'] ?? 0)), asset_currency((float) ($report['summary']['book_value_delta'] ?? 0)), ''], $widths, ['L','R','L','L','C','R','R','R','R','R','C'], 7, true, $headerPrinter);
            report_pdf_footer_note($pdf, $profile);
            $pdf->output('laporan-aset.pdf');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'File PDF laporan aset belum dapat dibuat.', $e);
        }
    }

    private function saveAsset(?int $id): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }

        $input = $this->assetInput();
        with_old_input($this->assetOldInput($input));
        $errors = $this->validateAssetInput($input, $id);
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect($id === null ? '/assets/create' : '/assets/edit?id=' . $id);
        }
        try {
            if ($id === null) {
                $assetId = $this->model()->createAsset($input, $this->userId());
                clear_old_input();
                flash('success', 'Aset baru berhasil ditambahkan.');
                $this->redirect('/assets/detail?id=' . $assetId);
            }
            $this->model()->updateAsset($id, $input, $this->userId());
            clear_old_input();
            flash('success', 'Data aset berhasil diperbarui.');
            $this->redirect('/assets/detail?id=' . $id);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Data aset gagal disimpan.');
            $this->redirect($id === null ? '/assets/create' : '/assets/edit?id=' . $id);
        }
    }

    private function saveCategory(?int $id): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        $input = [
            'category_code' => strtoupper(trim((string) post('category_code'))),
            'category_name' => trim((string) post('category_name')),
            'asset_group' => trim((string) post('asset_group')),
            'default_useful_life_months' => post('default_useful_life_months') !== null && post('default_useful_life_months') !== '' ? (int) post('default_useful_life_months') : null,
            'default_depreciation_method' => trim((string) post('default_depreciation_method', 'STRAIGHT_LINE')),
            'depreciation_allowed' => (string) post('depreciation_allowed', '1') === '1',
            'asset_coa_id' => post('asset_coa_id') !== null && post('asset_coa_id') !== '' ? (int) post('asset_coa_id') : null,
            'accumulated_depreciation_coa_id' => post('accumulated_depreciation_coa_id') !== null && post('accumulated_depreciation_coa_id') !== '' ? (int) post('accumulated_depreciation_coa_id') : null,
            'depreciation_expense_coa_id' => post('depreciation_expense_coa_id') !== null && post('depreciation_expense_coa_id') !== '' ? (int) post('depreciation_expense_coa_id') : null,
            'disposal_gain_coa_id' => post('disposal_gain_coa_id') !== null && post('disposal_gain_coa_id') !== '' ? (int) post('disposal_gain_coa_id') : null,
            'disposal_loss_coa_id' => post('disposal_loss_coa_id') !== null && post('disposal_loss_coa_id') !== '' ? (int) post('disposal_loss_coa_id') : null,
            'description' => trim((string) post('description')),
            'is_active' => (string) post('is_active', '1') === '1',
        ];
        with_old_input([
            'category_code' => $input['category_code'],
            'category_name' => $input['category_name'],
            'asset_group' => $input['asset_group'],
            'default_useful_life_months' => $input['default_useful_life_months'] === null ? '' : (string) $input['default_useful_life_months'],
            'default_depreciation_method' => $input['default_depreciation_method'],
            'depreciation_allowed' => $input['depreciation_allowed'] ? '1' : '0',
            'asset_coa_id' => $input['asset_coa_id'] === null ? '' : (string) $input['asset_coa_id'],
            'accumulated_depreciation_coa_id' => $input['accumulated_depreciation_coa_id'] === null ? '' : (string) $input['accumulated_depreciation_coa_id'],
            'depreciation_expense_coa_id' => $input['depreciation_expense_coa_id'] === null ? '' : (string) $input['depreciation_expense_coa_id'],
            'disposal_gain_coa_id' => $input['disposal_gain_coa_id'] === null ? '' : (string) $input['disposal_gain_coa_id'],
            'disposal_loss_coa_id' => $input['disposal_loss_coa_id'] === null ? '' : (string) $input['disposal_loss_coa_id'],
            'description' => $input['description'],
            'is_active' => $input['is_active'] ? '1' : '0',
        ]);
        $errors = $this->validateCategoryInput($input, $id);
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect($id === null ? '/assets/categories/create' : '/assets/categories/edit?id=' . $id);
        }
        try {
            if ($id === null) {
                $this->model()->createCategory($input, $this->userId());
                flash('success', 'Kategori aset berhasil ditambahkan.');
            } else {
                $this->model()->updateCategory($id, $input, $this->userId());
                flash('success', 'Kategori aset berhasil diperbarui.');
            }
            clear_old_input();
            $this->redirect('/assets/categories');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Kategori aset gagal disimpan.');
            $this->redirect($id === null ? '/assets/categories/create' : '/assets/categories/edit?id=' . $id);
        }
    }

    private function showAssetForm(string $title, ?array $row): void
    {
        $categories = $this->model()->getCategories(false);
        $journals = $this->model()->getJournalOptions(100);
        $coaOptions = $this->model()->getCoaOptions();
        $formData = [
            'asset_code' => old('asset_code', (string) ($row['asset_code'] ?? '')),
            'asset_name' => old('asset_name', (string) ($row['asset_name'] ?? '')),
            'entry_mode' => old('entry_mode', (string) ($row['entry_mode'] ?? 'ACQUISITION')),
            'category_id' => old('category_id', isset($row['category_id']) ? (string) $row['category_id'] : ''),
            'subcategory_name' => old('subcategory_name', (string) ($row['subcategory_name'] ?? '')),
            'business_unit_id' => old('business_unit_id', isset($row['business_unit_id']) && $row['business_unit_id'] !== null ? (string) $row['business_unit_id'] : ''),
            'quantity' => old('quantity', isset($row['quantity']) ? (string) $row['quantity'] : '1'),
            'unit_name' => old('unit_name', (string) (($row['unit_name'] ?? '') !== '' ? $row['unit_name'] : 'unit')),
            'acquisition_date' => old('acquisition_date', (string) ($row['acquisition_date'] ?? date('Y-m-d'))),
            'acquisition_cost' => old('acquisition_cost', isset($row['acquisition_cost']) ? (string) $row['acquisition_cost'] : '0.00'),
            'opening_as_of_date' => old('opening_as_of_date', (string) ($row['opening_as_of_date'] ?? date('Y-m-d'))),
            'opening_accumulated_depreciation' => old('opening_accumulated_depreciation', isset($row['opening_accumulated_depreciation']) ? (string) $row['opening_accumulated_depreciation'] : '0.00'),
            'residual_value' => old('residual_value', isset($row['residual_value']) ? (string) $row['residual_value'] : '0.00'),
            'useful_life_months' => old('useful_life_months', isset($row['useful_life_months']) && $row['useful_life_months'] !== null ? (string) $row['useful_life_months'] : ''),
            'depreciation_method' => old('depreciation_method', (string) ($row['depreciation_method'] ?? 'STRAIGHT_LINE')),
            'depreciation_start_date' => old('depreciation_start_date', (string) (($row['depreciation_start_date'] ?? '') !== '' ? $row['depreciation_start_date'] : ($row['acquisition_date'] ?? date('Y-m-d')))),
            'depreciation_allowed' => old('depreciation_allowed', isset($row['depreciation_allowed']) ? ((int) $row['depreciation_allowed'] === 1 ? '1' : '0') : '1'),
            'offset_coa_id' => old('offset_coa_id', isset($row['offset_coa_id']) && $row['offset_coa_id'] !== null ? (string) $row['offset_coa_id'] : ''),
            'location' => old('location', (string) ($row['location'] ?? '')),
            'supplier_name' => old('supplier_name', (string) ($row['supplier_name'] ?? '')),
            'source_of_funds' => old('source_of_funds', (string) ($row['source_of_funds'] ?? 'HASIL_USAHA')),
            'funding_source_detail' => old('funding_source_detail', (string) ($row['funding_source_detail'] ?? '')),
            'reference_no' => old('reference_no', (string) ($row['reference_no'] ?? '')),
            'linked_journal_id' => old('linked_journal_id', isset($row['linked_journal_id']) && $row['linked_journal_id'] !== null ? (string) $row['linked_journal_id'] : ''),
            'condition_status' => old('condition_status', (string) ($row['condition_status'] ?? 'GOOD')),
            'asset_status' => old('asset_status', (string) ($row['asset_status'] ?? 'ACTIVE')),
            'acquisition_sync_status' => old('acquisition_sync_status', (string) ($row['acquisition_sync_status'] ?? 'NONE')),
            'is_active' => old('is_active', isset($row['is_active']) ? ((int) $row['is_active'] === 1 ? '1' : '0') : '1'),
            'description' => old('description', (string) ($row['description'] ?? '')),
            'notes' => old('notes', (string) ($row['notes'] ?? '')),
        ];

        $this->view('assets/views/form', [
            'title' => $title,
            'row' => $row,
            'formData' => $formData,
            'categories' => $categories,
            'units' => business_unit_options(true),
            'journals' => $journals,
            'coaOptions' => $coaOptions,
            'entryModes' => asset_entry_modes(),
            'methods' => asset_depreciation_methods(),
            'conditions' => asset_conditions(),
            'statuses' => asset_statuses(),
            'groups' => asset_groups(),
            'fundingSources' => asset_funding_sources(),
        ]);
    }

    private function showCategoryForm(string $title, ?array $row): void
    {
        $coaOptions = $this->model()->getCoaOptions();
        $formData = [
            'category_code' => old('category_code', (string) ($row['category_code'] ?? '')),
            'category_name' => old('category_name', (string) ($row['category_name'] ?? '')),
            'asset_group' => old('asset_group', (string) ($row['asset_group'] ?? 'FIXED')),
            'default_useful_life_months' => old('default_useful_life_months', isset($row['default_useful_life_months']) && $row['default_useful_life_months'] !== null ? (string) $row['default_useful_life_months'] : ''),
            'default_depreciation_method' => old('default_depreciation_method', (string) ($row['default_depreciation_method'] ?? 'STRAIGHT_LINE')),
            'depreciation_allowed' => old('depreciation_allowed', isset($row['depreciation_allowed']) ? ((int) $row['depreciation_allowed'] === 1 ? '1' : '0') : '1'),
            'asset_coa_id' => old('asset_coa_id', isset($row['asset_coa_id']) && $row['asset_coa_id'] !== null ? (string) $row['asset_coa_id'] : ''),
            'accumulated_depreciation_coa_id' => old('accumulated_depreciation_coa_id', isset($row['accumulated_depreciation_coa_id']) && $row['accumulated_depreciation_coa_id'] !== null ? (string) $row['accumulated_depreciation_coa_id'] : ''),
            'depreciation_expense_coa_id' => old('depreciation_expense_coa_id', isset($row['depreciation_expense_coa_id']) && $row['depreciation_expense_coa_id'] !== null ? (string) $row['depreciation_expense_coa_id'] : ''),
            'disposal_gain_coa_id' => old('disposal_gain_coa_id', isset($row['disposal_gain_coa_id']) && $row['disposal_gain_coa_id'] !== null ? (string) $row['disposal_gain_coa_id'] : ''),
            'disposal_loss_coa_id' => old('disposal_loss_coa_id', isset($row['disposal_loss_coa_id']) && $row['disposal_loss_coa_id'] !== null ? (string) $row['disposal_loss_coa_id'] : ''),
            'description' => old('description', (string) ($row['description'] ?? '')),
            'is_active' => old('is_active', isset($row['is_active']) ? ((int) $row['is_active'] === 1 ? '1' : '0') : '1'),
        ];

        $this->view('assets/views/category_form', [
            'title' => $title,
            'row' => $row,
            'formData' => $formData,
            'groups' => asset_groups(),
            'coaOptions' => $coaOptions,
            'methods' => asset_depreciation_methods(),
        ]);
    }

    private function assetInput(): array
    {
        return [
            'asset_code' => strtoupper(trim((string) post('asset_code'))),
            'asset_name' => trim((string) post('asset_name')),
            'entry_mode' => strtoupper(trim((string) post('entry_mode', 'ACQUISITION'))),
            'category_id' => (int) post('category_id', 0),
            'subcategory_name' => trim((string) post('subcategory_name')),
            'business_unit_id' => post('business_unit_id') !== null && post('business_unit_id') !== '' ? (int) post('business_unit_id') : null,
            'quantity' => trim((string) post('quantity', '1')),
            'unit_name' => trim((string) post('unit_name', 'unit')),
            'acquisition_date' => trim((string) post('acquisition_date')),
            'acquisition_cost' => trim((string) post('acquisition_cost')),
            'opening_as_of_date' => trim((string) post('opening_as_of_date')),
            'opening_accumulated_depreciation' => trim((string) post('opening_accumulated_depreciation', '0')),
            'residual_value' => trim((string) post('residual_value')),
            'useful_life_months' => post('useful_life_months') !== null && post('useful_life_months') !== '' ? (int) post('useful_life_months') : null,
            'depreciation_method' => trim((string) post('depreciation_method', 'STRAIGHT_LINE')),
            'depreciation_start_date' => trim((string) post('depreciation_start_date')),
            'depreciation_allowed' => (string) post('depreciation_allowed', '1') === '1',
            'offset_coa_id' => post('offset_coa_id') !== null && post('offset_coa_id') !== '' ? (int) post('offset_coa_id') : null,
            'location' => trim((string) post('location')),
            'supplier_name' => trim((string) post('supplier_name')),
            'source_of_funds' => trim((string) post('source_of_funds', 'HASIL_USAHA')),
            'funding_source_detail' => trim((string) post('funding_source_detail')),
            'reference_no' => trim((string) post('reference_no')),
            'linked_journal_id' => post('linked_journal_id') !== null && post('linked_journal_id') !== '' ? (int) post('linked_journal_id') : null,
            'condition_status' => trim((string) post('condition_status', 'GOOD')),
            'asset_status' => trim((string) post('asset_status', 'ACTIVE')),
            'is_active' => (string) post('is_active', '1') === '1',
            'description' => trim((string) post('description')),
            'notes' => trim((string) post('notes')),
        ];
    }

    private function assetOldInput(array $input): array
    {
        return [
            'asset_code' => $input['asset_code'],
            'asset_name' => $input['asset_name'],
            'category_id' => (string) $input['category_id'],
            'subcategory_name' => $input['subcategory_name'],
            'business_unit_id' => $input['business_unit_id'] === null ? '' : (string) $input['business_unit_id'],
            'quantity' => (string) $input['quantity'],
            'unit_name' => $input['unit_name'],
            'acquisition_date' => $input['acquisition_date'],
            'acquisition_cost' => $input['acquisition_cost'],
            'residual_value' => $input['residual_value'],
            'useful_life_months' => $input['useful_life_months'] === null ? '' : (string) $input['useful_life_months'],
            'depreciation_method' => $input['depreciation_method'],
            'depreciation_start_date' => $input['depreciation_start_date'],
            'depreciation_allowed' => $input['depreciation_allowed'] ? '1' : '0',
            'location' => $input['location'],
            'supplier_name' => $input['supplier_name'],
            'source_of_funds' => $input['source_of_funds'],
            'funding_source_detail' => $input['funding_source_detail'],
            'reference_no' => $input['reference_no'],
            'linked_journal_id' => $input['linked_journal_id'] === null ? '' : (string) $input['linked_journal_id'],
            'condition_status' => $input['condition_status'],
            'asset_status' => $input['asset_status'],
            'is_active' => $input['is_active'] ? '1' : '0',
            'description' => $input['description'],
            'notes' => $input['notes'],
        ];
    }

    private function validateAssetInput(array &$input, ?int $currentId = null): array
    {
        $errors = [];

        if ($input['asset_code'] === '') {
            $errors[] = 'Kode aset wajib diisi.';
        } elseif (!preg_match('/^[A-Z0-9._\/-]{2,40}$/', $input['asset_code'])) {
            $errors[] = 'Kode aset hanya boleh huruf besar, angka, titik, garis bawah, slash, atau tanda hubung.';
        } elseif ($this->model()->findAssetByCode($input['asset_code'], $currentId)) {
            $errors[] = 'Kode aset sudah digunakan.';
        }

        if ($input['asset_name'] === '') {
            $errors[] = 'Nama aset wajib diisi.';
        } elseif (mb_strlen($input['asset_name']) < 3 || mb_strlen($input['asset_name']) > 160) {
            $errors[] = 'Nama aset harus 3 sampai 160 karakter.';
        }

        $category = $this->model()->findCategoryById((int) $input['category_id']);
        if (!$category) {
            $errors[] = 'Kategori aset tidak ditemukan.';
        }

        if (!isset(asset_entry_modes()[$input['entry_mode']])) {
            $errors[] = 'Mode pencatatan aset tidak valid.';
        }
        if (!$this->isValidDate($input['acquisition_date'])) {
            $errors[] = 'Tanggal perolehan tidak valid.';
        }
        if ($input['entry_mode'] === 'OPENING' && !$this->isValidDate($input['opening_as_of_date'])) {
            $errors[] = 'Tanggal saldo awal aset tidak valid.';
        }

        if ($input['depreciation_start_date'] !== '' && !$this->isValidDate($input['depreciation_start_date'])) {
            $errors[] = 'Tanggal mulai penyusutan tidak valid.';
        }

        $input['quantity'] = $this->normalizeDecimal((string) $input['quantity']);
        if ($input['quantity'] <= 0) {
            $errors[] = 'Qty aset harus lebih besar dari 0.';
        }
        if ($input['quantity'] > 999999) {
            $errors[] = 'Qty aset terlalu besar.';
        }
        $input['unit_name'] = trim((string) $input['unit_name']) !== '' ? trim((string) $input['unit_name']) : 'unit';
        if (mb_strlen($input['unit_name']) > 30) {
            $errors[] = 'Satuan aset maksimal 30 karakter.';
        }

        $input['acquisition_cost'] = $this->normalizeDecimal($input['acquisition_cost']);
        $input['opening_accumulated_depreciation'] = $this->normalizeDecimal($input['opening_accumulated_depreciation']);
        $input['residual_value'] = $this->normalizeDecimal($input['residual_value']);
        if ($input['acquisition_cost'] < 0) {
            $errors[] = 'Nilai perolehan tidak boleh negatif.';
        }
        if ($input['opening_accumulated_depreciation'] < 0) {
            $errors[] = 'Akumulasi penyusutan awal tidak boleh negatif.';
        }
        if ($input['residual_value'] < 0) {
            $errors[] = 'Nilai residu tidak boleh negatif.';
        }
        if ($input['residual_value'] > $input['acquisition_cost']) {
            $errors[] = 'Nilai residu tidak boleh lebih besar dari nilai perolehan.';
        }

        if (!isset(asset_depreciation_methods()[$input['depreciation_method']])) {
            $errors[] = 'Metode penyusutan tidak valid.';
        }
        if (!isset(asset_conditions()[$input['condition_status']])) {
            $errors[] = 'Kondisi aset tidak valid.';
        }
        if (!isset(asset_statuses()[$input['asset_status']])) {
            $errors[] = 'Status aset tidak valid.';
        }

        if ($input['business_unit_id'] !== null && !find_business_unit((int) $input['business_unit_id'])) {
            $errors[] = 'Unit usaha yang dipilih tidak ditemukan.';
        }

        if ($input['offset_coa_id'] !== null && $input['offset_coa_id'] <= 0) {
            $input['offset_coa_id'] = null;
        }
        if ($input['linked_journal_id'] !== null && $input['linked_journal_id'] <= 0) {
            $input['linked_journal_id'] = null;
        }
        if ($input['linked_journal_id'] !== null && !$this->model()->journalExists((int) $input['linked_journal_id'])) {
            $errors[] = 'Jurnal referensi tidak ditemukan.';
        }

        if ($category) {
            if ((int) ($category['depreciation_allowed'] ?? 1) !== 1) {
                $input['depreciation_allowed'] = false;
            }
            if ($input['useful_life_months'] === null && (int) ($category['default_useful_life_months'] ?? 0) > 0) {
                $input['useful_life_months'] = (int) $category['default_useful_life_months'];
            }
        }

        if ($input['depreciation_allowed']) {
            if ($input['useful_life_months'] === null || (int) $input['useful_life_months'] <= 0) {
                $errors[] = 'Umur manfaat wajib diisi untuk aset yang disusutkan.';
            }
            if ($input['depreciation_start_date'] === '') {
                $input['depreciation_start_date'] = $input['acquisition_date'];
            }
        } else {
            $input['useful_life_months'] = null;
            $input['depreciation_start_date'] = null;
        }

        if (mb_strlen($input['location']) > 150) {
            $errors[] = 'Lokasi aset maksimal 150 karakter.';
        }
        if (mb_strlen($input['supplier_name']) > 150) {
            $errors[] = 'Supplier / sumber perolehan maksimal 150 karakter.';
        }
        if (!isset(asset_funding_sources()[$input['source_of_funds']])) {
            $errors[] = 'Sumber dana tidak valid.';
        }
        if (mb_strlen($input['funding_source_detail']) > 150) {
            $errors[] = 'Detail sumber dana maksimal 150 karakter.';
        }
        if (mb_strlen($input['reference_no']) > 100) {
            $errors[] = 'Nomor referensi maksimal 100 karakter.';
        }
        if (mb_strlen($input['description']) > 1000) {
            $errors[] = 'Deskripsi aset maksimal 1000 karakter.';
        }
        if (mb_strlen($input['notes']) > 1000) {
            $errors[] = 'Catatan aset maksimal 1000 karakter.';
        }

        return $errors;
    }

    private function validateCategoryInput(array $input, ?int $currentId = null): array
    {
        $errors = [];
        if ($input['category_code'] === '') {
            $errors[] = 'Kode kategori wajib diisi.';
        } elseif (!preg_match('/^[A-Z0-9._\/-]{2,30}$/', $input['category_code'])) {
            $errors[] = 'Kode kategori hanya boleh huruf besar, angka, titik, slash, garis bawah, atau tanda hubung.';
        } elseif ($this->model()->findCategoryByCode($input['category_code'], $currentId)) {
            $errors[] = 'Kode kategori sudah digunakan.';
        }
        if ($input['category_name'] === '') {
            $errors[] = 'Nama kategori wajib diisi.';
        }
        if (!isset(asset_groups()[$input['asset_group']])) {
            $errors[] = 'Kelompok kategori tidak valid.';
        }
        if (!isset(asset_depreciation_methods()[$input['default_depreciation_method']])) {
            $errors[] = 'Metode penyusutan default tidak valid.';
        }
        if ($input['default_useful_life_months'] !== null && (int) $input['default_useful_life_months'] < 0) {
            $errors[] = 'Umur manfaat default tidak boleh negatif.';
        }
        foreach (['asset_coa_id','accumulated_depreciation_coa_id','depreciation_expense_coa_id','disposal_gain_coa_id','disposal_loss_coa_id'] as $field) {
            if (($input[$field] ?? null) !== null && !$this->model()->findCoaById((int) $input[$field])) {
                $errors[] = 'Mapping akun kategori tidak valid.';
                break;
            }
        }
        if (mb_strlen($input['description']) > 1000) {
            $errors[] = 'Deskripsi kategori maksimal 1000 karakter.';
        }
        return $errors;
    }

    private function validateMutation(array &$input, array $asset): array
    {
        $errors = [];
        if (!$this->isValidDate($input['mutation_date'])) {
            $errors[] = 'Tanggal mutasi tidak valid.';
        }
        if (!isset(asset_mutation_types()[$input['mutation_type']])) {
            $errors[] = 'Jenis mutasi tidak valid.';
        }
        if (in_array($input['mutation_type'], ['ACQUISITION', 'UPDATE'], true)) {
            $errors[] = 'Jenis mutasi tersebut dicatat otomatis oleh sistem.';
        }
        $input['amount'] = $input['amount'] !== '' ? $this->normalizeDecimal($input['amount']) : null;
        if ($input['amount'] !== null && $input['amount'] < 0) {
            $errors[] = 'Nominal mutasi tidak boleh negatif.';
        }
        if ($input['to_business_unit_id'] !== null && !find_business_unit((int) $input['to_business_unit_id'])) {
            $errors[] = 'Unit usaha tujuan tidak ditemukan.';
        }
        if ($input['new_status'] !== '' && !isset(asset_statuses()[$input['new_status']])) {
            $errors[] = 'Status baru tidak valid.';
        }
        if ($input['linked_journal_id'] !== null && !$this->model()->journalExists((int) $input['linked_journal_id'])) {
            $errors[] = 'Jurnal referensi mutasi tidak ditemukan.';
        }
        if (mb_strlen($input['reference_no']) > 100) {
            $errors[] = 'Nomor referensi mutasi maksimal 100 karakter.';
        }
        if (mb_strlen($input['notes']) > 1000) {
            $errors[] = 'Catatan mutasi maksimal 1000 karakter.';
        }

        switch ($input['mutation_type']) {
            case 'TRANSFER_UNIT':
                if ($input['to_business_unit_id'] === null) {
                    $errors[] = 'Pilih unit usaha tujuan.';
                }
                break;
            case 'TRANSFER_LOCATION':
                if ($input['to_location'] === '') {
                    $errors[] = 'Lokasi tujuan wajib diisi.';
                }
                break;
            case 'STATUS_CHANGE':
            case 'MAINTENANCE':
                if ($input['new_status'] === '') {
                    $errors[] = 'Status baru wajib dipilih.';
                }
                break;
            case 'SELL':
                if ($input['amount'] === null) {
                    $errors[] = 'Nilai penjualan wajib diisi.';
                }
                break;
        }

        if (in_array((string) ($asset['asset_status'] ?? ''), ['SOLD', 'DAMAGED', 'DISPOSED'], true)
            && in_array($input['mutation_type'], ['SELL', 'DAMAGE', 'DISPOSE'], true)) {
            $errors[] = 'Aset sudah berstatus selesai / dilepas, tidak dapat dimutasi kembali dengan status akhir yang sama.';
        }

        return $errors;
    }


    private function readImportRows(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            $rows = [];
            $handle = fopen($filePath, 'rb');
            if ($handle === false) {
                throw new RuntimeException('File CSV tidak dapat dibuka.');
            }
            while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($data === [null] || $data === false) {
                    continue;
                }
                $data = array_map(static fn($value): string => trim((string) $value), $data);
                if ($rows === [] && isset($data[0])) {
                    $data[0] = preg_replace('/^(?:ï»¿|ï»¿)/u', '', $data[0]) ?? $data[0];
                }
                $rows[] = $data;
            }
            fclose($handle);
            return $rows;
        }

        $reader = new XlsxReader();
        return $reader->readFirstSheet($filePath);
    }

    private function processImportRows(array $rows): array
    {
        if ($rows === []) {
            throw new RuntimeException('File import tidak berisi data.');
        }

        $header = array_map(static fn($value): string => strtolower(trim((string) $value)), array_shift($rows));
        $requiredHeaders = ['asset_code', 'asset_name', 'category_code', 'acquisition_date', 'acquisition_cost'];
        foreach ($requiredHeaders as $requiredHeader) {
            if (!in_array($requiredHeader, $header, true)) {
                throw new RuntimeException('Header wajib tidak ditemukan: ' . $requiredHeader . '. Gunakan template import aset terbaru.');
            }
        }

        $indexes = [];
        foreach ($header as $idx => $name) {
            if ($name !== '') {
                $indexes[$name] = $idx;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $lineIndex => $row) {
            $rowNumber = $lineIndex + 2;
            $normalized = [];
            foreach ($indexes as $name => $idx) {
                $normalized[$name] = trim((string) ($row[$idx] ?? ''));
            }

            if ($this->isImportRowEmpty($normalized)) {
                $skipped++;
                continue;
            }

            try {
                $payload = $this->mapImportRowToAssetInput($normalized);
                $existing = $this->model()->findAssetByCode($payload['asset_code']);
                $validationErrors = $this->validateAssetInput($payload, $existing ? (int) $existing['id'] : null);
                if ($validationErrors !== []) {
                    throw new RuntimeException(implode(' ', $validationErrors));
                }
                if ($existing) {
                    $this->model()->updateAsset((int) $existing['id'], $payload, $this->userId());
                    $updated++;
                } else {
                    $this->model()->createAsset($payload, $this->userId());
                    $created++;
                }
            } catch (Throwable $e) {
                $errors[] = 'Baris ' . $rowNumber . ': ' . $e->getMessage();
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 30),
        ];
    }

    private function isImportRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function mapImportRowToAssetInput(array $row): array
    {
        $categoryCode = strtoupper(trim((string) ($row['category_code'] ?? '')));
        $category = $this->model()->findCategoryByCode($categoryCode);
        if (!$category) {
            throw new RuntimeException('Kategori dengan kode ' . $categoryCode . ' tidak ditemukan.');
        }

        $unitCode = strtoupper(trim((string) ($row['business_unit_code'] ?? '')));
        $businessUnitId = null;
        if ($unitCode !== '') {
            $unit = $this->model()->findBusinessUnitByCode($unitCode);
            if (!$unit) {
                throw new RuntimeException('Unit usaha dengan kode ' . $unitCode . ' tidak ditemukan.');
            }
            $businessUnitId = (int) $unit['id'];
        }

        $sourceOfFunds = strtoupper(trim((string) ($row['source_of_funds'] ?? 'HASIL_USAHA')));
        if ($sourceOfFunds === '') {
            $sourceOfFunds = 'HASIL_USAHA';
        }

        $entryMode = strtoupper(trim((string) ($row['entry_mode'] ?? 'ACQUISITION')));
        $offsetAccountId = null;
        $offsetAccountCode = trim((string) ($row['offset_account_code'] ?? ''));
        if ($offsetAccountCode !== '') {
            $offset = $this->model()->findCoaByCode($offsetAccountCode);
            if (!$offset) {
                throw new RuntimeException('Akun lawan dengan kode ' . $offsetAccountCode . ' tidak ditemukan.');
            }
            $offsetAccountId = (int) $offset['id'];
        }
        return [
            'asset_code' => strtoupper(trim((string) ($row['asset_code'] ?? ''))),
            'asset_name' => trim((string) ($row['asset_name'] ?? '')),
            'entry_mode' => $entryMode !== '' ? $entryMode : 'ACQUISITION',
            'category_id' => (int) $category['id'],
            'subcategory_name' => trim((string) ($row['subcategory_name'] ?? '')),
            'business_unit_id' => $businessUnitId,
            'quantity' => trim((string) ($row['quantity'] ?? '1')) !== '' ? trim((string) ($row['quantity'] ?? '1')) : '1',
            'unit_name' => trim((string) ($row['unit_name'] ?? 'unit')) !== '' ? trim((string) ($row['unit_name'] ?? 'unit')) : 'unit',
            'acquisition_date' => $this->normalizeImportDateValue((string) ($row['acquisition_date'] ?? '')),
            'acquisition_cost' => trim((string) ($row['acquisition_cost'] ?? '0')),
            'opening_as_of_date' => $this->normalizeImportDateValue((string) ($row['opening_as_of_date'] ?? '')),
            'opening_accumulated_depreciation' => trim((string) ($row['opening_accumulated_depreciation'] ?? '0')),
            'residual_value' => trim((string) ($row['residual_value'] ?? '0')),
            'useful_life_months' => trim((string) ($row['useful_life_months'] ?? '')) !== '' ? (int) $row['useful_life_months'] : null,
            'depreciation_method' => trim((string) ($row['depreciation_method'] ?? 'STRAIGHT_LINE')) !== '' ? strtoupper(trim((string) $row['depreciation_method'])) : 'STRAIGHT_LINE',
            'depreciation_start_date' => $this->normalizeImportDateValue((string) ($row['depreciation_start_date'] ?? '')),
            'depreciation_allowed' => in_array(strtolower(trim((string) ($row['depreciation_allowed'] ?? '1'))), ['1','ya','yes','true'], true),
            'offset_coa_id' => $offsetAccountId,
            'location' => trim((string) ($row['location'] ?? '')),
            'supplier_name' => trim((string) ($row['supplier_name'] ?? '')),
            'source_of_funds' => $sourceOfFunds,
            'funding_source_detail' => trim((string) ($row['funding_source_detail'] ?? '')),
            'reference_no' => trim((string) ($row['reference_no'] ?? '')),
            'linked_journal_id' => null,
            'condition_status' => trim((string) ($row['condition_status'] ?? 'GOOD')) !== '' ? strtoupper(trim((string) $row['condition_status'])) : 'GOOD',
            'asset_status' => trim((string) ($row['asset_status'] ?? 'ACTIVE')) !== '' ? strtoupper(trim((string) $row['asset_status'])) : 'ACTIVE',
            'acquisition_sync_status' => $entryMode === 'ACQUISITION' ? 'READY' : 'NONE',
            'is_active' => true,
            'description' => trim((string) ($row['description'] ?? '')),
            'notes' => trim((string) ($row['notes'] ?? '')),
        ];
    }

    private function assetFilters(): array
    {
        return [
            'search' => trim((string) get_query('search', '')),
            'unit_id' => (int) get_query('unit_id', 0),
            'category_id' => (int) get_query('category_id', 0),
            'group' => trim((string) get_query('group', '')),
            'funding_source' => trim((string) get_query('funding_source', '')),
            'status' => trim((string) get_query('status', '')),
            'condition' => trim((string) get_query('condition', '')),
            'active' => trim((string) get_query('active', '')),
            'date_from' => trim((string) get_query('date_from', '')),
            'date_to' => trim((string) get_query('date_to', '')),
            'as_of_date' => trim((string) get_query('as_of_date', date('Y-m-d'))),
            'comparison_date' => trim((string) get_query('comparison_date', asset_comparison_date((string) get_query('as_of_date', date('Y-m-d'))))),
        ];
    }

    private function reportFilters(): array
    {
        return [
            'unit_id' => (int) get_query('unit_id', 0),
            'category_id' => (int) get_query('category_id', 0),
            'group' => trim((string) get_query('group', '')),
            'funding_source' => trim((string) get_query('funding_source', '')),
            'status' => trim((string) get_query('status', '')),
            'date_from' => trim((string) get_query('date_from', '')),
            'date_to' => trim((string) get_query('date_to', '')),
            'unit_id' => (int) get_query('unit_id', 0),
        ];
    }

    private function templateInstructionRows(): array
    {
        return [
            ['bagian', 'petunjuk'],
            ['Tujuan template', 'Gunakan sheet TEMPLATE_IMPORT untuk input massal aset opening atau aset baru tanpa perlu mengetik satu per satu di form. Header ada di baris pertama dan baris berikutnya sengaja dikosongkan agar aman untuk import.'],
            ['Format file', 'Simpan dan upload dalam format .xlsx. CSV masih diterima, tetapi .xlsx lebih aman untuk tanggal, qty, satuan, kode, dan catatan panjang. Jangan menambah kolom baru atau mengubah nama header.'],
            ['Kolom qty & satuan', 'Isi quantity dengan jumlah barang dalam satu register aset. Isi unit_name seperti unit, pcs, roll, set, meter, atau paket agar laporan aset BUMDes lebih mudah diaudit. Harga per unit dihitung otomatis dari total nilai perolehan dibagi qty.'],
            ['Tanggal wajib', 'Kolom acquisition_date, opening_as_of_date, dan depreciation_start_date harus memakai format YYYY-MM-DD, misalnya 2026-01-01.'],
            ['Entry mode', 'OPENING dipakai untuk saldo awal saat migrasi. ACQUISITION dipakai untuk aset baru yang dibeli setelah sistem berjalan.'],
            ['Category code', 'Gunakan kode kategori persis seperti sheet REFERENSI_KODE. Untuk aset WIFI/ISP biasanya paling cocok NETWORK, IT, EQUIPMENT, atau MACHINE tergantung sifat aset.'],
            ['Business unit code', 'Boleh dikosongkan bila aset tidak terikat unit usaha tertentu. Jika diisi, gunakan kode unit persis seperti sheet REFERENSI_KODE.'],
            ['Offset account code', 'Isi hanya bila aset baru perlu akun lawan perolehan. Untuk OPENING boleh dikosongkan. Kolom ini penting untuk menyiapkan sinkronisasi jurnal perolehan aset ke depan.'],
            ['Penyusutan', 'Jika depreciation_allowed = 1, isi useful_life_months dan depreciation_start_date. Jika tidak disusutkan, isi 0 lalu kosongkan umur manfaat.'],
            ['Update data lama', 'Jika asset_code sudah ada, import akan memperbarui aset tersebut. Jika belum ada, sistem akan membuat aset baru. Gunakan kode aset yang stabil agar mutasi, kartu aset, dan rencana sinkronisasi jurnal tetap rapi.'],
            ['Sinkron jurnal', 'Menu aset sekarang menyiapkan qty, satuan, akun lawan, mode entry, dan link jurnal agar sinkronisasi perolehan aset dari jurnal lebih mudah di tahap berikutnya. Untuk sinkron penuh otomatis dari menu jurnal masih perlu patch lanjutan di modul jurnal.'],
            ['Hapus aset', 'Aset dapat dihapus dari menu aset hanya jika belum tertaut jurnal, belum punya penyusutan terposting, dan belum punya mutasi berjurnal.'],
        ];
    }

    private function templateReferenceRows(array $categories, array $units, array $coas): array
    {
        $rows = [['kelompok', 'kode', 'label', 'catatan']];
        foreach (asset_entry_modes() as $code => $label) {
            $rows[] = ['entry_mode', (string) $code, (string) $label, $code === 'OPENING' ? 'Dipakai untuk saldo awal migrasi aset.' : 'Dipakai untuk aset baru setelah sistem berjalan.'];
        }
        foreach ($categories as $category) {
            $rows[] = ['category_code', (string) ($category['category_code'] ?? ''), (string) ($category['category_name'] ?? ''), 'Kelompok: ' . asset_group_label((string) ($category['asset_group'] ?? 'FIXED'))];
        }
        foreach ($units as $unit) {
            $rows[] = ['business_unit_code', (string) ($unit['unit_code'] ?? ''), (string) ($unit['unit_name'] ?? ''), 'Kosongkan jika aset tidak khusus unit usaha tertentu.'];
        }
        foreach (asset_funding_sources() as $code => $label) {
            $rows[] = ['source_of_funds', (string) $code, (string) $label, 'Sumber dana aset.'];
        }
        foreach (asset_depreciation_methods() as $code => $label) {
            $rows[] = ['depreciation_method', (string) $code, (string) $label, 'Gunakan sesuai kebijakan penyusutan.'];
        }
        foreach (asset_conditions() as $code => $label) {
            $rows[] = ['condition_status', (string) $code, (string) $label, 'Kondisi fisik aset.'];
        }
        foreach (asset_statuses() as $code => $label) {
            $rows[] = ['asset_status', (string) $code, (string) $label, 'Status master aset.'];
        }
        foreach ($coas as $coa) {
            $rows[] = ['offset_account_code', (string) ($coa['account_code'] ?? ''), (string) ($coa['account_name'] ?? ''), 'Opsional. Dipakai terutama untuk ACQUISITION bila ingin akun lawan perolehan terisi.'];
        }
        return $rows;
    }

    private function exportFilterSummaryRows(array $filters): array
    {
        $rows = [];
        foreach ($filters as $key => $value) {
            if ($value === '' || $value === null || (string) $value === '0') {
                continue;
            }
            $rows[] = ['filter_' . $key, is_scalar($value) ? (string) $value : json_encode($value)];
        }
        if ($rows === []) {
            $rows[] = ['filter_status', 'Tidak ada filter tambahan.'];
        }
        return $rows;
    }

    private function normalizeImportDateValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            if ($serial > 0) {
                $base = new DateTimeImmutable('1899-12-30');
                return $base->modify('+' . (int) floor($serial) . ' days')->format('Y-m-d');
            }
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y/m/d', 'Y.m.d', 'd.m.Y'] as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            if ($dt instanceof DateTimeImmutable && $dt->format($format) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        return $value;
    }

    private function buildSummary(array $rows): array
    {
        $summary = [
            'asset_count' => 0,
            'active_count' => 0,
            'total_quantity' => 0.0,
            'total_cost' => 0.0,
            'total_accumulated_depreciation' => 0.0,
            'total_book_value' => 0.0,
        ];
        foreach ($rows as $row) {
            $summary['asset_count']++;
            if ((int) $row['is_active'] === 1) {
                $summary['active_count']++;
            }
            $summary['total_quantity'] += (float) ($row['quantity'] ?? 1);
            $summary['total_cost'] += (float) $row['acquisition_cost'];
            $summary['total_accumulated_depreciation'] += (float) ($row['current_accumulated_depreciation'] ?? 0);
            $summary['total_book_value'] += (float) (($row['current_book_value'] ?? $row['acquisition_cost']) ?: 0);
        }
        return $summary;
    }

    private function normalizeDecimal(string $value): float
    {
        $value = str_replace(['Rp', 'rp', ' '], '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return round((float) $value, 2);
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTimeImmutable && $dt->format('Y-m-d') === $date;
    }
}
