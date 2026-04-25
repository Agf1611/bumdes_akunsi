<?php

declare(strict_types=1);

final class PeriodController extends Controller
{
    private function model(): PeriodModel
    {
        return new PeriodModel(Database::getInstance(db_config()));
    }


    public function selectWorking(): void
    {
        $years = working_year_options();
        if ($years === []) {
            flash('error', 'Belum ada periode akuntansi yang tersedia untuk dipilih.');
            $this->redirect('/periods');
        }

        if (count($years) === 1) {
            switch_working_year_session((int) $years[0]);
            $this->redirect('/dashboard');
        }

        $selectedYear = current_working_year();
        $cards = [];
        foreach ($years as $year) {
            $periods = working_year_periods((int) $year);
            $cards[] = [
                'year' => (int) $year,
                'period_count' => count($periods),
                'open_count' => count(array_filter($periods, static fn (array $p): bool => (string) ($p['status'] ?? '') === 'OPEN')),
                'active_count' => count(array_filter($periods, static fn (array $p): bool => (int) ($p['is_active'] ?? 0) === 1)),
                'default_period' => working_year_default_period((int) $year),
            ];
        }

        $this->view('periods/views/select_working', [
            'title' => 'Pilih Tahun Kerja',
            'yearCards' => $cards,
            'selectedYear' => $selectedYear,
        ]);
    }

    public function switchWorking(): void
    {
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        $year = (int) post('working_year');
        if ($year <= 0 || !switch_working_year_session($year)) {
            flash('error', 'Tahun kerja yang dipilih tidak valid atau belum tersedia.');
            $this->redirect('/periods/select-working');
        }

        flash('success', 'Tahun kerja berhasil diubah ke ' . $year . '.');
        $this->redirect('/dashboard');
    }

    public function index(): void
    {
        try {
            $periods = $this->model()->getList();
            foreach ($periods as &$period) {
                try {
                    $period['closing_readiness'] = $this->model()->buildClosingChecklist((int) ($period['id'] ?? 0));
                } catch (Throwable) {
                    $period['closing_readiness'] = null;
                }
            }
            unset($period);
            $this->view('periods/views/index', [
                'title' => 'Periode Akuntansi',
                'periods' => $periods,
                'statuses' => accounting_period_statuses(),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            render_error_page(500, 'Daftar periode akuntansi belum dapat dibuka. Pastikan tabel periode sudah dibuat.', $e);
        }
    }


    public function checklist(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID periode tidak valid.');
            $this->redirect('/periods');
        }

        try {
            $checklist = $this->model()->buildClosingChecklist($id);
            $this->view('periods/views/checklist', [
                'title' => 'Checklist Tutup Buku',
                'checklist' => $checklist,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            render_error_page(500, 'Checklist tutup buku belum dapat dibuka. Pastikan modul periode dan transaksi tersedia.', $e);
        }
    }

    public function create(): void
    {
        $this->showForm('Tambah Periode Akuntansi', null);
    }

    public function edit(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID periode tidak valid.');
            $this->redirect('/periods');
        }

        $period = $this->model()->findById($id);
        if (!$period) {
            flash('error', 'Periode yang ingin diubah tidak ditemukan.');
            $this->redirect('/periods');
        }

        $this->showForm('Edit Periode Akuntansi', $period);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID periode tidak valid untuk proses pembaruan.');
            $this->redirect('/periods');
        }

        $period = $this->model()->findById($id);
        if (!$period) {
            flash('error', 'Periode yang ingin diubah tidak ditemukan.');
            $this->redirect('/periods');
        }

        $this->save($id);
    }

    public function setActive(): void
    {
        $id = (int) get_query('id', 0);
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        if ($id <= 0) {
            flash('error', 'ID periode tidak valid.');
            $this->redirect('/periods');
        }

        try {
            $period = $this->model()->findById($id);
            if (!$period) {
                flash('error', 'Periode tidak ditemukan.');
                $this->redirect('/periods');
            }

            if ((string) $period['status'] !== 'OPEN') {
                flash('error', 'Hanya periode dengan status buka yang dapat dijadikan periode aktif.');
                $this->redirect('/periods');
            }

            $userId = (int) (Auth::user()['id'] ?? 0);
            $previousActive = current_accounting_period();
            $this->model()->setActive($id, $userId);
            $activeYear = (int) substr((string) ($period['start_date'] ?? ''), 0, 4);
            if ($activeYear > 0) {
                Session::put('working_fiscal_year', $activeYear);
            }
            Session::put('working_period_id', $id);
            audit_log('Periode Akuntansi', 'set_active', 'Periode aktif akuntansi diperbarui.', [
                'entity_type' => 'accounting_period',
                'entity_id' => (string) $id,
                'before' => $previousActive,
                'after' => $this->model()->findById($id),
            ]);
            flash('success', 'Periode aktif berhasil diperbarui. Hanya satu periode aktif yang diizinkan.');
            $this->redirect('/periods');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Periode aktif gagal diperbarui.');
            $this->redirect('/periods');
        }
    }

    public function toggleStatus(): void
    {
        $id = (int) get_query('id', 0);
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        if ($id <= 0) {
            flash('error', 'ID periode tidak valid.');
            $this->redirect('/periods');
        }

        try {
            $period = $this->model()->findById($id);
            if (!$period) {
                flash('error', 'Periode tidak ditemukan.');
                $this->redirect('/periods');
            }

            $newStatus = (string) $period['status'] === 'OPEN' ? 'CLOSED' : 'OPEN';
            if ($newStatus === 'CLOSED' && (int) $period['is_active'] !== 1) {
                flash('error', 'Periode nonaktif tidak perlu ditutup. Aktifkan lebih dulu jika memang akan ditutup.');
                $this->redirect('/periods');
            }
            if ($newStatus === 'CLOSED') {
                $checklist = $this->model()->buildClosingChecklist($id);
                $criticalFailures = (int) ($checklist['critical_failures'] ?? 0);
                if ($criticalFailures > 0) {
                    flash(
                        'error',
                        'Periode belum bisa ditutup karena masih ada ' . number_format($criticalFailures, 0, ',', '.') . ' blocker kritis pada checklist tutup buku.'
                    );
                    $this->redirect('/periods/checklist?id=' . $id);
                }
            }

            $userId = (int) (Auth::user()['id'] ?? 0);
            $beforePeriod = $period;
            $this->model()->setStatus($id, $newStatus, $userId);
            audit_log('Periode Akuntansi', $newStatus === 'OPEN' ? 'reopen' : 'close', $newStatus === 'OPEN' ? 'Periode akuntansi dibuka kembali.' : 'Periode akuntansi ditutup.', [
                'entity_type' => 'accounting_period',
                'entity_id' => (string) $id,
                'before' => $beforePeriod,
                'after' => $this->model()->findById($id),
            ]);
            flash('success', $newStatus === 'OPEN' ? 'Periode berhasil dibuka kembali.' : 'Periode berhasil ditutup. Periode tertutup tidak boleh dipakai input transaksi.');
            $this->redirect('/periods');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Status periode gagal diperbarui.');
            $this->redirect('/periods');
        }
    }



    public function yearEndClose(): void
    {
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID periode tidak valid.');
            $this->redirect('/periods');
        }

        try {
            $service = new YearEndClosingService(Database::getInstance(db_config()));
            $preview = $service->preview($id);
            $this->view('periods/views/year_end_close', [
                'title' => 'Tutup Buku Tahunan Otomatis',
                'preview' => $preview,
            ]);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', $e->getMessage() !== '' ? $e->getMessage() : 'Halaman tutup buku tahunan belum dapat dibuka.');
            $this->redirect('/periods');
        }
    }

    public function executeYearEndClose(): void
    {
        $id = (int) get_query('id', 0);
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
            return;
        }

        if ($id <= 0) {
            flash('error', 'ID periode tidak valid.');
            $this->redirect('/periods');
        }

        try {
            $service = new YearEndClosingService(Database::getInstance(db_config()));
            $beforePeriod = $this->model()->findById($id);
            $userId = (int) (Auth::user()['id'] ?? 0);
            $result = $service->execute($id, $userId);
            audit_log('Periode Akuntansi', 'year_end_close', 'Tutup buku tahunan otomatis dijalankan.', [
                'entity_type' => 'accounting_period',
                'entity_id' => (string) $id,
                'before' => $beforePeriod,
                'after' => [
                    'closed_period' => $result['closed_period'],
                    'next_period' => $result['next_period'],
                    'opening_journal_id' => $result['opening_journal_id'],
                    'totals' => $result['totals'],
                    'net_income' => $result['net_income'],
                    'retained_earnings' => $result['retained_earnings'],
                ],
            ]);
            flash('success', 'Tutup buku tahun ' . e((string) ($result['closed_period']['period_code'] ?? '')) . ' selesai. Periode ' . e((string) ($result['next_period']['period_name'] ?? 'tahun baru')) . ' sudah aktif dan jurnal saldo awal berhasil dibuat.');
            $this->redirect('/periods');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', $e->getMessage() !== '' ? $e->getMessage() : 'Tutup buku tahunan otomatis gagal dijalankan.');
            $this->redirect('/periods/year-end-close?id=' . $id);
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
            'period_code' => strtoupper(trim((string) post('period_code'))),
            'period_name' => trim((string) post('period_name')),
            'start_date' => trim((string) post('start_date')),
            'end_date' => trim((string) post('end_date')),
            'status' => trim((string) post('status', 'OPEN')),
            'is_active' => (string) post('is_active', '0') === '1',
            'updated_by' => (int) (Auth::user()['id'] ?? 0),
        ];

        with_old_input([
            'period_code' => $input['period_code'],
            'period_name' => $input['period_name'],
            'start_date' => $input['start_date'],
            'end_date' => $input['end_date'],
            'status' => $input['status'],
            'is_active' => $input['is_active'] ? '1' : '0',
        ]);

        $errors = $this->validate($input, $id);
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect($id === null ? '/periods/create' : '/periods/edit?id=' . $id);
        }

        try {
            $beforePeriod = $id !== null ? $this->model()->findById($id) : null;
            if ($id === null) {
                $createdId = $this->model()->create($input);
                audit_log('Periode Akuntansi', 'create', 'Periode akuntansi baru ditambahkan.', [
                    'entity_type' => 'accounting_period',
                    'entity_id' => (string) $createdId,
                    'after' => $this->model()->findById($createdId),
                ]);
                flash('success', 'Periode akuntansi baru berhasil ditambahkan.');
            } else {
                $this->model()->update($id, $input);
                audit_log('Periode Akuntansi', 'update', 'Periode akuntansi diperbarui.', [
                    'entity_type' => 'accounting_period',
                    'entity_id' => (string) $id,
                    'before' => $beforePeriod,
                    'after' => $this->model()->findById($id),
                ]);
                flash('success', 'Periode akuntansi berhasil diperbarui.');
            }

            clear_old_input();
            $this->redirect('/periods');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Periode akuntansi gagal disimpan. Silakan periksa kembali data yang diisi.');
            $this->redirect($id === null ? '/periods/create' : '/periods/edit?id=' . $id);
        }
    }

    private function validate(array $input, ?int $currentId = null): array
    {
        $errors = [];
        $statuses = accounting_period_statuses();

        if ($input['period_code'] === '') {
            $errors[] = 'Kode periode wajib diisi.';
        } elseif (!preg_match('/^[A-Z0-9.\/-]{3,30}$/', $input['period_code'])) {
            $errors[] = 'Kode periode hanya boleh berisi huruf besar, angka, titik, garis miring, atau tanda hubung dengan panjang 3 sampai 30 karakter.';
        } elseif ($this->model()->findByCode($input['period_code'], $currentId)) {
            $errors[] = 'Kode periode sudah dipakai. Gunakan kode yang unik.';
        }

        if ($input['period_name'] === '') {
            $errors[] = 'Nama periode wajib diisi.';
        } elseif (mb_strlen($input['period_name']) < 3 || mb_strlen($input['period_name']) > 100) {
            $errors[] = 'Nama periode harus 3 sampai 100 karakter.';
        }

        if (!isset($statuses[$input['status']])) {
            $errors[] = 'Status periode tidak valid.';
        }

        if (!$this->isValidDate($input['start_date'])) {
            $errors[] = 'Tanggal mulai periode wajib diisi dengan format yang benar.';
        }

        if (!$this->isValidDate($input['end_date'])) {
            $errors[] = 'Tanggal akhir periode wajib diisi dengan format yang benar.';
        }

        if ($this->isValidDate($input['start_date']) && $this->isValidDate($input['end_date'])) {
            if ($input['end_date'] < $input['start_date']) {
                $errors[] = 'Tanggal akhir periode tidak boleh lebih kecil dari tanggal mulai.';
            }

            if ($this->model()->hasOverlap($input['start_date'], $input['end_date'], $currentId)) {
                $errors[] = 'Rentang tanggal periode bertabrakan dengan periode lain. Gunakan rentang yang tidak saling tumpang tindih.';
            }
        }

        if ($input['is_active'] && $input['status'] !== 'OPEN') {
            $errors[] = 'Periode aktif harus berstatus buka.';
        }

        if ($currentId !== null) {
            $existing = $this->model()->findById($currentId);
            if ($existing && (int) $existing['is_active'] === 1 && $input['status'] === 'CLOSED') {
                $errors[] = 'Periode aktif tidak boleh diubah langsung menjadi tutup dari form edit. Gunakan tombol Tutup Periode di daftar periode.';
            }
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '') {
            return false;
        }

        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTimeImmutable && $dt->format('Y-m-d') === $date;
    }

    private function showForm(string $title, ?array $period): void
    {
        try {
            $formData = [
                'period_code' => old('period_code', (string) ($period['period_code'] ?? '')),
                'period_name' => old('period_name', (string) ($period['period_name'] ?? '')),
                'start_date' => old('start_date', (string) ($period['start_date'] ?? '')),
                'end_date' => old('end_date', (string) ($period['end_date'] ?? '')),
                'status' => old('status', (string) ($period['status'] ?? 'OPEN')),
                'is_active' => old('is_active', isset($period['is_active']) ? ((int) $period['is_active'] === 1 ? '1' : '0') : '0'),
            ];

            $this->view('periods/views/form', [
                'title' => $title,
                'period' => $period,
                'formData' => $formData,
                'statuses' => accounting_period_statuses(),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            render_error_page(500, 'Form periode akuntansi belum dapat dibuka. Pastikan tabel periode sudah dibuat dengan benar.', $e);
        }
    }
}
