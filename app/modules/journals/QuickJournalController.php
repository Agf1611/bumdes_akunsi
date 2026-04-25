<?php

declare(strict_types=1);

final class QuickJournalController extends Controller
{
    private function model(): JournalModel
    {
        return new JournalModel(Database::getInstance(db_config()));
    }

    private function attachmentService(): JournalAttachmentService
    {
        return new JournalAttachmentService();
    }

    public function create(): void
    {
        $templateKey = trim((string) get_query('template', 'cash_in'));
        $this->showForm($templateKey, null, []);
    }

    public function store(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman.');
            return;
        }

        $input = $this->readInput();
        $preview = $this->buildPreview($input);
        $errors = $preview['errors'];

        with_old_input($input);

        if ((string) post('action', 'preview') === 'preview' || $errors !== []) {
            if ($errors !== []) {
                flash('error', implode(' ', $errors));
            }
            $this->showForm($input['template_key'], $preview, $input);
            return;
        }

        $storedFile = null;
        try {
            $payload = [
                'journal_date' => $input['journal_date'],
                'description' => $input['description'],
                'period_id' => (int) $input['period_id'],
                'business_unit_id' => $input['business_unit_id'] !== '' ? (int) $input['business_unit_id'] : null,
                'print_template' => $preview['receipt'] !== null ? 'receipt' : 'standard',
                'total_debit' => number_format((float) $input['amount'], 2, '.', ''),
                'total_credit' => number_format((float) $input['amount'], 2, '.', ''),
                'created_by' => (int) (Auth::user()['id'] ?? 0),
                'updated_by' => (int) (Auth::user()['id'] ?? 0),
            ];

            $journalId = $this->model()->create($payload, $preview['lines'], $preview['receipt']);

            if (($preview['attachment_enabled'] ?? false) && isset($_FILES['attachment_file']) && (int) ($_FILES['attachment_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $storedFile = $this->attachmentService()->storeUploadedFile($_FILES['attachment_file']);
                $this->model()->createAttachment([
                    'journal_id' => $journalId,
                    'attachment_title' => $input['attachment_title'] !== '' ? $input['attachment_title'] : ('Lampiran ' . $input['description']),
                    'attachment_notes' => 'Diunggah dari transaksi cepat.',
                    'original_name' => $storedFile['original_name'],
                    'stored_name' => $storedFile['stored_name'],
                    'stored_file_path' => $storedFile['stored_file_path'],
                    'mime_type' => $storedFile['mime_type'],
                    'file_ext' => $storedFile['file_ext'],
                    'file_size' => $storedFile['file_size'],
                    'uploaded_by' => (int) (Auth::user()['id'] ?? 0),
                ]);
            }

            $this->trackQuickTemplateUsage($input['template_key']);
            audit_log('Jurnal Cepat', 'create', 'Transaksi cepat berhasil disimpan sebagai jurnal.', [
                'entity_type' => 'journal',
                'entity_id' => (string) $journalId,
                'context' => [
                    'template_key' => $input['template_key'],
                    'amount' => $input['amount'],
                    'debit_account_id' => $input['debit_account_id'],
                    'credit_account_id' => $input['credit_account_id'],
                ],
            ]);
            clear_old_input();
            flash('success', 'Transaksi cepat berhasil disimpan sebagai jurnal baru.');
            $this->redirect('/journals/detail?id=' . $journalId);
        } catch (Throwable $e) {
            if (is_array($storedFile) && !empty($storedFile['stored_name'])) {
                JournalAttachmentService::deleteStoredFile((string) $storedFile['stored_name']);
            }
            log_error($e);
            flash('error', 'Transaksi cepat gagal disimpan. ' . $e->getMessage());
            $this->showForm($input['template_key'], $preview, $input);
        }
    }

    public function toggleFavoriteTemplate(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman.');
            return;
        }

        $templateKey = trim((string) post('template_key', ''));
        $template = journal_quick_template_data($templateKey);
        if (!is_array($template)) {
            flash('error', 'Template transaksi cepat tidak dikenali.');
            $this->redirect('/journals/quick');
        }

        $favorites = workspace_preference_get('favorite_quick_transaction_templates', []);
        if (!is_array($favorites)) {
            $favorites = [];
        }

        $exists = false;
        $updated = [];
        foreach ($favorites as $item) {
            if ((string) ($item['template_key'] ?? '') === $templateKey) {
                $exists = true;
                continue;
            }
            $updated[] = $item;
        }

        if (!$exists) {
            array_unshift($updated, [
                'template_key' => $templateKey,
                'label' => (string) ($template['template_name'] ?? $templateKey),
                'saved_at' => date('Y-m-d H:i:s'),
            ]);
        }

        workspace_preference_put('favorite_quick_transaction_templates', array_slice($updated, 0, 8));
        flash('success', $exists ? 'Template favorit transaksi cepat dihapus.' : 'Template transaksi cepat disimpan ke favorit.');
        $this->redirect('/journals/quick?template=' . urlencode($templateKey));
    }

    private function readInput(): array
    {
        return [
            'template_key' => trim((string) post('template_key', 'cash_in')),
            'journal_date' => trim((string) post('journal_date', date('Y-m-d'))),
            'period_id' => trim((string) post('period_id', (string) (current_accounting_period()['id'] ?? ''))),
            'business_unit_id' => trim((string) post('business_unit_id', '')),
            'description' => trim((string) post('description', '')),
            'amount' => trim((string) post('amount', '0')),
            'debit_account_id' => trim((string) post('debit_account_id', '')),
            'credit_account_id' => trim((string) post('credit_account_id', '')),
            'party_name' => trim((string) post('party_name', '')),
            'reference_no' => trim((string) post('reference_no', '')),
            'attachment_title' => trim((string) post('attachment_title', '')),
        ];
    }

    private function showForm(string $templateKey, ?array $preview, array $oldInput): void
    {
        $template = journal_quick_template_data($templateKey) ?? journal_quick_template_data('cash_in');
        $accounts = $this->model()->getAccountOptions((int) (Auth::user()['id'] ?? 0));
        $favorites = workspace_preference_get('favorite_quick_transaction_templates', []);

        $formData = [
            'template_key' => old('template_key', (string) ($oldInput['template_key'] ?? ($template['template_key'] ?? 'cash_in'))),
            'journal_date' => old('journal_date', (string) ($oldInput['journal_date'] ?? date('Y-m-d'))),
            'period_id' => old('period_id', (string) ($oldInput['period_id'] ?? (string) (current_accounting_period()['id'] ?? ''))),
            'business_unit_id' => old('business_unit_id', (string) ($oldInput['business_unit_id'] ?? '')),
            'description' => old('description', (string) ($oldInput['description'] ?? ($template['description'] ?? ''))),
            'amount' => old('amount', (string) ($oldInput['amount'] ?? '')),
            'debit_account_id' => old('debit_account_id', (string) ($oldInput['debit_account_id'] ?? '')),
            'credit_account_id' => old('credit_account_id', (string) ($oldInput['credit_account_id'] ?? '')),
            'party_name' => old('party_name', (string) ($oldInput['party_name'] ?? '')),
            'reference_no' => old('reference_no', (string) ($oldInput['reference_no'] ?? '')),
            'attachment_title' => old('attachment_title', (string) ($oldInput['attachment_title'] ?? '')),
        ];

        $this->view('journals/views/quick_form', [
            'title' => 'Transaksi Cepat',
            'template' => $template,
            'templateOptions' => journal_quick_template_options(),
            'favoriteTemplates' => is_array($favorites) ? $favorites : [],
            'formData' => $formData,
            'preview' => $preview,
            'periodOptions' => $this->model()->getOpenPeriods(),
            'unitOptions' => business_unit_options(),
            'accountOptions' => $accounts,
            'attachmentFeatureStatus' => $this->model()->getAttachmentFeatureStatus(),
        ]);
    }

    private function buildPreview(array $input): array
    {
        $errors = [];
        $template = journal_quick_template_data($input['template_key']);
        if (!is_array($template)) {
            $errors[] = 'Template transaksi cepat tidak dikenal.';
        }

        $period = $this->model()->findPeriodById((int) $input['period_id']);
        if (!$period || (string) ($period['status'] ?? '') !== 'OPEN') {
            $errors[] = 'Periode akuntansi wajib dipilih dan harus berstatus buka.';
        }

        if ($input['description'] === '') {
            $errors[] = 'Keterangan transaksi wajib diisi.';
        }

        $amount = $this->parseMoney($input['amount']);
        if ($amount <= 0) {
            $errors[] = 'Nominal transaksi harus lebih besar dari nol.';
        }

        if ((int) $input['debit_account_id'] <= 0 || (int) $input['credit_account_id'] <= 0) {
            $errors[] = 'Akun debit dan akun kredit wajib dipilih.';
        }
        if ((int) $input['debit_account_id'] > 0 && (int) $input['debit_account_id'] === (int) $input['credit_account_id']) {
            $errors[] = 'Akun debit dan kredit tidak boleh sama.';
        }

        if ($input['journal_date'] === '' || DateTimeImmutable::createFromFormat('Y-m-d', $input['journal_date']) === false) {
            $errors[] = 'Tanggal transaksi tidak valid.';
        }

        $accountMap = [];
        foreach ($this->model()->getAccountOptions((int) (Auth::user()['id'] ?? 0)) as $account) {
            $accountMap[(int) ($account['id'] ?? 0)] = $account;
        }

        $debitAccount = $accountMap[(int) $input['debit_account_id']] ?? null;
        $creditAccount = $accountMap[(int) $input['credit_account_id']] ?? null;
        if ($debitAccount === null || $creditAccount === null) {
            $errors[] = 'Akun yang dipilih tidak ditemukan di COA aktif.';
        }

        $money = number_format($amount, 2, '.', '');
        $lines = [
            [
                'coa_id' => (int) $input['debit_account_id'],
                'line_description' => 'Debit ' . ($debitAccount['account_name'] ?? 'akun debit'),
                'debit' => $money,
                'credit' => '0.00',
                'partner_id' => 0,
                'inventory_item_id' => 0,
                'raw_material_id' => 0,
                'asset_id' => 0,
                'saving_account_id' => 0,
                'cashflow_component_id' => 0,
                'entry_tag' => '',
            ],
            [
                'coa_id' => (int) $input['credit_account_id'],
                'line_description' => 'Kredit ' . ($creditAccount['account_name'] ?? 'akun kredit'),
                'debit' => '0.00',
                'credit' => $money,
                'partner_id' => 0,
                'inventory_item_id' => 0,
                'raw_material_id' => 0,
                'asset_id' => 0,
                'saving_account_id' => 0,
                'cashflow_component_id' => 0,
                'entry_tag' => '',
            ],
        ];

        $receipt = null;
        if (in_array($input['template_key'], ['cash_in', 'cash_out', 'revenue', 'expense'], true)) {
            $receipt = [
                'party_title' => in_array($input['template_key'], ['cash_in', 'revenue'], true) ? 'Diterima dari' : 'Dibayar kepada',
                'party_name' => $input['party_name'],
                'purpose' => $input['description'],
                'amount_in_words' => journal_amount_in_words($amount),
                'payment_method' => 'Tunai / Transfer',
                'reference_no' => $input['reference_no'],
                'notes' => 'Dibuat dari transaksi cepat',
            ];
        }

        return [
            'errors' => $errors,
            'amount' => $amount,
            'lines' => $lines,
            'receipt' => $receipt,
            'attachment_enabled' => (bool) ($this->model()->getAttachmentFeatureStatus()['enabled'] ?? false),
            'debit_account' => $debitAccount,
            'credit_account' => $creditAccount,
        ];
    }

    private function parseMoney(string $value): float
    {
        $normalized = str_replace(['.', ','], ['', '.'], trim($value));
        return (float) $normalized;
    }

    private function trackQuickTemplateUsage(string $templateKey): void
    {
        $recent = workspace_preference_get('recent_quick_transaction_templates', []);
        if (!is_array($recent)) {
            $recent = [];
        }

        $recent = array_values(array_filter($recent, static fn (array $item): bool => (string) ($item['template_key'] ?? '') !== $templateKey));
        array_unshift($recent, [
            'template_key' => $templateKey,
            'used_at' => date('Y-m-d H:i:s'),
            'label' => journal_quick_template_label($templateKey),
        ]);
        workspace_preference_put('recent_quick_transaction_templates', array_slice($recent, 0, 6));
    }
}
