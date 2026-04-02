<?php

declare(strict_types=1);

final class BusinessUnitController extends Controller
{
    private function model(): BusinessUnitModel
    {
        return new BusinessUnitModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            $search = trim((string) get_query('search', ''));
            $this->view('business_units/views/index', [
                'title' => 'Master Unit Usaha',
                'rows' => $this->model()->getList($search),
                'search' => $search,
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Daftar unit usaha belum dapat dibuka. Pastikan migrasi unit usaha sudah dijalankan.', $e);
        }
    }

    public function create(): void
    {
        $this->showForm('Tambah Unit Usaha', null);
    }

    public function edit(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID unit usaha tidak valid.');
            $this->redirect('/business-units');
        }
        $row = $this->model()->findById($id);
        if (!$row) {
            flash('error', 'Unit usaha tidak ditemukan.');
            $this->redirect('/business-units');
        }
        $this->showForm('Edit Unit Usaha', $row);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID unit usaha tidak valid.');
            $this->redirect('/business-units');
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
            flash('error', 'Unit usaha tidak ditemukan.');
            $this->redirect('/business-units');
        }
        try {
            $newStatus = ((int) $row['is_active']) !== 1;
            $this->model()->setActive($id, $newStatus);
            flash('success', $newStatus ? 'Unit usaha berhasil diaktifkan.' : 'Unit usaha berhasil dinonaktifkan.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Status unit usaha gagal diperbarui.');
        }
        $this->redirect('/business-units');
    }

    public function delete(): void
    {
        $id = (int) get_query('id', 0);
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        $row = $this->model()->findById($id);
        if (!$row) {
            flash('error', 'Unit usaha tidak ditemukan.');
            $this->redirect('/business-units');
        }
        [$canDelete, $reason] = $this->model()->canDelete($id);
        if (!$canDelete) {
            flash('error', $reason);
            $this->redirect('/business-units');
        }
        try {
            $this->model()->delete($id);
            flash('success', 'Unit usaha berhasil dihapus.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Unit usaha gagal dihapus.');
        }
        $this->redirect('/business-units');
    }

    private function save(?int $id): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }

        $input = [
            'unit_code' => strtoupper(trim((string) post('unit_code'))),
            'unit_name' => trim((string) post('unit_name')),
            'description' => trim((string) post('description')),
            'is_active' => (string) post('is_active', '1') === '1',
        ];

        with_old_input([
            'unit_code' => $input['unit_code'],
            'unit_name' => $input['unit_name'],
            'description' => $input['description'],
            'is_active' => $input['is_active'] ? '1' : '0',
        ]);

        $errors = [];
        if ($input['unit_code'] === '') {
            $errors[] = 'Kode unit wajib diisi.';
        } elseif (!preg_match('/^[A-Z0-9._-]{2,30}$/', $input['unit_code'])) {
            $errors[] = 'Kode unit hanya boleh huruf besar, angka, titik, garis bawah, atau tanda hubung.';
        } elseif ($this->model()->findByCode($input['unit_code'], $id)) {
            $errors[] = 'Kode unit sudah dipakai. Gunakan kode lain.';
        }

        if ($input['unit_name'] === '') {
            $errors[] = 'Nama unit usaha wajib diisi.';
        } elseif (mb_strlen($input['unit_name']) < 3 || mb_strlen($input['unit_name']) > 120) {
            $errors[] = 'Nama unit usaha harus 3 sampai 120 karakter.';
        }

        if (mb_strlen($input['description']) > 500) {
            $errors[] = 'Deskripsi unit usaha maksimal 500 karakter.';
        }

        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect($id === null ? '/business-units/create' : '/business-units/edit?id=' . $id);
        }

        try {
            if ($id === null) {
                $this->model()->create($input);
                flash('success', 'Unit usaha berhasil ditambahkan.');
            } else {
                $this->model()->update($id, $input);
                flash('success', 'Unit usaha berhasil diperbarui.');
            }
            clear_old_input();
            $this->redirect('/business-units');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Data unit usaha gagal disimpan.');
            $this->redirect($id === null ? '/business-units/create' : '/business-units/edit?id=' . $id);
        }
    }

    private function showForm(string $title, ?array $row): void
    {
        $formData = [
            'unit_code' => old('unit_code', (string) ($row['unit_code'] ?? '')),
            'unit_name' => old('unit_name', (string) ($row['unit_name'] ?? '')),
            'description' => old('description', (string) ($row['description'] ?? '')),
            'is_active' => old('is_active', isset($row['is_active']) && (int) $row['is_active'] === 1 ? '1' : '1'),
        ];

        $this->view('business_units/views/form', [
            'title' => $title,
            'row' => $row,
            'formData' => $formData,
        ]);
    }
}
