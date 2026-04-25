<?php

declare(strict_types=1);

final class UserAccountController extends Controller
{
    private function model(): UserAccountModel
    {
        return new UserAccountModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            $search = trim((string) get_query('search', ''));
            $roleCode = trim((string) get_query('role', ''));

            $this->view('user_accounts/views/index', [
                'title' => 'Manajemen Akun Pengguna',
                'rows' => $this->model()->getList($search, $roleCode),
                'search' => $search,
                'roleCode' => $roleCode,
                'roleOptions' => $this->model()->getRoleOptions(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman akun pengguna belum dapat dibuka. Periksa koneksi database dan tabel users/roles.', $e);
        }
    }

    public function create(): void
    {
        $this->showForm('Tambah Akun Bendahara / Pimpinan', null);
    }

    public function edit(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID pengguna tidak valid.');
            $this->redirect('/user-accounts');
        }

        $row = $this->model()->findById($id);
        if (!$row) {
            flash('error', 'Akun bendahara / pimpinan tidak ditemukan.');
            $this->redirect('/user-accounts');
        }

        $this->showForm('Edit Akun Pengguna', $row);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID pengguna tidak valid.');
            $this->redirect('/user-accounts');
        }

        $row = $this->model()->findById($id);
        if (!$row) {
            flash('error', 'Akun bendahara / pimpinan tidak ditemukan.');
            $this->redirect('/user-accounts');
        }

        $this->save($id);
    }

    public function toggleActive(): void
    {
        $id = (int) get_query('id', 0);
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }

        $row = $this->model()->findById($id);
        if (!$row) {
            flash('error', 'Akun bendahara / pimpinan tidak ditemukan.');
            $this->redirect('/user-accounts');
        }

        try {
            $beforeRow = $row;
            $newStatus = ((int) ($row['is_active'] ?? 0)) !== 1;
            $this->model()->toggleActive($id, $newStatus);
            audit_log('Akun Pengguna', $newStatus ? 'activate' : 'deactivate', $newStatus ? 'Akun pengguna diaktifkan.' : 'Akun pengguna dinonaktifkan.', [
                'entity_type' => 'user',
                'entity_id' => (string) $id,
                'before' => $beforeRow,
                'after' => $this->model()->findById($id),
            ]);
            flash('success', $newStatus ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Status akun gagal diperbarui.');
        }

        $this->redirect('/user-accounts');
    }

    public function resetPassword(): void
    {
        $id = (int) get_query('id', 0);
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }

        $row = $this->model()->findById($id);
        if (!$row) {
            flash('error', 'Akun bendahara / pimpinan tidak ditemukan.');
            $this->redirect('/user-accounts');
        }

        try {
            $temporaryPassword = $this->generateTemporaryPassword();
            $this->model()->resetPassword($id, password_hash($temporaryPassword, PASSWORD_DEFAULT));
            audit_log('Akun Pengguna', 'reset_password', 'Password akun pengguna direset oleh admin.', [
                'severity' => 'warning',
                'entity_type' => 'user',
                'entity_id' => (string) $id,
                'before' => ['username' => (string) ($row['username'] ?? '')],
                'after' => ['username' => (string) ($row['username'] ?? ''), 'password_reset' => true],
            ]);
            flash('success', 'Password sementara untuk akun ' . (string) ($row['username'] ?? '') . ': ' . $temporaryPassword . '. Simpan dan kirimkan secara aman ke pengguna.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Password akun gagal direset.');
        }

        $this->redirect('/user-accounts');
    }

    private function save(?int $id): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }

        $input = [
            'full_name' => trim((string) post('full_name')),
            'username' => trim((string) post('username')),
            'role_code' => trim((string) post('role_code')),
            'password' => (string) post('password'),
            'password_confirmation' => (string) post('password_confirmation'),
            'is_active' => (string) post('is_active', '1') === '1',
            'mfa_enabled' => (string) post('mfa_enabled', '0') === '1',
            'mfa_secret' => strtoupper(trim((string) post('mfa_secret', ''))),
        ];

        with_old_input([
            'full_name' => $input['full_name'],
            'username' => $input['username'],
            'role_code' => $input['role_code'],
            'is_active' => $input['is_active'] ? '1' : '0',
            'mfa_enabled' => $input['mfa_enabled'] ? '1' : '0',
            'mfa_secret' => $input['mfa_secret'],
        ]);

        $errors = [];

        if ($input['full_name'] === '') {
            $errors[] = 'Nama lengkap wajib diisi.';
        } elseif (mb_strlen($input['full_name']) < 3 || mb_strlen($input['full_name']) > 100) {
            $errors[] = 'Nama lengkap harus 3 sampai 100 karakter.';
        }

        if ($input['username'] === '') {
            $errors[] = 'Username wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{4,50}$/', $input['username'])) {
            $errors[] = 'Username harus 4 sampai 50 karakter dan hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung.';
        } elseif ($this->model()->findByUsername($input['username'], $id)) {
            $errors[] = 'Username sudah dipakai. Gunakan username lain.';
        }

        $role = $this->model()->findRoleByCode($input['role_code']);
        if (!$role) {
            $errors[] = 'Role pengguna hanya boleh Bendahara atau Pimpinan.';
        }

        if ($id === null) {
            if ($input['password'] === '') {
                $errors[] = 'Password wajib diisi untuk akun baru.';
            } elseif (strlen($input['password']) < 8) {
                $errors[] = 'Password minimal 8 karakter.';
            }
        } elseif ($input['password'] !== '' && strlen($input['password']) < 8) {
            $errors[] = 'Password baru minimal 8 karakter.';
        }

        if ($input['password'] !== '' || $input['password_confirmation'] !== '') {
            if ($input['password'] !== $input['password_confirmation']) {
                $errors[] = 'Konfirmasi password tidak cocok.';
            }
        }

        if ($input['mfa_enabled']) {
            if ($input['mfa_secret'] === '') {
                $errors[] = 'Secret MFA wajib diisi jika MFA diaktifkan.';
            } elseif (preg_match('/^[A-Z2-7]{16,64}$/', $input['mfa_secret']) !== 1) {
                $errors[] = 'Secret MFA harus base32 16 sampai 64 karakter.';
            }
        }

        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect($id === null ? '/user-accounts/create' : '/user-accounts/edit?id=' . $id);
        }

        try {
            $beforeRow = $id !== null ? $this->model()->findById($id) : null;
            $payload = [
                'role_id' => (int) $role['id'],
                'full_name' => $input['full_name'],
                'username' => $input['username'],
                'password_hash' => $input['password'] !== '' ? password_hash($input['password'], PASSWORD_DEFAULT) : null,
                'is_active' => $input['is_active'],
                'mfa_enabled' => $input['mfa_enabled'],
                'mfa_secret' => $input['mfa_enabled'] ? $input['mfa_secret'] : '',
            ];

            if ($id === null) {
                $createdId = $this->model()->create($payload);
                audit_log('Akun Pengguna', 'create', 'Akun pengguna baru ditambahkan.', [
                    'entity_type' => 'user',
                    'entity_id' => (string) $createdId,
                    'after' => $this->model()->findById($createdId),
                ]);
                flash('success', 'Akun pengguna berhasil ditambahkan.');
            } else {
                $this->model()->update($id, $payload);
                audit_log('Akun Pengguna', 'update', 'Data akun pengguna diperbarui.', [
                    'entity_type' => 'user',
                    'entity_id' => (string) $id,
                    'before' => $beforeRow,
                    'after' => $this->model()->findById($id),
                ]);
                flash('success', 'Akun pengguna berhasil diperbarui.');
            }

            clear_old_input();
            $this->redirect('/user-accounts');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Data akun pengguna gagal disimpan.');
            $this->redirect($id === null ? '/user-accounts/create' : '/user-accounts/edit?id=' . $id);
        }
    }

    private function showForm(string $title, ?array $row): void
    {
        $formData = [
            'full_name' => old('full_name', (string) ($row['full_name'] ?? '')),
            'username' => old('username', (string) ($row['username'] ?? '')),
            'role_code' => old('role_code', (string) ($row['role_code'] ?? 'bendahara')),
            'is_active' => old('is_active', isset($row['is_active']) && (int) $row['is_active'] === 1 ? '1' : '1'),
            'mfa_enabled' => old('mfa_enabled', isset($row['mfa_enabled']) && (int) $row['mfa_enabled'] === 1 ? '1' : '0'),
            'mfa_secret' => old('mfa_secret', (string) ($row['mfa_secret'] ?? ($row ? '' : AuthMfa::generateSecret()))),
        ];

        $this->view('user_accounts/views/form', [
            'title' => $title,
            'row' => $row,
            'formData' => $formData,
            'roleOptions' => $this->model()->getRoleOptions(),
        ]);
    }

    private function generateTemporaryPassword(): string
    {
        $length = max(10, (int) (auth_config('password_reset')['temporary_length'] ?? 12));
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $password;
    }
}
