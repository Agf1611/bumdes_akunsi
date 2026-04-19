<?php

declare(strict_types=1);

final class JournalController extends Controller
{
    private function model(): JournalModel
    {
        return new JournalModel(Database::getInstance(db_config()));
    }

    private function attachmentService(): JournalAttachmentService
    {
        return new JournalAttachmentService();
    }

    public function index(): void
    {
        try {
            $filters = $this->readFilters();
            $this->view('journals/views/index', [
                'title' => 'Jurnal Umum',
                'filters' => $filters,
                'journals' => $this->model()->getList($filters),
                'periods' => $this->model()->getOpenPeriods(),
                'unitOptions' => business_unit_options(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Daftar jurnal belum dapat dibuka. Pastikan tabel jurnal sudah dibuat dengan benar.', $e);
        }
    }

    public function create(): void
    {
        $duplicateId = (int) get_query('duplicate_id', 0);
        if ($duplicateId > 0) {
            $this->createFromDuplicate($duplicateId);
            return;
        }

        $templateKey = trim((string) get_query('template', ''));
        $quickTemplate = $templateKey !== '' ? journal_quick_template_data($templateKey) : null;
        $this->showForm('Tambah Jurnal Umum', null, [], $quickTemplate, null);
    }

    private function createFromDuplicate(int $duplicateId): void
    {
        try {
            $header = $this->model()->findHeaderById($duplicateId);
            if (!$header) {
                flash('error', 'Jurnal sumber untuk duplikasi tidak ditemukan.');
                $this->redirect('/journals');
            }

            $details = $this->model()->getDetailsByJournalId($duplicateId);
            $quickTemplate = [
                'template_key' => 'duplicate',
                'template_name' => 'Duplikat Jurnal ' . (string) ($header['journal_no'] ?? ('#' . $duplicateId)),
                'journal_date' => date('Y-m-d'),
                'description' => (string) ($header['description'] ?? ''),
                'business_unit_id' => (string) ($header['business_unit_id'] ?? ''),
                'print_template' => (string) ($header['print_template'] ?? 'standard'),
                'source_journal_id' => $duplicateId,
                'source_journal_no' => (string) ($header['journal_no'] ?? ''),
                'receipt' => [
                    'party_title' => (string) ($header['party_title'] ?? 'Dibayar kepada'),
                    'party_name' => (string) ($header['party_name'] ?? ''),
                    'purpose' => (string) ($header['purpose'] ?? ''),
                    'amount_in_words' => (string) ($header['amount_in_words'] ?? ''),
                    'payment_method' => (string) ($header['payment_method'] ?? ''),
                    'reference_no' => (string) ($header['reference_no'] ?? ''),
                    'notes' => (string) ($header['notes'] ?? ''),
                ],
                'detail_rows' => array_map(function (array $detail): array {
                    return [
                        'coa_id' => (string) ($detail['coa_id'] ?? ''),
                        'line_description' => (string) ($detail['line_description'] ?? ''),
                        'debit_raw' => $this->formatMoneyForInput($detail['debit'] ?? 0),
                        'credit_raw' => $this->formatMoneyForInput($detail['credit'] ?? 0),
                    ];
                }, $details),
            ];

            $activePeriod = current_accounting_period();
            if (is_array($activePeriod) && (int) ($activePeriod['id'] ?? 0) > 0) {
                $quickTemplate['period_id'] = (string) $activePeriod['id'];
            } else {
                $quickTemplate['period_id'] = (string) ($header['period_id'] ?? '');
            }

            flash('success', 'Form sudah diisi dari jurnal ' . (string) ($header['journal_no'] ?? ('#' . $duplicateId)) . '. Periksa tanggal, periode, dan nominal sebelum disimpan sebagai jurnal baru.');
            $this->showForm('Duplikat Jurnal Umum', null, [], $quickTemplate, null);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Duplikasi jurnal belum dapat dibuka.');
            $this->redirect('/journals');
        }
    }

    public function edit(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID jurnal tidak valid.');
            $this->redirect('/journals');
        }

        try {
            $header = $this->model()->findHeaderById($id);
            if (!$header) {
                flash('error', 'Jurnal tidak ditemukan.');
                $this->redirect('/journals');
            }
            $this->ensurePeriodOpenForModification($header);
            $details = $this->model()->getDetailsByJournalId($id);
            $this->showForm('Edit Jurnal Umum', $header, $details, null, null);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Form edit jurnal belum dapat dibuka. Pastikan periode jurnal masih terbuka.');
            $this->redirect('/journals');
        }
    }

    public function detail(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID jurnal tidak valid.');
            $this->redirect('/journals');
        }

        try {
            $header = $this->model()->findHeaderById($id);
            if (!$header) {
                flash('error', 'Jurnal tidak ditemukan.');
                $this->redirect('/journals');
            }

            $this->view('journals/views/detail', [
                'title' => 'Detail Jurnal',
                'header' => $header,
                'details' => $this->model()->getDetailsByJournalId($id),
                'attachments' => $this->model()->getAttachmentsByJournalId($id),
                'attachmentFeatureStatus' => $this->model()->getAttachmentFeatureStatus(),
                'selectedUnitLabel' => business_unit_label(!empty($header['business_unit_id']) ? $header : null, false),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Detail jurnal belum dapat dibuka.', $e);
        }
    }

    public function print(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID jurnal tidak valid.');
            $this->redirect('/journals');
        }

        try {
            $header = $this->model()->findHeaderById($id);
            if (!$header) {
                flash('error', 'Jurnal tidak ditemukan.');
                $this->redirect('/journals');
            }

            $this->view('journals/views/print', [
                'title' => 'Cetak Jurnal',
                'header' => $header,
                'details' => $this->model()->getDetailsByJournalId($id),
                'profile' => app_profile(),
                'reportTitle' => 'Jurnal Umum',
                'periodLabel' => (string) ($header['period_name'] ?? '-'),
                'attachments' => $this->model()->getAttachmentsByJournalId($id),
                'selectedUnitLabel' => business_unit_label(!empty($header['business_unit_id']) ? $header : null),
            ], 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak jurnal belum dapat dibuka.', $e);
        }
    }

    public function printReceipt(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID jurnal tidak valid.');
            $this->redirect('/journals');
        }

        try {
            $header = $this->model()->findHeaderById($id);
            if (!$header) {
                flash('error', 'Jurnal tidak ditemukan.');
                $this->redirect('/journals');
            }
            if (!journal_is_receipt_enabled($header)) {
                flash('error', 'Jurnal ini tidak disiapkan untuk dicetak sebagai bukti transaksi / kwitansi.');
                $this->redirect('/journals/detail?id=' . $id);
            }

            $totalAmount = (float) ($header['total_debit'] ?? 0);
            $amountInWords = journal_normalize_amount_in_words((string) ($header['amount_in_words'] ?? ''), $totalAmount);

            $this->view('journals/views/print_receipt', [
                'title' => 'Cetak Bukti Transaksi',
                'header' => $header,
                'details' => $this->model()->getDetailsByJournalId($id),
                'profile' => app_profile(),
                'reportTitle' => 'Bukti Transaksi / Kwitansi',
                'periodLabel' => (string) ($header['period_name'] ?? '-'),
                'selectedUnitLabel' => business_unit_label(!empty($header['business_unit_id']) ? $header : null),
                'attachments' => $this->model()->getAttachmentsByJournalId($id),
                'amountInWords' => $amountInWords,
                'totalAmount' => $totalAmount,
            ], 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak bukti transaksi belum dapat dibuka.', $e);
        }
    }

    public function printList(): void
    {
        try {
            $filters = $this->readFilters();
            $selectedPeriod = !empty($filters['period_id']) ? $this->model()->findPeriodById((int) $filters['period_id']) : null;
            $journals = $this->model()->getPrintList($filters);

            $this->view('journals/views/print_list', [
                'title' => 'Cetak Daftar Jurnal',
                'filters' => $filters,
                'journals' => $journals,
                'profile' => app_profile(),
                'reportTitle' => 'Daftar Jurnal / Daftar Transaksi',
                'periodLabel' => report_period_label($filters, $selectedPeriod),
                'selectedUnitLabel' => report_selected_unit_label($filters),
            ], 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak daftar jurnal belum dapat dibuka.', $e);
        }
    }


    public function export(): void
    {
        try {
            $filters = $this->readFilters();
            $rows = $this->model()->getList($filters);

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="jurnal_export_' . date('Ymd_His') . '.csv"');

            $out = fopen('php://output', 'wb');
            if ($out === false) {
                throw new RuntimeException('Stream ekspor jurnal tidak dapat dibuka.');
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['journal_no', 'journal_date', 'period_code', 'unit_code', 'description', 'total_debit', 'total_credit', 'print_template', 'period_status'], ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($out, [
                    (string) ($row['journal_no'] ?? ''),
                    (string) ($row['journal_date'] ?? ''),
                    (string) ($row['period_code'] ?? ''),
                    (string) ($row['unit_code'] ?? ''),
                    (string) ($row['description'] ?? ''),
                    (string) ($row['total_debit'] ?? '0'),
                    (string) ($row['total_credit'] ?? '0'),
                    (string) ($row['print_template'] ?? 'standard'),
                    (string) ($row['period_status'] ?? ''),
                ], ',', '"', '');
            }
            fclose($out);
            exit;
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Export jurnal gagal diproses.');
            $this->redirect('/journals');
        }
    }

    public function uploadAttachment(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        $journalId = (int) get_query('id', 0);
        if ($journalId <= 0) {
            flash('error', 'ID jurnal tidak valid untuk upload lampiran.');
            $this->redirect('/journals');
        }

        $storedFile = null;
        try {
            $header = $this->model()->findHeaderById($journalId);
            if (!$header) {
                throw new RuntimeException('Jurnal tidak ditemukan.');
            }
            $this->ensurePeriodOpenForModification($header);
            $featureStatus = $this->model()->getAttachmentFeatureStatus();
            if (!(bool) ($featureStatus['enabled'] ?? false)) {
                throw new RuntimeException('Fitur lampiran jurnal belum aktif. Jalankan file database/patch_stage5_journal_attachments.sql terlebih dahulu.');
            }

            $attachmentTitle = trim((string) post('attachment_title', ''));
            $attachmentNotes = trim((string) post('attachment_notes', ''));
            if ($attachmentTitle !== '' && strlen($attachmentTitle) > 150) {
                throw new RuntimeException('Judul lampiran maksimal 150 karakter.');
            }
            if (strlen($attachmentNotes) > 255) {
                throw new RuntimeException('Catatan lampiran maksimal 255 karakter.');
            }

            $storedFile = $this->attachmentService()->storeUploadedFile($_FILES['attachment_file'] ?? []);
            $attachmentId = $this->model()->createAttachment([
                'journal_id' => $journalId,
                'attachment_title' => $attachmentTitle,
                'attachment_notes' => $attachmentNotes,
                'original_name' => $storedFile['original_name'],
                'stored_name' => $storedFile['stored_name'],
                'stored_file_path' => $storedFile['stored_file_path'],
                'mime_type' => $storedFile['mime_type'],
                'file_ext' => $storedFile['file_ext'],
                'file_size' => $storedFile['file_size'],
                'uploaded_by' => (int) (Auth::user()['id'] ?? 0),
            ]);

            audit_log('Lampiran Jurnal', 'upload', 'Lampiran bukti transaksi diunggah.', [
                'entity_type' => 'journal_attachment',
                'entity_id' => (string) $attachmentId,
                'context' => [
                    'journal_id' => $journalId,
                    'journal_no' => (string) ($header['journal_no'] ?? ''),
                    'title' => $attachmentTitle,
                    'original_name' => $storedFile['original_name'],
                    'file_size' => $storedFile['file_size'],
                    'mime_type' => $storedFile['mime_type'],
                ],
                'after' => $this->model()->findAttachmentById($attachmentId),
            ]);

            flash('success', 'Lampiran jurnal berhasil diunggah.');
        } catch (Throwable $e) {
            if (is_array($storedFile) && !empty($storedFile['stored_name'])) {
                JournalAttachmentService::deleteStoredFile((string) $storedFile['stored_name']);
            }
            log_error($e);
            flash('error', 'Lampiran jurnal gagal diunggah. ' . $e->getMessage());
        }

        $this->redirect('/journals/detail?id=' . $journalId);
    }

    public function downloadAttachment(): void
    {
        $attachmentId = (int) get_query('id', 0);
        if ($attachmentId <= 0) {
            http_response_code(404);
            render_error_page(404, 'Lampiran jurnal tidak ditemukan.');
            return;
        }

        $requestedMode = strtolower(trim((string) get_query('mode', 'download')));
        $inlineMode = in_array($requestedMode, ['preview', 'inline', 'view'], true);

        try {
            $attachment = $this->model()->findAttachmentById($attachmentId);
            if (!$attachment) {
                http_response_code(404);
                render_error_page(404, 'Lampiran jurnal tidak ditemukan.');
                return;
            }

            $path = JournalAttachmentService::filePath((string) ($attachment['stored_name'] ?? ''));
            if ($path === null) {
                throw new RuntimeException('File lampiran tidak ditemukan di server.');
            }

            $downloadName = JournalAttachmentService::downloadFileName($attachment);
            $fallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $downloadName) ?: ('lampiran-jurnal-' . $attachmentId);
            $contentType = JournalAttachmentService::contentType($attachment);
            $disposition = $inlineMode ? 'inline' : 'attachment';

            audit_log('Lampiran Jurnal', $inlineMode ? 'preview' : 'download', $inlineMode ? 'Lampiran bukti transaksi dipratinjau.' : 'Lampiran bukti transaksi diunduh.', [
                'entity_type' => 'journal_attachment',
                'entity_id' => (string) $attachmentId,
                'context' => [
                    'journal_id' => (int) ($attachment['journal_id'] ?? 0),
                    'journal_no' => (string) ($attachment['journal_no'] ?? ''),
                    'original_name' => (string) ($attachment['original_name'] ?? ''),
                    'file_size' => (int) ($attachment['file_size'] ?? 0),
                    'mode' => $inlineMode ? 'preview' : 'download',
                ],
            ]);

            header('Content-Type: ' . $contentType);
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, max-age=300');
            header('Content-Length: ' . (string) ((int) (@filesize($path) ?: 0)));
            header("Content-Disposition: {$disposition}; filename=\"{$fallbackName}\"; filename*=UTF-8''" . rawurlencode($downloadName));
            readfile($path);
            exit;
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, $inlineMode ? 'Lampiran jurnal belum dapat dipratinjau.' : 'Lampiran jurnal belum dapat diunduh.', $e);
        }
    }

    public function deleteAttachment(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        $attachmentId = (int) get_query('id', 0);
        if ($attachmentId <= 0) {
            flash('error', 'ID lampiran tidak valid.');
            $this->redirect('/journals');
        }

        $redirectJournalId = 0;
        try {
            $attachment = $this->model()->findAttachmentById($attachmentId);
            if (!$attachment) {
                throw new RuntimeException('Lampiran jurnal tidak ditemukan.');
            }
            $redirectJournalId = (int) ($attachment['journal_id'] ?? 0);
            $header = $this->model()->findHeaderById($redirectJournalId);
            if (!$header) {
                throw new RuntimeException('Jurnal terkait lampiran tidak ditemukan.');
            }
            $this->ensurePeriodOpenForModification($header);

            $this->model()->deleteAttachment($attachmentId);
            JournalAttachmentService::deleteStoredFile((string) ($attachment['stored_name'] ?? ''));

            audit_log('Lampiran Jurnal', 'delete', 'Lampiran bukti transaksi dihapus.', [
                'entity_type' => 'journal_attachment',
                'entity_id' => (string) $attachmentId,
                'before' => $attachment,
                'severity' => 'warning',
            ]);

            flash('success', 'Lampiran jurnal berhasil dihapus.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Lampiran jurnal gagal dihapus. ' . $e->getMessage());
        }

        $this->redirect($redirectJournalId > 0 ? '/journals/detail?id=' . $redirectJournalId : '/journals');
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID jurnal tidak valid untuk proses pembaruan.');
            $this->redirect('/journals');
        }

        $header = $this->model()->findHeaderById($id);
        if (!$header) {
            flash('error', 'Jurnal yang ingin diubah tidak ditemukan.');
            $this->redirect('/journals');
        }

        try {
            $this->ensurePeriodOpenForModification($header);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            $this->redirect('/journals');
        }

        $this->save($id);
    }

    public function delete(): void
    {
        $id = (int) get_query('id', 0);
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        if ($id <= 0) {
            flash('error', 'ID jurnal tidak valid.');
            $this->redirect('/journals');
        }

        try {
            $header = $this->model()->findHeaderById($id);
            if (!$header) {
                flash('error', 'Jurnal tidak ditemukan.');
                $this->redirect('/journals');
            }

            $this->ensurePeriodOpenForModification($header);
            $beforeSnapshot = $this->journalAuditSnapshot($id);
            $attachmentsBeforeDelete = $this->model()->getAttachmentsByJournalId($id);
            $this->model()->delete($id);
            $this->cleanupAttachmentFiles($attachmentsBeforeDelete);
            audit_log('Jurnal Umum', 'delete', 'Jurnal umum dihapus.', [
                'entity_type' => 'journal',
                'entity_id' => (string) $id,
                'before' => $beforeSnapshot,
                'severity' => 'warning',
            ]);
            flash('success', 'Jurnal berhasil dihapus.');
            $this->redirect('/journals');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Jurnal gagal dihapus. ' . $e->getMessage());
            $this->redirect('/journals');
        }
    }

    public function bulkAction(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        $redirectTo = $this->sanitizeJournalRedirect((string) post('redirect_to', '/journals'));
        $selectedIds = array_values(array_unique(array_filter(array_map('intval', (array) post('journal_ids', [])), static fn (int $id): bool => $id > 0)));
        if ($selectedIds === []) {
            flash('error', 'Pilih minimal satu jurnal yang ingin diproses.');
            $this->redirect($redirectTo);
        }

        $action = trim((string) post('bulk_action', ''));
        if (!in_array($action, ['change_unit', 'delete'], true)) {
            flash('error', 'Pilih aksi massal yang ingin dijalankan.');
            $this->redirect($redirectTo);
        }

        $targetUnitId = null;
        $targetUnitLabel = 'Global / Semua unit';
        if ($action === 'change_unit') {
            $rawUnitValue = trim((string) post('bulk_business_unit_id', ''));
            if ($rawUnitValue !== '') {
                $targetUnitId = (int) $rawUnitValue;
                if ($targetUnitId <= 0) {
                    flash('error', 'Unit usaha tujuan tidak valid.');
                    $this->redirect($redirectTo);
                }
                $targetUnit = find_business_unit($targetUnitId);
                if (!$targetUnit || (int) ($targetUnit['is_active'] ?? 0) !== 1) {
                    flash('error', 'Unit usaha tujuan tidak ditemukan atau tidak aktif.');
                    $this->redirect($redirectTo);
                }
                $targetUnitLabel = business_unit_label($targetUnit, false);
            }
        }

        $headersById = $this->model()->findHeadersByIds($selectedIds);
        $processed = 0;
        $skipped = [];
        $missing = [];
        $failed = [];
        $updatedBy = (int) (Auth::user()['id'] ?? 0);

        foreach ($selectedIds as $journalId) {
            $header = $headersById[$journalId] ?? null;
            if (!$header) {
                $missing[] = '#' . $journalId;
                continue;
            }

            try {
                $this->ensurePeriodOpenForModification($header);
                if ($action === 'change_unit') {
                    $before = $this->journalAuditSnapshot($journalId);
                    $this->model()->updateBusinessUnit($journalId, $targetUnitId, $updatedBy);
                    audit_log('Jurnal Umum', 'bulk_update_unit', 'Unit usaha jurnal diperbarui melalui aksi massal.', [
                        'entity_type' => 'journal',
                        'entity_id' => (string) $journalId,
                        'before' => $before,
                        'after' => $this->journalAuditSnapshot($journalId),
                        'context' => [
                            'target_unit_id' => $targetUnitId,
                            'target_unit_label' => $targetUnitLabel,
                            'mode' => 'bulk',
                        ],
                    ]);
                } else {
                    $beforeSnapshot = $this->journalAuditSnapshot($journalId);
                    $attachmentsBeforeDelete = $this->model()->getAttachmentsByJournalId($journalId);
                    $this->model()->delete($journalId);
                    $this->cleanupAttachmentFiles($attachmentsBeforeDelete);
                    audit_log('Jurnal Umum', 'bulk_delete', 'Jurnal umum dihapus melalui aksi massal.', [
                        'entity_type' => 'journal',
                        'entity_id' => (string) $journalId,
                        'before' => $beforeSnapshot,
                        'severity' => 'warning',
                        'context' => ['mode' => 'bulk'],
                    ]);
                }
                $processed++;
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), 'Periode jurnal sudah ditutup')) {
                    $skipped[] = (string) ($header['journal_no'] ?? ('#' . $journalId));
                    continue;
                }
                log_error($e);
                $failed[] = (string) ($header['journal_no'] ?? ('#' . $journalId)) . ' (' . $e->getMessage() . ')';
            }
        }

        $parts = [];
        if ($action === 'change_unit') {
            $parts[] = $processed . ' jurnal berhasil dipindahkan ke unit ' . $targetUnitLabel . '.';
        } else {
            $parts[] = $processed . ' jurnal berhasil dihapus.';
        }
        if ($skipped !== []) {
            $parts[] = count($skipped) . ' jurnal dilewati karena periodenya sudah tutup: ' . implode(', ', array_slice($skipped, 0, 10)) . (count($skipped) > 10 ? ' dan lainnya.' : '.');
        }
        if ($missing !== []) {
            $parts[] = count($missing) . ' data tidak ditemukan: ' . implode(', ', array_slice($missing, 0, 10)) . (count($missing) > 10 ? ' dan lainnya.' : '.');
        }
        if ($failed !== []) {
            $parts[] = count($failed) . ' jurnal gagal diproses: ' . implode('; ', array_slice($failed, 0, 5)) . (count($failed) > 5 ? '; dan lainnya.' : '.');
        }

        flash($processed > 0 ? 'success' : 'error', trim(implode(' ', $parts)));
        $this->redirect($redirectTo);
    }

    private function save(?int $id): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        $headerInput = [
            'journal_date' => trim((string) post('journal_date')),
            'description' => trim((string) post('description')),
            'period_id' => (int) post('period_id', 0),
            'business_unit_id' => (int) post('business_unit_id', 0),
            'print_template' => trim((string) post('print_template', 'standard')),
            'created_by' => (int) (Auth::user()['id'] ?? 0),
            'updated_by' => (int) (Auth::user()['id'] ?? 0),
        ];
        $receiptInput = [
            'party_title' => trim((string) post('party_title', 'Dibayar kepada')),
            'party_name' => trim((string) post('party_name', '')),
            'purpose' => trim((string) post('purpose', '')),
            'amount_in_words' => trim((string) post('amount_in_words', '')),
            'payment_method' => trim((string) post('payment_method', '')),
            'reference_no' => trim((string) post('reference_no', '')),
            'notes' => trim((string) post('notes', '')),
        ];

        $coaIds = post('coa_id', []);
        $lineDescriptions = post('line_description', []);
        $debits = post('debit', []);
        $credits = post('credit', []);
        $partnerIds = post('partner_id', []);
        $inventoryItemIds = post('inventory_item_id', []);
        $rawMaterialIds = post('raw_material_id', []);
        $assetIds = post('asset_id', []);
        $savingAccountIds = post('saving_account_id', []);
        $cashflowComponentIds = post('cashflow_component_id', []);
        $entryTags = post('entry_tag', []);

        $detailInput = [];
        $maxRows = max(
            is_countable($coaIds) ? count($coaIds) : 0,
            is_countable($lineDescriptions) ? count($lineDescriptions) : 0,
            is_countable($debits) ? count($debits) : 0,
            is_countable($credits) ? count($credits) : 0,
            is_countable($partnerIds) ? count($partnerIds) : 0,
            is_countable($inventoryItemIds) ? count($inventoryItemIds) : 0,
            is_countable($rawMaterialIds) ? count($rawMaterialIds) : 0,
            is_countable($assetIds) ? count($assetIds) : 0,
            is_countable($savingAccountIds) ? count($savingAccountIds) : 0,
            is_countable($cashflowComponentIds) ? count($cashflowComponentIds) : 0,
            is_countable($entryTags) ? count($entryTags) : 0
        );
        for ($i = 0; $i < $maxRows; $i++) {
            $detailInput[] = [
                'coa_id' => isset($coaIds[$i]) ? (int) $coaIds[$i] : 0,
                'line_description' => trim((string) ($lineDescriptions[$i] ?? '')),
                'debit_raw' => trim((string) ($debits[$i] ?? '')),
                'credit_raw' => trim((string) ($credits[$i] ?? '')),
                'partner_id' => isset($partnerIds[$i]) ? (int) $partnerIds[$i] : 0,
                'inventory_item_id' => isset($inventoryItemIds[$i]) ? (int) $inventoryItemIds[$i] : 0,
                'raw_material_id' => isset($rawMaterialIds[$i]) ? (int) $rawMaterialIds[$i] : 0,
                'asset_id' => isset($assetIds[$i]) ? (int) $assetIds[$i] : 0,
                'saving_account_id' => isset($savingAccountIds[$i]) ? (int) $savingAccountIds[$i] : 0,
                'cashflow_component_id' => isset($cashflowComponentIds[$i]) ? (int) $cashflowComponentIds[$i] : 0,
                'entry_tag' => trim((string) ($entryTags[$i] ?? '')),
            ];
        }

        with_old_input([
            'journal_date' => $headerInput['journal_date'],
            'description' => $headerInput['description'],
            'period_id' => (string) $headerInput['period_id'],
            'business_unit_id' => (string) $headerInput['business_unit_id'],
            'print_template' => $headerInput['print_template'],
            'receipt' => $receiptInput,
            'detail_rows' => $detailInput,
        ]);

        [$errors, $normalizedLines, $totals, $normalizedReceipt] = $this->validate($headerInput, $detailInput, $receiptInput, $id);
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect($id === null ? '/journals/create' : '/journals/edit?id=' . $id);
        }

        $payload = [
            'journal_date' => $headerInput['journal_date'],
            'description' => $headerInput['description'],
            'period_id' => $headerInput['period_id'],
            'business_unit_id' => $headerInput['business_unit_id'] > 0 ? $headerInput['business_unit_id'] : null,
            'print_template' => $headerInput['print_template'],
            'total_debit' => $totals['debit'],
            'total_credit' => $totals['credit'],
            'created_by' => $headerInput['created_by'],
            'updated_by' => $headerInput['updated_by'],
        ];

        try {
            $beforeSnapshot = $id !== null ? $this->journalAuditSnapshot($id) : null;
            if ($id === null) {
                $journalId = $this->model()->create($payload, $normalizedLines, $normalizedReceipt);
                audit_log('Jurnal Umum', 'create', 'Jurnal umum baru ditambahkan.', [
                    'entity_type' => 'journal',
                    'entity_id' => (string) $journalId,
                    'after' => $this->journalAuditSnapshot($journalId),
                    'context' => $this->journalAuditContext($payload, $normalizedLines),
                ]);
                flash('success', 'Jurnal umum berhasil ditambahkan.');
            } else {
                $this->model()->update($id, $payload, $normalizedLines, $normalizedReceipt);
                $journalId = $id;
                audit_log('Jurnal Umum', 'update', 'Jurnal umum diperbarui.', [
                    'entity_type' => 'journal',
                    'entity_id' => (string) $journalId,
                    'before' => $beforeSnapshot,
                    'after' => $this->journalAuditSnapshot($journalId),
                    'context' => $this->journalAuditContext($payload, $normalizedLines),
                ]);
                flash('success', 'Jurnal umum berhasil diperbarui.');
            }
            clear_old_input();
            $this->redirect('/journals/detail?id=' . $journalId);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Jurnal umum gagal disimpan. Silakan periksa data dan coba lagi.');
            $this->redirect($id === null ? '/journals/create' : '/journals/edit?id=' . $id);
        }
    }

    private function validate(array $headerInput, array $detailInput, array $receiptInput, ?int $existingId = null): array
    {
        $errors = [];
        $normalizedLines = [];
        $normalizedReceipt = null;
        $totalDebitCents = 0;
        $totalCreditCents = 0;

        if (!$this->isValidDate($headerInput['journal_date'])) {
            $errors[] = 'Tanggal jurnal wajib diisi dengan format yang benar.';
        }
        if ($headerInput['description'] === '') {
            $errors[] = 'Keterangan jurnal wajib diisi.';
        } elseif (mb_strlen($headerInput['description']) < 3 || mb_strlen($headerInput['description']) > 255) {
            $errors[] = 'Keterangan jurnal harus 3 sampai 255 karakter.';
        }

        if (!array_key_exists($headerInput['print_template'], journal_print_template_options())) {
            $errors[] = 'Template cetak jurnal tidak valid.';
        }

        if ($headerInput['period_id'] <= 0) {
            $errors[] = 'Periode jurnal wajib dipilih.';
        } else {
            $period = $this->model()->findPeriodById($headerInput['period_id']);
            if (!$period) {
                $errors[] = 'Periode jurnal tidak ditemukan.';
            } elseif ((string) $period['status'] !== 'OPEN') {
                $errors[] = 'Jurnal hanya bisa disimpan ke periode yang masih terbuka.';
            } elseif ($this->isValidDate($headerInput['journal_date']) && !$this->dateWithinPeriod($headerInput['journal_date'], $period)) {
                $errors[] = 'Tanggal jurnal harus berada di dalam rentang periode yang dipilih.';
            }
        }

        if ($headerInput['business_unit_id'] > 0) {
            $unit = find_business_unit($headerInput['business_unit_id']);
            if (!$unit) {
                $errors[] = 'Unit usaha yang dipilih tidak ditemukan.';
            } elseif ((int) ($unit['is_active'] ?? 0) !== 1) {
                $errors[] = 'Unit usaha yang dipilih harus aktif.';
            }
        }

        foreach ($detailInput as $index => $line) {
            $rowNumber = $index + 1;
            $debitCents = $this->moneyToCents($line['debit_raw']);
            $creditCents = $this->moneyToCents($line['credit_raw']);
            $isBlankRow = $line['coa_id'] <= 0 && $line['line_description'] === '' && $debitCents === 0 && $creditCents === 0;
            if ($isBlankRow) {
                continue;
            }

            $rowHasError = false;
            if ($line['coa_id'] <= 0) {
                $errors[] = 'Baris jurnal #' . $rowNumber . ' wajib memilih akun.';
                $rowHasError = true;
            } else {
                $account = $this->model()->findJournalAccountById($line['coa_id']);
                if (!$account) {
                    $errors[] = 'Baris jurnal #' . $rowNumber . ' menggunakan akun yang tidak ditemukan.';
                    $rowHasError = true;
                } else {
                    if ((int) $account['is_active'] !== 1) {
                        $errors[] = 'Baris jurnal #' . $rowNumber . ' menggunakan akun nonaktif.';
                        $rowHasError = true;
                    }
                    if ((int) $account['is_header'] === 1) {
                        $errors[] = 'Baris jurnal #' . $rowNumber . ' harus memakai akun detail, bukan akun header.';
                        $rowHasError = true;
                    }
                }
            }

            foreach ([
                'partner_id' => ['reference_partners', 'Mitra / partner'],
                'inventory_item_id' => ['inventory_items', 'Persediaan'],
                'raw_material_id' => ['raw_materials', 'Bahan baku'],
                'asset_id' => ['asset_items', 'Aset'],
                'saving_account_id' => ['saving_accounts', 'Simpanan'],
                'cashflow_component_id' => ['cashflow_components', 'Komponen arus kas'],
            ] as $metaField => [$tableName, $label]) {
                $metaId = (int) ($line[$metaField] ?? 0);
                if ($metaId > 0 && !$this->referenceExists($tableName, $metaId)) {
                    $errors[] = $label . ' pada baris jurnal #' . $rowNumber . ' tidak ditemukan.';
                    $rowHasError = true;
                }
            }
            if (($line['entry_tag'] ?? '') !== '' && !array_key_exists((string) $line['entry_tag'], $this->model()->getReferenceOptions()['entry_tags'])) {
                $errors[] = 'Tag entri pada baris jurnal #' . $rowNumber . ' tidak valid.';
                $rowHasError = true;
            }

            if (mb_strlen($line['line_description']) > 255) {
                $errors[] = 'Uraian baris jurnal #' . $rowNumber . ' maksimal 255 karakter.';
                $rowHasError = true;
            }
            if ($debitCents === null || $creditCents === null) {
                $errors[] = 'Nilai debit/kredit pada baris jurnal #' . $rowNumber . ' tidak valid.';
                $rowHasError = true;
            }
            if ($debitCents !== null && $creditCents !== null) {
                if ($debitCents < 0 || $creditCents < 0) {
                    $errors[] = 'Nilai debit/kredit pada baris jurnal #' . $rowNumber . ' tidak boleh negatif.';
                    $rowHasError = true;
                }
                if ($debitCents > 0 && $creditCents > 0) {
                    $errors[] = 'Baris jurnal #' . $rowNumber . ' tidak boleh mengisi debit dan kredit sekaligus.';
                    $rowHasError = true;
                }
                if ($debitCents === 0 && $creditCents === 0) {
                    $errors[] = 'Baris jurnal #' . $rowNumber . ' minimal harus memiliki nilai debit atau kredit.';
                    $rowHasError = true;
                }
            }

            if ($rowHasError) {
                continue;
            }

            $normalizedLines[] = [
                'coa_id' => $line['coa_id'],
                'line_description' => $line['line_description'],
                'debit' => $this->centsToMoney($debitCents ?? 0),
                'credit' => $this->centsToMoney($creditCents ?? 0),
                'partner_id' => (int) ($line['partner_id'] ?? 0),
                'inventory_item_id' => (int) ($line['inventory_item_id'] ?? 0),
                'raw_material_id' => (int) ($line['raw_material_id'] ?? 0),
                'asset_id' => (int) ($line['asset_id'] ?? 0),
                'saving_account_id' => (int) ($line['saving_account_id'] ?? 0),
                'cashflow_component_id' => (int) ($line['cashflow_component_id'] ?? 0),
                'entry_tag' => trim((string) ($line['entry_tag'] ?? '')),
            ];
            $totalDebitCents += $debitCents ?? 0;
            $totalCreditCents += $creditCents ?? 0;
        }

        if (count($normalizedLines) < 2) {
            $errors[] = 'Jurnal umum minimal harus memiliki 2 baris detail.';
        }
        if ($totalDebitCents === 0 && $totalCreditCents === 0) {
            $errors[] = 'Total jurnal tidak boleh semua nol.';
        }
        if ($totalDebitCents !== $totalCreditCents) {
            $errors[] = 'Total debit harus sama dengan total kredit.';
        }

        $hasDebitLine = false;
        $hasCreditLine = false;
        foreach ($normalizedLines as $line) {
            if ((float) ($line['debit'] ?? 0) > 0) {
                $hasDebitLine = true;
            }
            if ((float) ($line['credit'] ?? 0) > 0) {
                $hasCreditLine = true;
            }
        }
        if (!$hasDebitLine || !$hasCreditLine) {
            $errors[] = 'Jurnal harus memiliki minimal satu baris debit dan satu baris kredit.';
        }

        $workingYear = current_working_year();
        if ($workingYear > 0 && $this->isValidDate($headerInput['journal_date'])) {
            $journalYear = (int) substr($headerInput['journal_date'], 0, 4);
            if ($journalYear !== $workingYear) {
                $errors[] = 'Tanggal jurnal harus sesuai dengan tahun kerja aktif ' . $workingYear . '.';
            }
        }

        if ($errors === []) {
            $duplicate = $this->model()->findPotentialDuplicate([
                'period_id' => $headerInput['period_id'],
                'journal_date' => $headerInput['journal_date'],
                'description' => $headerInput['description'],
                'business_unit_id' => $headerInput['business_unit_id'] > 0 ? $headerInput['business_unit_id'] : null,
                'total_debit' => $this->centsToMoney($totalDebitCents),
                'total_credit' => $this->centsToMoney($totalCreditCents),
            ], $existingId);
            if (is_array($duplicate)) {
                $errors[] = 'Terdeteksi jurnal serupa dengan nomor ' . (string) ($duplicate['journal_no'] ?? '#') . '. Periksa kembali agar tidak terjadi input ganda.';
            }
        }

        if ($headerInput['print_template'] === 'receipt') {
            if (!$this->model()->isReceiptFeatureEnabled()) {
                $errors[] = 'Fitur kwitansi belum aktif penuh. Import file database/patch_journal_print_receipt.sql melalui phpMyAdmin terlebih dahulu.';
            }

            $validPartyTitles = journal_receipt_party_title_options();
            if (!isset($validPartyTitles[$receiptInput['party_title']])) {
                $errors[] = 'Label pihak pada bukti transaksi tidak valid.';
            }
            if ($receiptInput['party_name'] === '') {
                $errors[] = 'Nama pihak pada bukti transaksi wajib diisi.';
            } elseif (mb_strlen($receiptInput['party_name']) > 150) {
                $errors[] = 'Nama pihak pada bukti transaksi maksimal 150 karakter.';
            }
            if ($receiptInput['purpose'] === '') {
                $errors[] = 'Tujuan transaksi pada bukti transaksi wajib diisi.';
            } elseif (mb_strlen($receiptInput['purpose']) > 255) {
                $errors[] = 'Tujuan transaksi pada bukti transaksi maksimal 255 karakter.';
            }
            if ($receiptInput['amount_in_words'] !== '' && mb_strlen($receiptInput['amount_in_words']) > 255) {
                $errors[] = 'Nominal terbilang maksimal 255 karakter.';
            }
            if (mb_strlen($receiptInput['payment_method']) > 50) {
                $errors[] = 'Metode pembayaran maksimal 50 karakter.';
            }
            if (mb_strlen($receiptInput['reference_no']) > 100) {
                $errors[] = 'Nomor referensi maksimal 100 karakter.';
            }
            if (mb_strlen($receiptInput['notes']) > 1000) {
                $errors[] = 'Catatan bukti transaksi maksimal 1000 karakter.';
            }

            $normalizedReceipt = [
                'party_title' => $receiptInput['party_title'],
                'party_name' => $receiptInput['party_name'],
                'purpose' => $receiptInput['purpose'],
                'amount_in_words' => $receiptInput['amount_in_words'] !== ''
                    ? $receiptInput['amount_in_words']
                    : journal_amount_in_words($this->centsToMoney($totalDebitCents)),
                'payment_method' => $receiptInput['payment_method'],
                'reference_no' => $receiptInput['reference_no'],
                'notes' => $receiptInput['notes'],
            ];
        }

        return [$errors, $normalizedLines, ['debit' => $this->centsToMoney($totalDebitCents), 'credit' => $this->centsToMoney($totalCreditCents)], $normalizedReceipt];
    }

    private function showForm(string $title, ?array $header, array $details, ?array $quickTemplate = null, ?array $duplicateSource = null): void
    {
        try {
            $periodOptions = $this->model()->getOpenPeriods();
            $journalNoPreviewMap = [];
            foreach ($periodOptions as $period) {
                $periodId = (int) ($period['id'] ?? 0);
                if ($periodId <= 0) {
                    continue;
                }
                try {
                    $journalNoPreviewMap[(string) $periodId] = $this->model()->previewJournalNumber($periodId);
                } catch (Throwable) {
                    $journalNoPreviewMap[(string) $periodId] = 'Otomatis saat disimpan';
                }
            }

            $formRows = old_input('detail_rows');
            if (!is_array($formRows) || $formRows === []) {
                if (is_array($quickTemplate) && !empty($quickTemplate['detail_rows']) && is_array($quickTemplate['detail_rows'])) {
                    $formRows = $quickTemplate['detail_rows'];
                } elseif ($details !== []) {
                    $formRows = array_map(function (array $detail): array {
                        return [
                            'coa_id' => (string) $detail['coa_id'],
                            'line_description' => (string) $detail['line_description'],
                            'debit_raw' => $this->formatMoneyForInput($detail['debit'] ?? 0),
                            'credit_raw' => $this->formatMoneyForInput($detail['credit'] ?? 0),
                            'partner_id' => (string) ($detail['partner_id'] ?? ''),
                            'inventory_item_id' => (string) ($detail['inventory_item_id'] ?? ''),
                            'raw_material_id' => (string) ($detail['raw_material_id'] ?? ''),
                            'asset_id' => (string) ($detail['asset_id'] ?? ''),
                            'saving_account_id' => (string) ($detail['saving_account_id'] ?? ''),
                            'cashflow_component_id' => (string) ($detail['cashflow_component_id'] ?? ''),
                            'entry_tag' => (string) ($detail['entry_tag'] ?? ''),
                        ];
                    }, $details);
                } else {
                    $formRows = [
                        ['coa_id' => '', 'line_description' => '', 'debit_raw' => '', 'credit_raw' => '', 'partner_id' => '', 'inventory_item_id' => '', 'raw_material_id' => '', 'asset_id' => '', 'saving_account_id' => '', 'cashflow_component_id' => '', 'entry_tag' => ''],
                        ['coa_id' => '', 'line_description' => '', 'debit_raw' => '', 'credit_raw' => '', 'partner_id' => '', 'inventory_item_id' => '', 'raw_material_id' => '', 'asset_id' => '', 'saving_account_id' => '', 'cashflow_component_id' => '', 'entry_tag' => ''],
                    ];
                }
            }

            $receiptOld = old_input('receipt');
            $quickReceipt = is_array($quickTemplate['receipt'] ?? null) ? $quickTemplate['receipt'] : [];
            $selectedPeriodId = old('period_id', (string) ($header['period_id'] ?? ($quickTemplate['period_id'] ?? (string) (current_accounting_period()['id'] ?? ''))));
            $this->view('journals/views/form', [
                'title' => $title,
                'header' => $header,
                'formData' => [
                    'journal_no' => (string) ($header['journal_no'] ?? 'Otomatis saat disimpan'),
                    'journal_date' => old('journal_date', (string) ($header['journal_date'] ?? ($quickTemplate['journal_date'] ?? date('Y-m-d')))),
                    'description' => old('description', (string) ($header['description'] ?? ($quickTemplate['description'] ?? ''))),
                    'period_id' => $selectedPeriodId,
                    'business_unit_id' => old('business_unit_id', (string) ($header['business_unit_id'] ?? ($quickTemplate['business_unit_id'] ?? ''))),
                    'print_template' => old('print_template', (string) ($header['print_template'] ?? ($quickTemplate['print_template'] ?? 'standard'))),
                ],
                'receiptData' => [
                    'party_title' => is_array($receiptOld) ? (string) ($receiptOld['party_title'] ?? 'Dibayar kepada') : (string) ($header['party_title'] ?? ($quickReceipt['party_title'] ?? 'Dibayar kepada')),
                    'party_name' => is_array($receiptOld) ? (string) ($receiptOld['party_name'] ?? '') : (string) ($header['party_name'] ?? ($quickReceipt['party_name'] ?? '')),
                    'purpose' => is_array($receiptOld) ? (string) ($receiptOld['purpose'] ?? '') : (string) ($header['purpose'] ?? ($quickReceipt['purpose'] ?? '')),
                    'amount_in_words' => is_array($receiptOld) ? (string) ($receiptOld['amount_in_words'] ?? '') : (string) ($header['amount_in_words'] ?? ($quickReceipt['amount_in_words'] ?? '')),
                    'payment_method' => is_array($receiptOld) ? (string) ($receiptOld['payment_method'] ?? '') : (string) ($header['payment_method'] ?? ($quickReceipt['payment_method'] ?? '')),
                    'reference_no' => is_array($receiptOld) ? (string) ($receiptOld['reference_no'] ?? '') : (string) ($header['reference_no'] ?? ($quickReceipt['reference_no'] ?? '')),
                    'notes' => is_array($receiptOld) ? (string) ($receiptOld['notes'] ?? '') : (string) ($header['notes'] ?? ($quickReceipt['notes'] ?? '')),
                ],
                'detailRows' => $formRows,
                'periodOptions' => $periodOptions,
                'journalNoPreviewMap' => $journalNoPreviewMap,
                'journalNoPreviewCurrent' => $journalNoPreviewMap[$selectedPeriodId] ?? ((string) ($header['journal_no'] ?? 'Otomatis saat disimpan')),
                'accountOptions' => $this->model()->getAccountOptions((int) (Auth::user()['id'] ?? 0)),
                'unitOptions' => business_unit_options(),
                'receiptPartyTitleOptions' => journal_receipt_party_title_options(),
                'receiptFeatureStatus' => $this->model()->getReceiptFeatureStatus(),
                'referenceOptions' => $this->model()->getReferenceOptions(),
                'quickTemplateOptions' => journal_quick_template_options(),
                'activeQuickTemplate' => $quickTemplate,
                'duplicateSource' => $duplicateSource,
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Form jurnal umum belum dapat dibuka. Pastikan tabel jurnal, periode, dan COA sudah tersedia.', $e);
        }
    }


    private function referenceExists(string $table, int $id): bool
    {
        try {
            $db = Database::getInstance(db_config());
            $stmt = $db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name LIMIT 1');
            $stmt->bindValue(':table_name', $table, PDO::PARAM_STR);
            $stmt->execute();
            if (!(bool) $stmt->fetchColumn()) {
                return true;
            }
            $sql = 'SELECT 1 FROM ' . $table . ' WHERE id = :id LIMIT 1';
            $check = $db->prepare($sql);
            $check->bindValue(':id', $id, PDO::PARAM_INT);
            $check->execute();
            return (bool) $check->fetchColumn();
        } catch (Throwable) {
            return true;
        }
    }

    private function ensurePeriodOpenForModification(array $header): void
    {
        if ((string) ($header['period_status'] ?? '') !== 'OPEN') {
            throw new RuntimeException('Periode jurnal sudah ditutup. Jurnal pada periode tertutup tidak boleh diubah atau dihapus.');
        }
    }

    private function readFilters(): array
    {
        $filters = [
            'period_id' => (int) get_query('period_id', 0),
            'unit_id' => (int) get_query('unit_id', 0),
            'date_from' => trim((string) get_query('date_from', '')),
            'date_to' => trim((string) get_query('date_to', '')),
        ];

        if ($filters['period_id'] <= 0 && $filters['date_from'] === '' && $filters['date_to'] === '') {
            $range = working_year_date_range();
            $filters['date_from'] = (string) ($range['date_from'] ?? '');
            $filters['date_to'] = (string) ($range['date_to'] ?? '');
        }

        return $filters;
    }

    private function sanitizeJournalRedirect(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/journals';
        }
        if (!str_starts_with($path, '/journals')) {
            return '/journals';
        }
        return $path;
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '') {
            return false;
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTimeImmutable && $dt->format('Y-m-d') === $date;
    }

    private function dateWithinPeriod(string $date, array $period): bool
    {
        return $date >= (string) $period['start_date'] && $date <= (string) $period['end_date'];
    }

    private function moneyToCents(string $value): ?int
    {
        $value = trim(str_replace(["Â ", ' '], '', $value));
        if ($value === '') {
            return 0;
        }

        $lastDot = strrpos($value, '.');
        $lastComma = strrpos($value, ',');

        if ($lastDot !== false && $lastComma !== false) {
            if ($lastDot > $lastComma) {
                $value = str_replace(',', '', $value);
            } else {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
        } elseif ($lastComma !== false) {
            if (preg_match('/,\d{1,2}$/', $value) === 1) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastDot !== false) {
            if (preg_match('/\.\d{1,2}$/', $value) !== 1) {
                $value = str_replace('.', '', $value);
            }
        }

        if (!preg_match('/^-?\d+(\.\d{1,2})?$/', $value)) {
            return null;
        }

        $negative = str_starts_with($value, '-');
        if ($negative) {
            $value = substr($value, 1);
        }

        [$whole, $decimal] = array_pad(explode('.', $value, 2), 2, '0');
        $decimal = str_pad(substr($decimal, 0, 2), 2, '0');
        $cents = ((int) $whole * 100) + (int) $decimal;

        return $negative ? -$cents : $cents;
    }

    private function centsToMoney(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function formatMoneyForInput(mixed $value): string
    {
        $normalized = number_format((float) $value, 2, '.', '');
        if (str_ends_with($normalized, '.00')) {
            return substr($normalized, 0, -3);
        }
        return rtrim(rtrim($normalized, '0'), '.');
    }

    private function cleanupAttachmentFiles(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            JournalAttachmentService::deleteStoredFile((string) ($attachment['stored_name'] ?? ''));
        }
    }

    private function journalAuditSnapshot(int $journalId): ?array
    {
        $header = $this->model()->findHeaderById($journalId);
        if (!$header) {
            return null;
        }

        return [
            'header' => $header,
            'details' => $this->model()->getDetailsByJournalId($journalId),
            'attachments' => $this->model()->getAttachmentsByJournalId($journalId),
        ];
    }

    private function journalAuditContext(array $payload, array $normalizedLines): array
    {
        return [
            'journal_date' => $payload['journal_date'] ?? '',
            'period_id' => $payload['period_id'] ?? null,
            'business_unit_id' => $payload['business_unit_id'] ?? null,
            'line_count' => count($normalizedLines),
            'total_debit' => $payload['total_debit'] ?? 0,
            'total_credit' => $payload['total_credit'] ?? 0,
            'print_template' => $payload['print_template'] ?? 'standard',
        ];
    }
}
