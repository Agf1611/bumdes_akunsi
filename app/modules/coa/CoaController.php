<?php

declare(strict_types=1);

final class CoaController extends Controller
{
    private function model(): CoaModel
    {
        return new CoaModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            $filters = [
                'search' => trim((string) get_query('search', '')),
                'type' => trim((string) get_query('type', '')),
            ];

            $this->view('coa/views/index', [
                'title' => 'Chart of Accounts',
                'accounts' => $this->model()->getList($filters),
                'filters' => $filters,
                'types' => coa_account_types(),
                'totalAccounts' => $this->model()->countAll(),
                'errorMessage' => get_flash('error'),
                'successMessage' => get_flash('success'),
                'globalCoaCount' => coa_default_global_account_count(),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            render_error_page(500, 'Daftar akun belum dapat dibuka. Pastikan tabel COA sudah dibuat dengan benar.', $e);
        }
    }

    public function export(): void
    {
        try {
            $filters = [
                'search' => trim((string) get_query('search', '')),
                'type' => trim((string) get_query('type', '')),
            ];
            $rows = $this->model()->getList($filters);

            $exportRows = [
                ['account_code', 'account_name', 'account_type', 'account_category', 'parent_code', 'is_header', 'is_active'],
            ];
            foreach ($rows as $row) {
                $exportRows[] = [
                    (string) ($row['account_code'] ?? ''),
                    (string) ($row['account_name'] ?? ''),
                    (string) ($row['account_type'] ?? ''),
                    (string) ($row['account_category'] ?? ''),
                    (string) ($row['parent_code'] ?? ''),
                    ((int) (($row['is_header'] ?? 0) ? 1 : 0)) === 1 ? '1' : '0',
                    ((int) (($row['is_active'] ?? 0) ? 1 : 0)) === 1 ? '1' : '0',
                ];
            }

            $writer = new XlsxWriter();
            $writer->output('coa_export_' . date('Ymd_His') . '.xlsx', $exportRows);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Export COA gagal diproses.');
            $this->redirect('/coa');
        }
    }

    public function create(): void
    {
        $this->showForm('Tambah Akun', null);
    }

    public function edit(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID akun tidak valid.');
            $this->redirect('/coa');
        }

        $account = $this->model()->findById($id);
        if (!$account) {
            flash('error', 'Akun yang ingin diubah tidak ditemukan.');
            $this->redirect('/coa');
        }

        $this->showForm('Edit Akun', $account);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID akun tidak valid untuk proses pembaruan.');
            $this->redirect('/coa');
        }

        $existing = $this->model()->findById($id);
        if (!$existing) {
            flash('error', 'Akun yang ingin diubah tidak ditemukan.');
            $this->redirect('/coa');
        }

        $this->save($id);
    }

    public function toggleActive(): void
    {
        $id = (int) get_query('id', 0);
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        if ($id <= 0) {
            flash('error', 'ID akun tidak valid.');
            $this->redirect('/coa');
        }

        try {
            $account = $this->model()->findById($id);
            if (!$account) {
                flash('error', 'Akun tidak ditemukan.');
                $this->redirect('/coa');
            }

            $newStatus = ((int) $account['is_active']) !== 1;
            if ($newStatus === false && $this->model()->hasChildren($id)) {
                flash('error', 'Akun yang masih memiliki akun turunan tidak boleh dinonaktifkan.');
                $this->redirect('/coa');
            }

            $this->model()->setActive($id, $newStatus);
            flash('success', $newStatus ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.');
            $this->redirect('/coa');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Status akun gagal diperbarui.');
            $this->redirect('/coa');
        }
    }

    public function seedGlobalDefaults(): void
    {
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        try {
            $result = $this->model()->seedDefaultGlobalAccounts();
            $inserted = (int) ($result['inserted'] ?? 0);
            $skipped = (int) ($result['skipped'] ?? 0);

            if ($inserted > 0) {
                flash('success', 'COA standar KepmenDesa 136/2022 berhasil ditambahkan. Akun baru: ' . $inserted . '. Akun yang sudah ada dilewati: ' . $skipped . '.');
            } else {
                flash('success', 'COA standar KepmenDesa 136/2022 sudah lengkap. Tidak ada akun baru yang perlu ditambahkan.');
            }
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'COA standar KepmenDesa 136/2022 gagal ditambahkan. Silakan periksa log aplikasi.');
        }

        $this->redirect('/coa');
    }

    public function delete(): void
    {
        $id = (int) get_query('id', 0);
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        if ($id <= 0) {
            flash('error', 'ID akun tidak valid.');
            $this->redirect('/coa');
        }

        try {
            $account = $this->model()->findById($id);
            if (!$account) {
                flash('error', 'Akun tidak ditemukan.');
                $this->redirect('/coa');
            }

            [$canDelete, $reason] = $this->model()->canDelete($id);
            if (!$canDelete) {
                flash('error', $reason);
                $this->redirect('/coa');
            }

            $this->model()->delete($id);
            flash('success', 'Akun berhasil dihapus.');
            $this->redirect('/coa');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Akun gagal dihapus. Silakan periksa relasi data terlebih dahulu.');
            $this->redirect('/coa');
        }
    }

    private function save(?int $id): void
    {
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        $input = [
            'account_code' => strtoupper(trim((string) post('account_code'))),
            'account_name' => trim((string) post('account_name')),
            'account_type' => trim((string) post('account_type')),
            'account_category' => trim((string) post('account_category')),
            'parent_id' => post('parent_id') !== null && post('parent_id') !== '' ? (int) post('parent_id') : null,
            'is_header' => (string) post('is_header', '0') === '1',
            'is_active' => (string) post('is_active', '1') === '1',
        ];

        with_old_input([
            'account_code' => $input['account_code'],
            'account_name' => $input['account_name'],
            'account_type' => $input['account_type'],
            'account_category' => $input['account_category'],
            'parent_id' => $input['parent_id'] === null ? '' : (string) $input['parent_id'],
            'is_header' => $input['is_header'] ? '1' : '0',
            'is_active' => $input['is_active'] ? '1' : '0',
        ]);

        $errors = $this->validate($input, $id);
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect($id === null ? '/coa/create' : '/coa/edit?id=' . $id);
        }

        try {
            if ($id === null) {
                $this->model()->create($input);
                flash('success', 'Akun baru berhasil ditambahkan.');
            } else {
                $this->model()->update($id, $input);
                flash('success', 'Data akun berhasil diperbarui.');
            }

            clear_old_input();
            $this->redirect('/coa');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Data akun gagal disimpan. Silakan coba lagi.');
            $this->redirect($id === null ? '/coa/create' : '/coa/edit?id=' . $id);
        }
    }

    private function showForm(string $title, ?array $account): void
    {
        try {
            $formData = [
                'account_code' => old('account_code', (string) ($account['account_code'] ?? '')),
                'account_name' => old('account_name', (string) ($account['account_name'] ?? '')),
                'account_type' => old('account_type', (string) ($account['account_type'] ?? 'ASSET')),
                'account_category' => old('account_category', (string) ($account['account_category'] ?? 'CURRENT_ASSET')),
                'parent_id' => old('parent_id', isset($account['parent_id']) && $account['parent_id'] !== null ? (string) $account['parent_id'] : ''),
                'is_header' => old('is_header', isset($account['is_header']) ? ((int) $account['is_header'] === 1 ? '1' : '0') : '0'),
                'is_active' => old('is_active', isset($account['is_active']) ? ((int) $account['is_active'] === 1 ? '1' : '0') : '1'),
            ];

            $categoriesMap = coa_categories_by_type();
            if (!isset($categoriesMap[$formData['account_type']])) {
                $formData['account_type'] = 'ASSET';
            }

            $fallbackCategories = $categoriesMap[$formData['account_type']] ?? [];
            if ($fallbackCategories === []) {
                $fallbackCategories = coa_categories_for_type('ASSET');
                $formData['account_type'] = 'ASSET';
            }

            if (!isset($fallbackCategories[$formData['account_category']])) {
                $formData['account_category'] = (string) array_key_first($fallbackCategories);
            }

            $this->view('coa/views/form', [
                'title' => $title,
                'account' => $account,
                'formData' => $formData,
                'types' => coa_account_types(),
                'categoriesMap' => $categoriesMap,
                'parentOptions' => $this->model()->getParentOptions($account['id'] ?? null),
                'errorMessage' => get_flash('error'),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            render_error_page(500, 'Form akun belum dapat ditampilkan.', $e);
        }
    }

    private function validate(array $input, ?int $id): array
    {
        $errors = [];

        if ($input['account_code'] === '') {
            $errors[] = 'Kode akun wajib diisi.';
        } elseif (!preg_match('/^[A-Z0-9.\-]{1,30}$/', $input['account_code'])) {
            $errors[] = 'Kode akun hanya boleh berisi huruf besar, angka, titik, atau tanda hubung.';
        }

        $existingCode = $this->model()->findByCode($input['account_code'], $id);
        if ($existingCode) {
            $errors[] = 'Kode akun sudah digunakan oleh akun lain.';
        }

        if ($input['account_name'] === '') {
            $errors[] = 'Nama akun wajib diisi.';
        }

        $types = coa_account_types();
        if (!isset($types[$input['account_type']])) {
            $errors[] = 'Tipe akun tidak valid.';
        }

        $categories = coa_categories_for_type($input['account_type']);
        if ($categories === [] || !isset($categories[$input['account_category']])) {
            $errors[] = 'Kategori akun tidak sesuai dengan tipe akun yang dipilih.';
        }

        if ($input['parent_id'] !== null) {
            $parent = $this->model()->findById((int) $input['parent_id']);
            if (!$parent) {
                $errors[] = 'Akun parent tidak ditemukan.';
            } elseif ((int) $parent['is_header'] !== 1) {
                $errors[] = 'Akun parent harus merupakan akun header.';
            } elseif ((int) $parent['is_active'] !== 1) {
                $errors[] = 'Akun parent harus dalam status aktif.';
            } elseif (!$this->model()->canSetAsParent((int) $input['parent_id'], $id)) {
                $errors[] = 'Relasi parent akun menyebabkan siklus struktur akun.';
            }
        }

        if ($input['is_header'] && $id !== null && $this->model()->isUsedInJournal($id)) {
            $errors[] = 'Akun yang sudah dipakai pada jurnal tidak boleh diubah menjadi header.';
        }

        if (!$input['is_active'] && $id !== null && $this->model()->hasChildren($id)) {
            $errors[] = 'Akun yang memiliki akun turunan tidak boleh dinonaktifkan.';
        }

        return $errors;
    }
}
