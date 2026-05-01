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

    private function assetModel(): AssetModel
    {
        return new AssetModel(Database::getInstance(db_config()));
    }

    private function db(): PDO
    {
        return Database::getInstance(db_config());
    }

    private function userId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    public function index(): void
    {
        try {
            $filters = $this->readFilters();
            $page = listing_resolve_page();
            $perPage = listing_resolve_per_page(10);
            $total = $this->model()->countList($filters);
            $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $offset = max(0, ($page - 1) * $perPage);
            $journals = $this->model()->getList($filters, [
                'limit' => $perPage,
                'offset' => $offset,
            ]);

            $this->view('journals/views/index', [
                'title' => 'Jurnal Umum',
                'filters' => $filters,
                'journals' => $journals,
                'listing' => [
                    'items' => $journals,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'from' => $total === 0 ? 0 : ($offset + 1),
                    'to' => $total === 0 ? 0 : min($total, $offset + count($journals)),
                    'has_prev' => $page > 1,
                    'has_next' => $page < $totalPages,
                    'prev_page' => $page > 1 ? ($page - 1) : 1,
                    'next_page' => $page < $totalPages ? ($page + 1) : $totalPages,
                ],
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

            audit_log('Lampiran Jurnal', 'download', 'Lampiran bukti transaksi diunduh.', [
                'entity_type' => 'journal_attachment',
                'entity_id' => (string) $attachmentId,
                'context' => [
                    'journal_id' => (int) ($attachment['journal_id'] ?? 0),
                    'journal_no' => (string) ($attachment['journal_no'] ?? ''),
                    'original_name' => (string) ($attachment['original_name'] ?? ''),
                    'file_size' => (int) ($attachment['file_size'] ?? 0),
                ],
            ]);

            header('Content-Type: ' . $contentType);
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . (string) ((int) (@filesize($path) ?: 0)));
            header("Content-Disposition: attachment; filename=\"{$fallbackName}\"; filename*=UTF-8''" . rawurlencode($downloadName));
            readfile($path);
            exit;
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Lampiran jurnal belum dapat diunduh.', $e);
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

    public function workflowAction(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        $redirectTo = $this->sanitizeJournalRedirect((string) post('redirect_to', '/journals'));
        $journalId = (int) post('journal_id', 0);
        $action = strtolower(trim((string) post('workflow_action', '')));
        $reason = trim((string) post('workflow_reason', ''));
        if ($journalId <= 0) {
            flash('error', 'ID jurnal tidak valid.');
            $this->redirect($redirectTo);
        }

        try {
            $header = $this->model()->findHeaderById($journalId);
            if (!$header) {
                flash('error', 'Jurnal tidak ditemukan.');
                $this->redirect($redirectTo);
            }
            $allowed = journal_workflow_allowed_actions((string) ($header['workflow_status'] ?? 'POSTED'), (string) (Auth::user()['role_code'] ?? ''));
            if (!in_array($action, $allowed, true)) {
                flash('error', 'Role Anda tidak punya akses untuk aksi workflow ini.');
                $this->redirect($redirectTo);
            }

            $this->ensurePeriodOpenForModification($header);
            $beforeSnapshot = $this->journalAuditSnapshot($journalId);
            $userId = (int) (Auth::user()['id'] ?? 0);
            if ($action === 'reverse') {
                $reversalId = $this->model()->createReversal($journalId, $userId, $reason);
                audit_log('Jurnal Umum', 'workflow_reverse', 'Jurnal direversal dengan jurnal pembalik.', [
                    'entity_type' => 'journal',
                    'entity_id' => (string) $journalId,
                    'before' => $beforeSnapshot,
                    'after' => [
                        'source' => $this->journalAuditSnapshot($journalId),
                        'reversal' => $this->journalAuditSnapshot($reversalId),
                    ],
                    'severity' => 'warning',
                    'context' => ['reason' => $reason, 'reversal_journal_id' => $reversalId],
                ]);
                flash('success', 'Reversal berhasil dibuat. Jurnal pembalik #' . (string) $reversalId . ' sudah diposting.');
            } else {
                $result = $this->model()->applyWorkflowAction($journalId, $action, $userId, $reason);
                audit_log('Jurnal Umum', 'workflow_' . $action, 'Status workflow jurnal diperbarui.', [
                    'entity_type' => 'journal',
                    'entity_id' => (string) $journalId,
                    'before' => $beforeSnapshot,
                    'after' => $this->journalAuditSnapshot($journalId),
                    'context' => $result + ['reason' => $reason],
                    'severity' => in_array($action, ['void'], true) ? 'warning' : 'info',
                ]);
                flash('success', 'Status jurnal berhasil diubah menjadi ' . journal_workflow_label((string) ($result['after_status'] ?? '')) . '.');
            }
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Aksi workflow gagal. ' . $e->getMessage());
        }

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
        $assetFormEnabled = post('asset_form_enabled', []);
        $assetFormItems = post('asset_form_items', []);

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
            is_countable($entryTags) ? count($entryTags) : 0,
            is_countable($assetFormEnabled) ? count($assetFormEnabled) : 0,
            is_countable($assetFormItems) ? count($assetFormItems) : 0
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
                'asset_form' => [
                    'enabled' => (string) ($assetFormEnabled[$i] ?? '0'),
                    'items' => $this->decodeManagedAssetItemsJson((string) ($assetFormItems[$i] ?? '')),
                ],
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
            $db = $this->db();
            $ownTransaction = !$db->inTransaction();
            if ($ownTransaction) {
                $db->beginTransaction();
            }
            $beforeSnapshot = $id !== null ? $this->journalAuditSnapshot($id) : null;
            $successMessage = '';
            if ($id === null) {
                $journalId = $this->model()->create($payload, $normalizedLines, $normalizedReceipt);
                $successMessage = 'Jurnal umum berhasil ditambahkan.';
                audit_log('Jurnal Umum', 'create', 'Jurnal umum baru ditambahkan.', [
                    'entity_type' => 'journal',
                    'entity_id' => (string) $journalId,
                    'after' => $this->journalAuditSnapshot($journalId),
                    'context' => $this->journalAuditContext($payload, $normalizedLines),
                ]);
            } else {
                $this->model()->update($id, $payload, $normalizedLines, $normalizedReceipt);
                $journalId = $id;
                $successMessage = 'Jurnal umum berhasil diperbarui.';
                audit_log('Jurnal Umum', 'update', 'Jurnal umum diperbarui.', [
                    'entity_type' => 'journal',
                    'entity_id' => (string) $journalId,
                    'before' => $beforeSnapshot,
                    'after' => $this->journalAuditSnapshot($journalId),
                    'context' => $this->journalAuditContext($payload, $normalizedLines),
                ]);
            }

            $savedHeader = $this->model()->findHeaderById($journalId);
            if (!$savedHeader) {
                throw new RuntimeException('Jurnal yang baru disimpan tidak dapat dimuat ulang untuk sinkronisasi aset.');
            }

            $assetSync = $this->syncManagedAssetsFromJournal($journalId, $savedHeader, $normalizedLines, $this->userId());

            if ($ownTransaction && $db->inTransaction()) {
                $db->commit();
            }

            clear_old_input();
            if (($assetSync['created'] + $assetSync['updated']) > 0) {
                $successMessage .= ' Sinkron aset: ' . $assetSync['created'] . ' aset baru, ' . $assetSync['updated'] . ' aset diperbarui.';
            }
            flash('success', $successMessage);
            $this->redirect('/journals/detail?id=' . $journalId);
        } catch (Throwable $e) {
            if (isset($ownTransaction) && $ownTransaction && $this->db()->inTransaction()) {
                $this->db()->rollBack();
            }
            log_error($e);
            flash('error', 'Jurnal umum gagal disimpan. ' . $e->getMessage());
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

            [$assetFormErrors, $normalizedAssetForm] = $this->validateManagedAssetForm($line, $headerInput, $account ?? null, $rowNumber);
            if ($assetFormErrors !== []) {
                foreach ($assetFormErrors as $assetFormError) {
                    $errors[] = $assetFormError;
                }
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
                'asset_form' => $normalizedAssetForm,
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
                    $formRows = array_map(fn (array $detail): array => $this->buildFormRowFromDetail($detail, $header), $details);
                } else {
                    $formRows = [
                        $this->emptyJournalFormRow(),
                        $this->emptyJournalFormRow(),
                    ];
                }
            }
            $formRows = array_map(fn (array $row): array => $this->prepareFormRow($row, $header), $formRows);

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
                'assetCategoryOptions' => $this->assetModel()->getCategories(false),
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

    private function emptyJournalFormRow(): array
    {
        return [
            'coa_id' => '',
            'line_description' => '',
            'debit_raw' => '',
            'credit_raw' => '',
            'partner_id' => '',
            'inventory_item_id' => '',
            'raw_material_id' => '',
            'asset_id' => '',
            'saving_account_id' => '',
            'cashflow_component_id' => '',
            'entry_tag' => '',
            'asset_form' => $this->defaultManagedAssetForm(),
        ];
    }

    private function prepareFormRow(array $row, ?array $header = null): array
    {
        $rawAssetForm = is_array($row['asset_form'] ?? null) ? $row['asset_form'] : [];
        $prepared = array_merge($this->emptyJournalFormRow(), $row);
        $prepared['asset_form'] = $this->normalizeManagedAssetFormForDisplay(
            $rawAssetForm,
            (int) ($prepared['asset_id'] ?? 0),
            $prepared,
            $header
        );

        return $prepared;
    }

    private function buildFormRowFromDetail(array $detail, ?array $header = null): array
    {
        return $this->prepareFormRow([
            'coa_id' => (string) ($detail['coa_id'] ?? ''),
            'line_description' => (string) ($detail['line_description'] ?? ''),
            'debit_raw' => $this->formatMoneyForInput($detail['debit'] ?? 0),
            'credit_raw' => $this->formatMoneyForInput($detail['credit'] ?? 0),
            'partner_id' => (string) ($detail['partner_id'] ?? ''),
            'inventory_item_id' => (string) ($detail['inventory_item_id'] ?? ''),
            'raw_material_id' => (string) ($detail['raw_material_id'] ?? ''),
            'asset_id' => (string) ($detail['asset_id'] ?? ''),
            'saving_account_id' => (string) ($detail['saving_account_id'] ?? ''),
            'cashflow_component_id' => (string) ($detail['cashflow_component_id'] ?? ''),
            'entry_tag' => (string) ($detail['entry_tag'] ?? ''),
        ], $header);
    }

    private function defaultManagedAssetForm(): array
    {
        return [
            'enabled' => '0',
            'items' => [$this->defaultManagedAssetFormItem()],
        ];
    }

    private function defaultManagedAssetFormItem(): array
    {
        return [
            'asset_name' => '',
            'category_id' => '',
            'subcategory_name' => '',
            'quantity' => '1',
            'unit_name' => 'unit',
            'acquisition_cost_raw' => '',
            'location' => '',
            'supplier_name' => '',
            'description' => '',
        ];
    }

    private function normalizeManagedAssetFormForDisplay(array $assetForm, int $assetId, array $row, ?array $header = null): array
    {
        $hasExplicitEnabled = array_key_exists('enabled', $assetForm);
        $normalized = array_merge($this->defaultManagedAssetForm(), $assetForm);
        $normalized['items'] = $this->normalizeManagedAssetItemsForDisplay(
            is_array($normalized['items'] ?? null) ? $normalized['items'] : [],
            $row,
            $header
        );
        $asset = null;
        if ($assetId > 0) {
            try {
                $asset = $this->assetModel()->findAssetById($assetId);
            } catch (Throwable) {
                $asset = null;
            }
        }

        if (is_array($asset)) {
            $journalLinked = (int) ($asset['linked_journal_id'] ?? 0) > 0
                && (int) ($asset['linked_journal_id'] ?? 0) === (int) ($header['id'] ?? 0);
            $normalized['enabled'] = $hasExplicitEnabled ? (string) ($normalized['enabled'] ?? '0') : ($journalLinked ? '1' : '0');
            if ($normalized['items'] === [] || $this->managedAssetItemsAreEmpty($normalized['items'])) {
                $normalized['items'] = [[
                    'asset_name' => (string) ($asset['asset_name'] ?? ''),
                    'category_id' => (string) ($asset['category_id'] ?? ''),
                    'subcategory_name' => (string) ($asset['subcategory_name'] ?? ''),
                    'quantity' => (string) ($asset['quantity'] ?? '1'),
                    'unit_name' => (string) (($asset['unit_name'] ?? '') !== '' ? $asset['unit_name'] : 'unit'),
                    'acquisition_cost_raw' => $this->formatMoneyForInput($asset['acquisition_cost'] ?? 0),
                    'location' => (string) ($asset['location'] ?? ''),
                    'supplier_name' => (string) ($asset['supplier_name'] ?? ''),
                    'description' => (string) ($asset['description'] ?? ''),
                ]];
            }
        }

        if ((!is_array($asset) || (int) ($asset['id'] ?? 0) <= 0)
            && ((int) ($header['id'] ?? 0) > 0)
            && ((int) ($row['line_no'] ?? 0) > 0)
            && ($normalized['items'] === [] || $this->managedAssetItemsAreEmpty($normalized['items']))) {
            $managedAssets = $this->assetModel()->getManagedJournalAssetsByLine((int) $header['id'], (int) $row['line_no']);
            if ($managedAssets !== []) {
                $normalized['enabled'] = $hasExplicitEnabled ? (string) ($normalized['enabled'] ?? '0') : '1';
                $normalized['items'] = array_map(function (array $managedAsset): array {
                    return [
                        'asset_name' => (string) ($managedAsset['asset_name'] ?? ''),
                        'category_id' => (string) ($managedAsset['category_id'] ?? ''),
                        'subcategory_name' => (string) ($managedAsset['subcategory_name'] ?? ''),
                        'quantity' => (string) ($managedAsset['quantity'] ?? '1'),
                        'unit_name' => (string) (($managedAsset['unit_name'] ?? '') !== '' ? $managedAsset['unit_name'] : 'unit'),
                        'acquisition_cost_raw' => $this->formatMoneyForInput($managedAsset['acquisition_cost'] ?? 0),
                        'location' => (string) ($managedAsset['location'] ?? ''),
                        'supplier_name' => (string) ($managedAsset['supplier_name'] ?? ''),
                        'description' => (string) ($managedAsset['description'] ?? ''),
                    ];
                }, $managedAssets);
            }
        }

        return $normalized;
    }

    private function normalizeManagedAssetItemsForDisplay(array $items, array $row, ?array $header = null): array
    {
        if ($items === [] && $this->hasLegacyManagedAssetItemShape($row)) {
            $items = [[
                'asset_name' => (string) ($row['asset_name'] ?? ''),
                'category_id' => (string) ($row['category_id'] ?? ''),
                'subcategory_name' => (string) ($row['subcategory_name'] ?? ''),
                'quantity' => (string) ($row['quantity'] ?? '1'),
                'unit_name' => (string) ($row['unit_name'] ?? 'unit'),
                'acquisition_cost_raw' => (string) ($row['acquisition_cost_raw'] ?? ''),
                'location' => (string) ($row['location'] ?? ''),
                'supplier_name' => (string) ($row['supplier_name'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
            ]];
        }

        if ($items === []) {
            $items = [$this->defaultManagedAssetFormItem()];
        }

        $normalizedItems = [];
        foreach ($items as $item) {
            $normalizedItem = array_merge($this->defaultManagedAssetFormItem(), is_array($item) ? $item : []);
            if ($normalizedItem['asset_name'] === '') {
                $normalizedItem['asset_name'] = trim((string) (($row['line_description'] ?? '') !== '' ? ($row['line_description'] ?? '') : ($header['description'] ?? '')));
            }
            if ($normalizedItem['acquisition_cost_raw'] === '') {
                $normalizedItem['acquisition_cost_raw'] = trim((string) (($row['debit_raw'] ?? '') !== '' ? ($row['debit_raw'] ?? '') : ''));
            }
            if ($normalizedItem['unit_name'] === '') {
                $normalizedItem['unit_name'] = 'unit';
            }
            $normalizedItems[] = $normalizedItem;
        }

        return $normalizedItems;
    }

    private function hasLegacyManagedAssetItemShape(array $row): bool
    {
        foreach (['asset_name', 'category_id', 'subcategory_name', 'quantity', 'unit_name', 'acquisition_cost_raw', 'location', 'supplier_name', 'description'] as $key) {
            if (array_key_exists($key, $row)) {
                return true;
            }
        }

        return false;
    }

    private function managedAssetItemsAreEmpty(array $items): bool
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            foreach (['asset_name', 'category_id', 'subcategory_name', 'acquisition_cost_raw', 'location', 'supplier_name', 'description'] as $key) {
                if (trim((string) ($item[$key] ?? '')) !== '') {
                    return false;
                }
            }
            if ((float) $this->normalizeDecimalNumber((string) ($item['quantity'] ?? '1')) !== 1.0) {
                return false;
            }
        }

        return true;
    }

    private function validateManagedAssetForm(array $line, array $headerInput, ?array $account, int $rowNumber): array
    {
        $assetForm = is_array($line['asset_form'] ?? null) ? $line['asset_form'] : [];
        $enabled = (string) ($assetForm['enabled'] ?? '0') === '1';
        if (!$enabled) {
            return [[], ['enabled' => false, 'items' => []]];
        }

        $errors = [];
        $debitCents = $this->moneyToCents((string) ($line['debit_raw'] ?? '0'));
        if (($debitCents ?? 0) <= 0) {
            $errors[] = 'Form aset pada baris jurnal #' . $rowNumber . ' hanya bisa dipakai pada baris debit dengan nilai perolehan.';
        }

        $items = $this->normalizeManagedAssetItemsForValidation($assetForm, $line, $headerInput, $account);
        if ($items === []) {
            $errors[] = 'Form aset pada baris jurnal #' . $rowNumber . ' minimal harus memiliki satu item aset.';
            return [$errors, ['enabled' => true, 'items' => []]];
        }

        if ((int) ($line['asset_id'] ?? 0) > 0 && count($items) > 1) {
            $errors[] = 'Baris jurnal #' . $rowNumber . ' tidak bisa menautkan satu referensi aset lama untuk lebih dari satu item aset baru.';
        }

        $normalizedItems = [];
        $totalCost = 0.0;
        foreach ($items as $itemIndex => $item) {
            $itemNumber = $itemIndex + 1;
            $category = null;
            $categoryId = (int) ($item['category_id'] ?? 0);
            if ($categoryId > 0) {
                $category = $this->assetModel()->findCategoryById($categoryId);
            }
            if (!$category && !empty($line['coa_id'])) {
                $category = $this->assetModel()->findActiveCategoryByAssetCoaId((int) $line['coa_id']);
                if ($category) {
                    $categoryId = (int) ($category['id'] ?? 0);
                }
            }
            if (!$category || (int) ($category['is_active'] ?? 1) !== 1) {
                $errors[] = 'Kategori aset item #' . $itemNumber . ' pada baris jurnal #' . $rowNumber . ' wajib dipilih dan harus aktif.';
            }

            $assetName = trim((string) ($item['asset_name'] ?? ''));
            if ($assetName === '') {
                $assetName = trim((string) (($line['line_description'] ?? '') !== '' ? ($line['line_description'] ?? '') : (($account['account_name'] ?? '') !== '' ? ($account['account_name'] ?? '') : ($headerInput['description'] ?? ''))));
            }
            if ($assetName === '' || mb_strlen($assetName) < 3 || mb_strlen($assetName) > 160) {
                $errors[] = 'Nama aset item #' . $itemNumber . ' pada baris jurnal #' . $rowNumber . ' harus 3 sampai 160 karakter.';
            }

            $quantity = $this->normalizeDecimalNumber((string) ($item['quantity'] ?? '1'));
            if ($quantity <= 0) {
                $errors[] = 'Jumlah aset item #' . $itemNumber . ' pada baris jurnal #' . $rowNumber . ' harus lebih besar dari 0.';
            }

            $unitName = trim((string) ($item['unit_name'] ?? ''));
            if ($unitName === '') {
                $unitName = 'unit';
            }
            if (mb_strlen($unitName) > 30) {
                $errors[] = 'Satuan aset item #' . $itemNumber . ' pada baris jurnal #' . $rowNumber . ' maksimal 30 karakter.';
            }

            $acquisitionCost = $this->normalizeDecimalNumber((string) ($item['acquisition_cost_raw'] ?? ''));
            if (count($items) === 1 && $acquisitionCost <= 0) {
                $acquisitionCost = ((int) ($debitCents ?? 0)) / 100;
            }
            if ($acquisitionCost <= 0) {
                $errors[] = 'Total nilai perolehan item #' . $itemNumber . ' pada baris jurnal #' . $rowNumber . ' harus lebih besar dari 0.';
            }

            $subcategoryName = trim((string) ($item['subcategory_name'] ?? ''));
            if (mb_strlen($subcategoryName) > 120) {
                $errors[] = 'Subkategori item #' . $itemNumber . ' pada baris jurnal #' . $rowNumber . ' maksimal 120 karakter.';
            }

            $location = trim((string) ($item['location'] ?? ''));
            if (mb_strlen($location) > 150) {
                $errors[] = 'Lokasi item #' . $itemNumber . ' pada baris jurnal #' . $rowNumber . ' maksimal 150 karakter.';
            }

            $supplierName = trim((string) ($item['supplier_name'] ?? ''));
            if (mb_strlen($supplierName) > 150) {
                $errors[] = 'Sumber perolehan item #' . $itemNumber . ' pada baris jurnal #' . $rowNumber . ' maksimal 150 karakter.';
            }

            $description = trim((string) ($item['description'] ?? ''));
            if (mb_strlen($description) > 1000) {
                $errors[] = 'Deskripsi item #' . $itemNumber . ' pada baris jurnal #' . $rowNumber . ' maksimal 1000 karakter.';
            }

            $normalizedItems[] = [
                'asset_name' => $assetName,
                'category_id' => $categoryId > 0 ? $categoryId : null,
                'subcategory_name' => $subcategoryName,
                'quantity' => $quantity,
                'unit_name' => $unitName,
                'acquisition_cost' => $acquisitionCost,
                'acquisition_cost_raw' => $this->formatMoneyForInput($acquisitionCost),
                'location' => $location,
                'supplier_name' => $supplierName,
                'description' => $description,
            ];
            $totalCost += $acquisitionCost;
        }

        $lineDebit = ((int) ($debitCents ?? 0)) / 100;
        if ($lineDebit > 0 && abs($totalCost - $lineDebit) >= 0.005) {
            $errors[] = 'Total nilai semua item aset pada baris jurnal #' . $rowNumber . ' harus sama dengan nilai debit baris tersebut.';
        }

        return [$errors, [
            'enabled' => true,
            'items' => $normalizedItems,
        ]];
    }

    private function syncManagedAssetsFromJournal(int $journalId, array $header, array $lines, int $userId): array
    {
        $summary = ['created' => 0, 'updated' => 0];
        $offsetCoaId = $this->detectJournalOffsetCoaId($lines);

        foreach ($lines as $index => $line) {
            $assetForm = is_array($line['asset_form'] ?? null) ? $line['asset_form'] : [];
            $lineNo = $index + 1;
            if (!(bool) ($assetForm['enabled'] ?? false)) {
                $this->assetModel()->purgeManagedJournalAssetsByLineExceptCodes($journalId, $lineNo, [], $userId);
                $existingAssetId = (int) ($line['asset_id'] ?? 0);
                if ($existingAssetId > 0) {
                    $existingAsset = $this->assetModel()->findAssetById($existingAssetId);
                    if ($existingAsset && (int) ($existingAsset['linked_journal_id'] ?? 0) === $journalId) {
                        $this->model()->updateLineAssetReference($journalId, $lineNo, null);
                    }
                }
                continue;
            }
            $items = is_array($assetForm['items'] ?? null) ? $assetForm['items'] : [];
            $existingAssetId = (int) ($line['asset_id'] ?? 0);
            $existingSelectedAsset = $existingAssetId > 0 ? $this->assetModel()->findAssetById($existingAssetId) : null;
            if ($existingAssetId > 0 && !$existingSelectedAsset) {
                throw new RuntimeException('Aset pada baris jurnal #' . $lineNo . ' tidak ditemukan saat sinkronisasi.');
            }
            if ($existingSelectedAsset && (int) ($existingSelectedAsset['linked_journal_id'] ?? 0) > 0 && (int) ($existingSelectedAsset['linked_journal_id'] ?? 0) !== $journalId) {
                throw new RuntimeException('Aset pada baris jurnal #' . $lineNo . ' sudah tertaut ke jurnal lain sehingga tidak aman diperbarui dari form jurnal ini.');
            }

            $managedAssets = $this->assetModel()->getManagedJournalAssetsByLine($journalId, $lineNo);
            $managedAssetsByCode = [];
            foreach ($managedAssets as $managedAsset) {
                $managedAssetsByCode[(string) ($managedAsset['asset_code'] ?? '')] = $managedAsset;
            }

            $keepCodes = [];
            $savedIds = [];
            foreach ($items as $itemIndex => $assetItem) {
                $itemNo = $itemIndex + 1;
                $assetCode = $this->buildManagedAssetCode($journalId, $lineNo, $itemNo);
                $existingAsset = $managedAssetsByCode[$assetCode] ?? null;
                if (!$existingAsset && count($items) === 1 && $existingSelectedAsset) {
                    $existingAsset = $existingSelectedAsset;
                    $assetCode = (string) ($existingSelectedAsset['asset_code'] ?? $assetCode);
                }
                $keepCodes[] = $assetCode;

                $category = $this->assetModel()->findCategoryById((int) ($assetItem['category_id'] ?? 0));
                if (!$category) {
                    throw new RuntimeException('Kategori aset item #' . $itemNo . ' pada baris jurnal #' . $lineNo . ' tidak ditemukan saat sinkronisasi.');
                }

                $depreciationAllowed = (int) ($category['depreciation_allowed'] ?? 1) === 1;
                $assetPayload = [
                    'asset_code' => $assetCode,
                    'asset_name' => (string) ($assetItem['asset_name'] ?? ''),
                    'entry_mode' => 'ACQUISITION',
                    'category_id' => (int) ($assetItem['category_id'] ?? 0),
                    'subcategory_name' => (string) ($assetItem['subcategory_name'] ?? ''),
                    'business_unit_id' => !empty($header['business_unit_id']) ? (int) $header['business_unit_id'] : null,
                    'quantity' => (float) ($assetItem['quantity'] ?? 1),
                    'unit_name' => (string) ($assetItem['unit_name'] ?? 'unit'),
                    'acquisition_date' => (string) ($header['journal_date'] ?? date('Y-m-d')),
                    'acquisition_cost' => (float) ($assetItem['acquisition_cost'] ?? 0),
                    'opening_as_of_date' => null,
                    'opening_accumulated_depreciation' => 0,
                    'residual_value' => 0,
                    'useful_life_months' => $depreciationAllowed ? (!empty($category['default_useful_life_months']) ? (int) $category['default_useful_life_months'] : 36) : null,
                    'depreciation_method' => (string) ($category['default_depreciation_method'] ?? 'STRAIGHT_LINE'),
                    'depreciation_start_date' => $depreciationAllowed ? (string) ($header['journal_date'] ?? date('Y-m-d')) : null,
                    'depreciation_allowed' => $depreciationAllowed,
                    'offset_coa_id' => $offsetCoaId,
                    'location' => (string) ($assetItem['location'] ?? ''),
                    'supplier_name' => (string) ($assetItem['supplier_name'] ?? ''),
                    'source_of_funds' => 'HASIL_USAHA',
                    'funding_source_detail' => '',
                    'reference_no' => (string) (($header['reference_no'] ?? '') !== '' ? $header['reference_no'] : ($header['journal_no'] ?? '')),
                    'linked_journal_id' => $journalId,
                    'condition_status' => $existingAsset ? (string) ($existingAsset['condition_status'] ?? 'GOOD') : 'GOOD',
                    'asset_status' => $existingAsset ? (string) ($existingAsset['asset_status'] ?? 'ACTIVE') : 'ACTIVE',
                    'acquisition_sync_status' => 'POSTED',
                    'is_active' => $existingAsset ? (int) ($existingAsset['is_active'] ?? 1) : 1,
                    'description' => (string) ($assetItem['description'] ?? ''),
                    'notes' => $this->buildManagedAssetNote($journalId, $lineNo, $itemNo, (string) ($header['journal_no'] ?? '')),
                ];

                if ($existingAsset) {
                    $assetId = (int) ($existingAsset['id'] ?? 0);
                    $this->assetModel()->updateAsset($assetId, $assetPayload, $userId);
                    $savedIds[] = $assetId;
                    $summary['updated']++;
                    continue;
                }

                $createdAssetId = $this->assetModel()->createAsset($assetPayload, $userId);
                $savedIds[] = $createdAssetId;
                $summary['created']++;
            }

            $this->assetModel()->purgeManagedJournalAssetsByLineExceptCodes($journalId, $lineNo, $keepCodes, $userId);
            $this->model()->updateLineAssetReference($journalId, $lineNo, count($savedIds) === 1 ? (int) $savedIds[0] : null);
        }

        return $summary;
    }

    private function detectJournalOffsetCoaId(array $lines): ?int
    {
        $bestCoaId = null;
        $bestCredit = 0.0;
        foreach ($lines as $line) {
            $credit = (float) ($line['credit'] ?? 0);
            if ($credit > $bestCredit && (int) ($line['coa_id'] ?? 0) > 0) {
                $bestCredit = $credit;
                $bestCoaId = (int) $line['coa_id'];
            }
        }

        return $bestCoaId;
    }

    private function buildManagedAssetCode(int $journalId, int $lineNo, int $itemNo): string
    {
        return 'JFA-' . $journalId . '-' . str_pad((string) $lineNo, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) $itemNo, 2, '0', STR_PAD_LEFT);
    }

    private function buildManagedAssetNote(int $journalId, int $lineNo, int $itemNo, string $journalNo): string
    {
        $journalLabel = trim($journalNo) !== '' ? $journalNo : ('#' . $journalId);
        return '[JOURNAL-FORM-ASSET:' . $journalId . ':' . $lineNo . ':' . $itemNo . '] Dibuat / diperbarui dari jurnal ' . $journalLabel . ' baris #' . $lineNo . ' item #' . $itemNo . '.';
    }

    private function normalizeManagedAssetItemsForValidation(array $assetForm, array $line, array $headerInput, ?array $account): array
    {
        $rawItems = is_array($assetForm['items'] ?? null) ? $assetForm['items'] : [];
        $normalized = [];
        foreach ($rawItems as $item) {
            $candidate = array_merge($this->defaultManagedAssetFormItem(), is_array($item) ? $item : []);
            $hasAnyValue = false;
            foreach (['asset_name', 'category_id', 'subcategory_name', 'acquisition_cost_raw', 'location', 'supplier_name', 'description'] as $key) {
                if (trim((string) ($candidate[$key] ?? '')) !== '') {
                    $hasAnyValue = true;
                    break;
                }
            }
            if (!$hasAnyValue) {
                $quantity = $this->normalizeDecimalNumber((string) ($candidate['quantity'] ?? '1'));
                if ($quantity !== 1.0) {
                    $hasAnyValue = true;
                }
            }
            if (!$hasAnyValue) {
                continue;
            }

            if (trim((string) ($candidate['asset_name'] ?? '')) === '') {
                $candidate['asset_name'] = trim((string) (($line['line_description'] ?? '') !== '' ? ($line['line_description'] ?? '') : (($account['account_name'] ?? '') !== '' ? ($account['account_name'] ?? '') : ($headerInput['description'] ?? ''))));
            }
            if (trim((string) ($candidate['category_id'] ?? '')) === '' && !empty($line['coa_id'])) {
                $category = $this->assetModel()->findActiveCategoryByAssetCoaId((int) $line['coa_id']);
                if ($category) {
                    $candidate['category_id'] = (string) ($category['id'] ?? '');
                }
            }

            $normalized[] = $candidate;
        }

        return $normalized;
    }

    private function decodeManagedAssetItemsJson(string $json): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = array_merge($this->defaultManagedAssetFormItem(), [
                'asset_name' => trim((string) ($item['asset_name'] ?? '')),
                'category_id' => trim((string) ($item['category_id'] ?? '')),
                'subcategory_name' => trim((string) ($item['subcategory_name'] ?? '')),
                'quantity' => trim((string) ($item['quantity'] ?? '1')),
                'unit_name' => trim((string) ($item['unit_name'] ?? 'unit')),
                'acquisition_cost_raw' => trim((string) ($item['acquisition_cost_raw'] ?? '')),
                'location' => trim((string) ($item['location'] ?? '')),
                'supplier_name' => trim((string) ($item['supplier_name'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
            ]);
        }

        return $items;
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

    private function normalizeDecimalNumber(string $value): float
    {
        $value = trim(str_replace(["Â ", ' ', 'Rp', 'rp'], '', $value));
        if ($value === '') {
            return 0.0;
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
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
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
