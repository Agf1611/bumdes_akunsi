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
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            render_error_page(500, 'Daftar akun belum dapat dibuka. Pastikan tabel COA sudah dibuat dengan benar.', $e);
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
            flash('error', 'Data akun gagal disimpan. Silakan periksa kembali isian formulir Anda.');
            $this->redirect($id === null ? '/coa/create' : '/coa/edit?id=' . $id);
        }
    }

    private function validate(array $input, ?int $currentId = null): array
    {
        $errors = [];
        $types = coa_account_types();
        $categories = coa_categories_for_type($input['account_type']);

        if ($input['account_code'] === '') {
            $errors[] = 'Kode akun wajib diisi.';
        } elseif (!preg_match('/^[A-Z0-9.\-]{1,30}$/', $input['account_code'])) {
            $errors[] = 'Kode akun hanya boleh berisi huruf besar, angka, titik, atau tanda hubung dengan panjang maksimal 30 karakter.';
        } elseif ($this->model()->findByCode($input['account_code'], $currentId)) {
            $errors[] = 'Kode akun sudah dipakai. Gunakan kode akun yang unik.';
        }

        if ($input['account_name'] === '') {
            $errors[] = 'Nama akun wajib diisi.';
        } elseif (mb_strlen($input['account_name']) < 3 || mb_strlen($input['account_name']) > 150) {
            $errors[] = 'Nama akun harus 3 sampai 150 karakter.';
        }

        if (!isset($types[$input['account_type']])) {
            $errors[] = 'Tipe akun tidak valid.';
        }

        if ($categories === [] || !isset($categories[$input['account_category']])) {
            $errors[] = 'Kategori akun tidak valid untuk tipe akun yang dipilih.';
        }

        if ($input['parent_id'] !== null) {
            $parent = $this->model()->findById((int) $input['parent_id']);
            if (!$parent) {
                $errors[] = 'Parent akun tidak ditemukan.';
            } else {
                if ((int) $parent['is_header'] !== 1) {
                    $errors[] = 'Parent akun harus berupa akun header.';
                }
                if ((int) $parent['is_active'] !== 1) {
                    $errors[] = 'Parent akun harus dalam status aktif.';
                }
                if ((string) $parent['account_type'] !== $input['account_type']) {
                    $errors[] = 'Tipe akun parent harus sama dengan tipe akun anak.';
                }
                if (!$this->model()->canSetAsParent((int) $parent['id'], $currentId)) {
                    $errors[] = 'Parent akun tidak valid karena menyebabkan struktur akun menjadi melingkar.';
                }
            }
        }

        if ($currentId !== null) {
            $existing = $this->model()->findById($currentId);
            if ($existing && (int) $existing['is_header'] === 1 && !$input['is_header'] && $this->model()->hasChildren($currentId)) {
                $errors[] = 'Akun header yang masih memiliki turunan tidak boleh diubah menjadi akun detail.';
            }
        }

        return $errors;
    }

    private function showForm(string $title, ?array $account): void
    {
        try {
            $currentId = $account ? (int) $account['id'] : null;
            $defaultParentId = '';
            if ($account !== null && array_key_exists('parent_id', $account) && $account['parent_id'] !== null) {
                $defaultParentId = (string) $account['parent_id'];
            }

            $formData = [
                'account_code' => old('account_code', (string) ($account['account_code'] ?? '')),
                'account_name' => old('account_name', (string) ($account['account_name'] ?? '')),
                'account_type' => old('account_type', (string) ($account['account_type'] ?? 'ASSET')),
                'account_category' => old('account_category', (string) ($account['account_category'] ?? 'CURRENT_ASSET')),
                'parent_id' => old('parent_id', $defaultParentId),
                'is_header' => old('is_header', isset($account['is_header']) ? ((int) $account['is_header'] === 1 ? '1' : '0') : '0'),
                'is_active' => old('is_active', isset($account['is_active']) ? ((int) $account['is_active'] === 1 ? '1' : '0') : '1'),
            ];

            $this->view('coa/views/form', [
                'title' => $title,
                'account' => $account,
                'formData' => $formData,
                'types' => coa_account_types(),
                'categoriesMap' => coa_categories_by_type(),
                'parentOptions' => $this->model()->getParentOptions($currentId),
                'errorMessage' => get_flash('error'),
                'successMessage' => get_flash('success'),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            render_error_page(500, 'Form akun belum dapat dibuka. Pastikan tabel COA sudah dibuat dengan benar.', $e);
        }
    }
}
