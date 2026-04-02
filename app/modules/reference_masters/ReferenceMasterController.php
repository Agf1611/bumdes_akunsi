<?php

declare(strict_types=1);

final class ReferenceMasterController extends Controller
{
    private function model(): ReferenceMasterModel
    {
        return new ReferenceMasterModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        $type = $this->readType();
        try {
            $search = trim((string) get_query('search', ''));
            $config = ReferenceMasterModel::config($type);
            $this->view('reference_masters/views/index', [
                'title' => $config['title'] ?? 'Master Referensi',
                'type' => $type,
                'config' => $config,
                'search' => $search,
                'rows' => $this->model()->getList($type, $search),
                'types' => ReferenceMasterModel::configs(),
                'isReady' => $this->model()->isReady($type),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Master referensi belum dapat dibuka.', $e);
        }
    }

    public function create(): void
    {
        $type = $this->readType();
        $this->showForm('Tambah ' . (ReferenceMasterModel::config($type)['title'] ?? 'Data Referensi'), $type, null);
    }

    public function edit(): void
    {
        $type = $this->readType();
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID data referensi tidak valid.');
            $this->redirect('/reference-masters?type=' . urlencode($type));
        }
        try {
            $row = $this->model()->findById($type, $id);
            if (!$row) {
                flash('error', 'Data referensi tidak ditemukan.');
                $this->redirect('/reference-masters?type=' . urlencode($type));
            }
            $this->showForm('Edit ' . (ReferenceMasterModel::config($type)['title'] ?? 'Data Referensi'), $type, $row);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Form edit referensi belum dapat dibuka. ' . $e->getMessage());
            $this->redirect('/reference-masters?type=' . urlencode($type));
        }
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID data referensi tidak valid.');
            $this->redirect('/reference-masters');
        }
        $this->save($id);
    }

    public function toggleActive(): void
    {
        $type = $this->readType();
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        $id = (int) get_query('id', 0);
        try {
            $row = $this->model()->findById($type, $id);
            if (!$row) {
                throw new RuntimeException('Data referensi tidak ditemukan.');
            }
            $newStatus = ((int) ($row['is_active'] ?? 0)) !== 1;
            $this->model()->setActive($type, $id, $newStatus);
            flash('success', $newStatus ? 'Data referensi berhasil diaktifkan.' : 'Data referensi berhasil dinonaktifkan.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Status referensi gagal diperbarui. ' . $e->getMessage());
        }
        $this->redirect('/reference-masters?type=' . urlencode($type));
    }

    public function delete(): void
    {
        $type = $this->readType();
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        $id = (int) get_query('id', 0);
        try {
            [$canDelete, $reason] = $this->model()->canDelete($type, $id);
            if (!$canDelete) {
                flash('error', $reason);
                $this->redirect('/reference-masters?type=' . urlencode($type));
            }
            $this->model()->delete($type, $id);
            flash('success', 'Data referensi berhasil dihapus.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Data referensi gagal dihapus. ' . $e->getMessage());
        }
        $this->redirect('/reference-masters?type=' . urlencode($type));
    }

    private function save(?int $id): void
    {
        $type = $this->readType();
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }
        $config = ReferenceMasterModel::config($type) ?? [];
        $data = [
            'code' => strtoupper(trim((string) post('code'))),
            'name' => trim((string) post('name')),
            'type_value' => trim((string) post('type_value')),
            'phone' => trim((string) post('phone')),
            'address' => trim((string) post('address')),
            'unit_name' => trim((string) post('unit_name')),
            'owner_name' => trim((string) post('owner_name')),
            'notes' => trim((string) post('notes')),
            'is_active' => (string) post('is_active', '1') === '1',
        ];
        with_old_input($data);
        $errors = [];
        if ($data['code'] === '') {
            $errors[] = 'Kode wajib diisi.';
        } elseif (!preg_match('/^[A-Z0-9._\/-]{2,40}$/', $data['code'])) {
            $errors[] = 'Kode hanya boleh huruf besar, angka, titik, garis miring, garis bawah, atau tanda hubung.';
        } elseif ($this->model()->findByCode($type, $data['code'], $id)) {
            $errors[] = 'Kode sudah dipakai. Gunakan kode lain.';
        }
        if ($data['name'] === '') {
            $errors[] = 'Nama wajib diisi.';
        } elseif (mb_strlen($data['name']) < 3 || mb_strlen($data['name']) > 150) {
            $errors[] = 'Nama harus 3 sampai 150 karakter.';
        }
        if (($config['type_field'] ?? null) !== null && $data['type_value'] !== '' && !isset(($config['type_options'] ?? [])[$data['type_value']])) {
            $errors[] = 'Jenis referensi tidak valid.';
        }
        if (mb_strlen($data['notes']) > 255) {
            $errors[] = 'Catatan maksimal 255 karakter.';
        }
        if ($type === 'partners' && mb_strlen($data['address']) > 255) {
            $errors[] = 'Alamat maksimal 255 karakter.';
        }
        if ($type === 'partners' && mb_strlen($data['phone']) > 50) {
            $errors[] = 'Telepon maksimal 50 karakter.';
        }
        if (in_array($type, ['inventory', 'raw-materials'], true) && mb_strlen($data['unit_name']) > 30) {
            $errors[] = 'Satuan maksimal 30 karakter.';
        }
        if ($type === 'savings' && mb_strlen($data['owner_name']) > 150) {
            $errors[] = 'Nama pemilik maksimal 150 karakter.';
        }
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect($id === null ? '/reference-masters/create?type=' . urlencode($type) : '/reference-masters/edit?type=' . urlencode($type) . '&id=' . $id);
        }

        try {
            if ($id === null) {
                $this->model()->create($type, $data);
                flash('success', 'Data referensi berhasil ditambahkan.');
            } else {
                $this->model()->update($type, $id, $data);
                flash('success', 'Data referensi berhasil diperbarui.');
            }
            clear_old_input();
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Data referensi gagal disimpan. ' . $e->getMessage());
            $this->redirect($id === null ? '/reference-masters/create?type=' . urlencode($type) : '/reference-masters/edit?type=' . urlencode($type) . '&id=' . $id);
        }
        $this->redirect('/reference-masters?type=' . urlencode($type));
    }

    private function showForm(string $title, string $type, ?array $row): void
    {
        $config = ReferenceMasterModel::config($type) ?? [];
        $formData = [
            'code' => old('code', (string) ($row[$config['code_field'] ?? ''] ?? '')),
            'name' => old('name', (string) ($row[$config['name_field'] ?? ''] ?? '')),
            'type_value' => old('type_value', (string) (($config['type_field'] ?? null) !== null ? ($row[$config['type_field']] ?? '') : '')),
            'phone' => old('phone', (string) ($row['phone'] ?? '')),
            'address' => old('address', (string) ($row['address'] ?? '')),
            'unit_name' => old('unit_name', (string) ($row['unit_name'] ?? '')),
            'owner_name' => old('owner_name', (string) ($row['owner_name'] ?? '')),
            'notes' => old('notes', (string) ($row[$config['description_field'] ?? ''] ?? '')),
            'is_active' => old('is_active', isset($row['is_active']) && (int) $row['is_active'] !== 1 ? '0' : '1'),
        ];
        $this->view('reference_masters/views/form', [
            'title' => $title,
            'type' => $type,
            'config' => $config,
            'row' => $row,
            'formData' => $formData,
        ]);
    }

    private function readType(): string
    {
        $type = trim((string) get_query('type', 'partners'));
        return ReferenceMasterModel::config($type) !== null ? $type : 'partners';
    }
}
