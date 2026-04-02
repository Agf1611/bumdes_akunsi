<?php

declare(strict_types=1);

final class ProfileController extends Controller
{
    private function model(): ProfileModel
    {
        return new ProfileModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            $profile = $this->model()->findFirst() ?? [
                'bumdes_name' => '',
                'address' => '',
                'village_name' => '',
                'district_name' => '',
                'regency_name' => '',
                'province_name' => '',
                'legal_entity_no' => '',
                'nib' => '',
                'npwp' => '',
                'phone' => '',
                'email' => '',
                'logo_path' => '',
                'director_name' => '',
                'director_position' => 'Direktur',
                'signature_city' => '',
                'signature_path' => '',
                'treasurer_name' => '',
                'treasurer_position' => 'Bendahara',
                'treasurer_signature_path' => '',
                'receipt_signature_mode' => 'treasurer_recipient_director',
                'receipt_require_recipient_cash' => 1,
                'receipt_require_recipient_transfer' => 0,
                'director_sign_threshold' => '0.00',
                'show_stamp' => 1,
                'active_period_start' => '',
                'active_period_end' => '',
            ];

            $this->view('settings/views/profile_form', [
                'title' => 'Profil BUMDes',
                'profile' => $profile,
                'errorMessage' => get_flash('error'),
                'successMessage' => get_flash('success'),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Data profil BUMDes belum dapat dibuka. Pastikan tabel modul profil sudah dibuat.', $e);
        }
    }

    public function save(): void
    {
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        $input = [
            'bumdes_name' => trim((string) post('bumdes_name')),
            'address' => trim((string) post('address')),
            'village_name' => trim((string) post('village_name')),
            'district_name' => trim((string) post('district_name')),
            'regency_name' => trim((string) post('regency_name')),
            'province_name' => trim((string) post('province_name')),
            'legal_entity_no' => trim((string) post('legal_entity_no')),
            'nib' => trim((string) post('nib')),
            'npwp' => trim((string) post('npwp')),
            'phone' => trim((string) post('phone')),
            'email' => trim((string) post('email')),
            'director_name' => trim((string) post('director_name')),
            'director_position' => trim((string) post('director_position')),
            'signature_city' => trim((string) post('signature_city')),
            'treasurer_name' => trim((string) post('treasurer_name')),
            'treasurer_position' => trim((string) post('treasurer_position')),
            'receipt_signature_mode' => trim((string) post('receipt_signature_mode')),
            'receipt_require_recipient_cash' => post('receipt_require_recipient_cash') ? 1 : 0,
            'receipt_require_recipient_transfer' => post('receipt_require_recipient_transfer') ? 1 : 0,
            'director_sign_threshold' => trim((string) post('director_sign_threshold')),
            'show_stamp' => post('show_stamp') ? 1 : 0,
            'active_period_start' => trim((string) post('active_period_start')),
            'active_period_end' => trim((string) post('active_period_end')),
        ];

        with_old_input($input);
        $errors = $this->validate($input, $_FILES['logo'] ?? null, $_FILES['signature_file'] ?? null, $_FILES['treasurer_signature_file'] ?? null);
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect('/settings/profile');
        }

        try {
            $existing = $this->model()->findFirst();
            $beforeProfile = is_array($existing) ? $existing : null;
            $input['logo_path'] = upload_bumdes_logo($_FILES['logo'] ?? ['error' => UPLOAD_ERR_NO_FILE], $existing['logo_path'] ?? null);
            $input['signature_path'] = upload_director_signature($_FILES['signature_file'] ?? ['error' => UPLOAD_ERR_NO_FILE], $existing['signature_path'] ?? null);
            $input['treasurer_signature_path'] = upload_treasurer_signature($_FILES['treasurer_signature_file'] ?? ['error' => UPLOAD_ERR_NO_FILE], $existing['treasurer_signature_path'] ?? null);
            $input['leader_name'] = $input['director_name'];
            $input['updated_by'] = (int) (Auth::user()['id'] ?? 0);
            $input['director_sign_threshold'] = $input['director_sign_threshold'] !== ''
                ? number_format((float) $input['director_sign_threshold'], 2, '.', '')
                : '0.00';

            $this->model()->save($input);
            $savedProfile = $this->model()->findFirst();
            app_profile(true);
            audit_log('Profil BUMDes', 'update', 'Profil BUMDes dan pengaturan dokumen diperbarui.', [
                'entity_type' => 'app_profile',
                'entity_id' => (string) ($savedProfile['id'] ?? ''),
                'before' => $beforeProfile,
                'after' => $savedProfile,
                'context' => ['legal_identity' => report_profile_legal($savedProfile ?? [])],
            ]);
            clear_old_input();
            flash('success', 'Profil BUMDes, penandatangan dokumen, dan aturan kwitansi berhasil disimpan.');
            $this->redirect('/settings/profile');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Profil BUMDes gagal disimpan: ' . $e->getMessage());
            $this->redirect('/settings/profile');
        }
    }

    private function validate(array $input, ?array $logoFile, ?array $signatureFile, ?array $treasurerSignatureFile): array
    {
        $errors = [];

        if ($input['bumdes_name'] === '') {
            $errors[] = 'Nama BUMDes wajib diisi.';
        } elseif (mb_strlen($input['bumdes_name']) < 3 || mb_strlen($input['bumdes_name']) > 150) {
            $errors[] = 'Nama BUMDes harus 3 sampai 150 karakter.';
        }

        if ($input['address'] === '') {
            $errors[] = 'Alamat wajib diisi.';
        } elseif (mb_strlen($input['address']) > 500) {
            $errors[] = 'Alamat maksimal 500 karakter.';
        }

        foreach ([
            'Desa' => $input['village_name'],
            'Kecamatan' => $input['district_name'],
            'Kabupaten' => $input['regency_name'],
            'Provinsi' => $input['province_name'],
        ] as $label => $value) {
            if ($value !== '' && mb_strlen($value) > 120) {
                $errors[] = $label . ' maksimal 120 karakter.';
            }
        }

        if ($input['legal_entity_no'] !== '' && mb_strlen($input['legal_entity_no']) > 120) {
            $errors[] = 'Nomor badan hukum maksimal 120 karakter.';
        }
        if ($input['nib'] !== '' && mb_strlen($input['nib']) > 50) {
            $errors[] = 'NIB maksimal 50 karakter.';
        }
        if ($input['npwp'] !== '' && mb_strlen($input['npwp']) > 50) {
            $errors[] = 'NPWP maksimal 50 karakter.';
        }

        if ($input['phone'] !== '' && !preg_match('/^[0-9+()\-\s]{6,30}$/', $input['phone'])) {
            $errors[] = 'Telepon hanya boleh berisi angka dan simbol + ( ) - dengan panjang 6 sampai 30 karakter.';
        }

        if ($input['email'] !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }

        if ($input['director_name'] === '') {
            $errors[] = 'Nama direktur / pimpinan wajib diisi.';
        } elseif (mb_strlen($input['director_name']) > 120) {
            $errors[] = 'Nama direktur maksimal 120 karakter.';
        }

        if ($input['director_position'] === '') {
            $errors[] = 'Jabatan direktur wajib diisi.';
        } elseif (mb_strlen($input['director_position']) > 100) {
            $errors[] = 'Jabatan direktur maksimal 100 karakter.';
        }

        if ($input['treasurer_name'] === '') {
            $errors[] = 'Nama bendahara wajib diisi agar bisa dipakai pada cetak kwitansi.';
        } elseif (mb_strlen($input['treasurer_name']) > 120) {
            $errors[] = 'Nama bendahara maksimal 120 karakter.';
        }

        if ($input['treasurer_position'] === '') {
            $errors[] = 'Jabatan bendahara wajib diisi.';
        } elseif (mb_strlen($input['treasurer_position']) > 100) {
            $errors[] = 'Jabatan bendahara maksimal 100 karakter.';
        }

        if ($input['signature_city'] !== '' && mb_strlen($input['signature_city']) > 100) {
            $errors[] = 'Kota tanda tangan maksimal 100 karakter.';
        }

        $allowedModes = [
            'treasurer_only',
            'treasurer_recipient',
            'treasurer_director',
            'treasurer_recipient_director',
        ];
        if (!in_array($input['receipt_signature_mode'], $allowedModes, true)) {
            $errors[] = 'Mode tanda tangan kwitansi tidak valid.';
        }

        if ($input['director_sign_threshold'] !== '' && !is_numeric($input['director_sign_threshold'])) {
            $errors[] = 'Batas nominal tanda tangan direktur harus berupa angka.';
        } elseif ($input['director_sign_threshold'] !== '' && (float) $input['director_sign_threshold'] < 0) {
            $errors[] = 'Batas nominal tanda tangan direktur tidak boleh negatif.';
        }

        if (!$this->isValidDate($input['active_period_start'])) {
            $errors[] = 'Tanggal mulai periode aktif wajib diisi dengan format yang benar.';
        }

        if (!$this->isValidDate($input['active_period_end'])) {
            $errors[] = 'Tanggal akhir periode aktif wajib diisi dengan format yang benar.';
        }

        if ($this->isValidDate($input['active_period_start']) && $this->isValidDate($input['active_period_end']) && $input['active_period_end'] < $input['active_period_start']) {
            $errors[] = 'Tanggal akhir periode aktif tidak boleh lebih kecil dari tanggal mulai.';
        }

        foreach ([
            'logo' => $logoFile,
            'tanda tangan direktur' => $signatureFile,
            'tanda tangan bendahara' => $treasurerSignatureFile,
        ] as $label => $file) {
            if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $allowedErrors = [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE];
                if (!in_array((int) $file['error'], $allowedErrors, true)) {
                    $errors[] = 'File ' . $label . ' gagal diunggah. Silakan pilih file yang valid.';
                    continue;
                }

                $size = (int) ($file['size'] ?? 0);
                if ($size <= 0 || $size > 2 * 1024 * 1024) {
                    $errors[] = 'Ukuran file ' . $label . ' maksimal 2 MB.';
                    continue;
                }

                $tmpName = (string) ($file['tmp_name'] ?? '');
                if ($tmpName === '') {
                    $errors[] = 'File ' . $label . ' tidak valid.';
                    continue;
                }

                if (profile_upload_image_meta($tmpName, (string) ($file['name'] ?? '')) === null) {
                    $errors[] = 'Format file ' . $label . ' hanya boleh JPG, PNG, atau WEBP.';
                }
            }
        }

        return $errors;
    }

    private function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
    }
}
