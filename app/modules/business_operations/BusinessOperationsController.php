<?php

declare(strict_types=1);

final class BusinessOperationsController extends Controller
{
    private const PAGES = [
        'employees' => [
            'title' => 'Manajemen Karyawan',
            'icon' => 'bi-people',
            'description' => 'Data pengurus, karyawan, kontak, jabatan, dan status per unit usaha.',
            'create_label' => 'Tambah Karyawan',
            'route' => '/business-employees',
        ],
        'business' => [
            'title' => 'Manajemen Bisnis',
            'icon' => 'bi-building',
            'description' => 'Catat layanan, kegiatan, target, dan kondisi operasional unit usaha.',
            'create_label' => 'Tambah Aktivitas',
            'route' => '/business-management',
        ],
        'budgets' => [
            'title' => 'Anggaran',
            'icon' => 'bi-wallet2',
            'description' => 'Pagu pendapatan, belanja, modal, dan pembelian aset per unit usaha.',
            'create_label' => 'Tambah Anggaran',
            'route' => '/budgets',
        ],
        'budget_plans' => [
            'title' => 'Rencana Anggaran',
            'icon' => 'bi-clipboard2-check',
            'description' => 'RAB kegiatan/pembelian dengan rincian item, qty, harga, dan total.',
            'create_label' => 'Tambah RAB',
            'route' => '/budget-plans',
        ],
        'budget_reports' => [
            'title' => 'Laporan Rencana Anggaran',
            'icon' => 'bi-bar-chart-line',
            'description' => 'Bandingkan anggaran, RAB, dan realisasi jurnal per unit usaha.',
            'create_label' => 'Lihat Laporan',
            'route' => '/budget-plan-reports',
        ],
    ];

    private function model(): BusinessOperationsModel
    {
        return new BusinessOperationsModel(Database::getInstance(db_config()));
    }

    public function employees(): void
    {
        $this->renderList('employees');
    }

    public function business(): void
    {
        $this->renderList('business');
    }

    public function budgets(): void
    {
        $this->renderList('budgets');
    }

    public function budgetPlans(): void
    {
        $this->renderList('budget_plans');
    }

    public function budgetReports(): void
    {
        try {
            $filters = $this->filters();
            $model = $this->model();
            $this->view('business_operations/views/report', [
                'title' => self::PAGES['budget_reports']['title'],
                'page' => self::PAGES['budget_reports'],
                'units' => $model->units(),
                'filters' => $filters,
                'report' => $model->isReady() ? $model->report($filters) : null,
                'isReady' => $model->isReady(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            render_error_page(500, 'Laporan rencana anggaran belum dapat dibuka.', $e);
        }
    }

    public function create(): void
    {
        $type = $this->readType();
        $this->renderForm($type, null);
    }

    public function edit(): void
    {
        $type = $this->readType();
        $id = (int) get_query('id', 0);
        if ($id <= 0) {
            flash('error', 'ID data tidak valid.');
            $this->redirect($this->routeFor($type));
        }

        try {
            $row = $this->model()->find($type, $id);
            if (!$row) {
                flash('error', 'Data tidak ditemukan.');
                $this->redirect($this->routeFor($type));
            }
            $this->renderForm($type, $row);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Form edit belum dapat dibuka. ' . $e->getMessage());
            $this->redirect($this->routeFor($type));
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
            flash('error', 'ID data tidak valid.');
            $this->redirect($this->routeFor($this->readType()));
        }
        $this->save($id);
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
            $this->model()->delete($type, $id);
            flash('success', 'Data berhasil dihapus.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Data gagal dihapus. ' . $e->getMessage());
        }
        $this->redirect($this->routeFor($type));
    }

    private function renderList(string $type): void
    {
        try {
            $model = $this->model();
            $filters = $this->filters();
            $rows = [];
            if ($model->isReady()) {
                $rows = match ($type) {
                    'employees' => $model->listEmployees($filters),
                    'business' => $model->listActivities($filters),
                    'budgets' => $model->listBudgets($filters),
                    'budget_plans' => $model->listPlans($filters),
                    default => [],
                };
            }

            $this->view('business_operations/views/index', [
                'title' => self::PAGES[$type]['title'],
                'type' => $type,
                'page' => self::PAGES[$type],
                'rows' => $rows,
                'units' => $model->units(),
                'filters' => $filters,
                'isReady' => $model->isReady(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            render_error_page(500, 'Menu Kelola Usaha belum dapat dibuka.', $e);
        }
    }

    private function renderForm(string $type, ?array $row): void
    {
        $model = $this->model();
        if (!$model->isReady()) {
            flash('error', 'Tabel Kelola Usaha belum tersedia. Jalankan update database terlebih dahulu.');
            $this->redirect($this->routeFor($type));
        }

        $items = [];
        if ($type === 'budget_plans' && $row !== null) {
            $items = $model->planItems((int) $row['id']);
        }

        $this->view('business_operations/views/form', [
            'title' => ($row === null ? 'Tambah ' : 'Edit ') . self::PAGES[$type]['title'],
            'type' => $type,
            'page' => self::PAGES[$type],
            'row' => $row,
            'items' => $items,
            'units' => $model->units(),
            'accounts' => $model->accounts(),
            'formData' => $this->formData($type, $row, $items),
        ]);
    }

    private function save(?int $id): void
    {
        $type = $this->readType();
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan telah berakhir. Silakan muat ulang halaman.');
            return;
        }

        try {
            [$data, $items] = $this->inputFor($type);
            with_old_input($data);
            $errors = $this->validateInput($type, $data, $items);
            if ($errors !== []) {
                flash('error', implode(' ', $errors));
                $this->redirect($id === null ? $this->createRoute($type) : $this->editRoute($type, $id));
            }

            $model = $this->model();
            match ($type) {
                'employees' => $model->saveEmployee($id, $data),
                'business' => $model->saveActivity($id, $data),
                'budgets' => $model->saveBudget($id, $data),
                'budget_plans' => $model->savePlan($id, $data, $items),
                default => throw new InvalidArgumentException('Jenis data tidak valid.'),
            };
            clear_old_input();
            flash('success', 'Data berhasil disimpan.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Data gagal disimpan. ' . $e->getMessage());
            $this->redirect($id === null ? $this->createRoute($type) : $this->editRoute($type, $id));
        }

        $this->redirect($this->routeFor($type));
    }

    private function filters(): array
    {
        return [
            'search' => trim((string) get_query('search', '')),
            'unit_id' => (int) get_query('unit_id', current_business_unit_id()),
            'year' => (int) get_query('year', (int) date('Y')),
            'month' => (int) get_query('month', 0),
        ];
    }

    private function inputFor(string $type): array
    {
        $base = [
            'business_unit_id' => (int) post('business_unit_id', 0),
            'notes' => trim((string) post('notes', '')),
        ];

        return match ($type) {
            'employees' => [[
                ...$base,
                'employee_name' => trim((string) post('employee_name', '')),
                'position_title' => trim((string) post('position_title', '')),
                'phone' => trim((string) post('phone', '')),
                'email' => trim((string) post('email', '')),
                'status' => in_array((string) post('status', 'ACTIVE'), ['ACTIVE', 'INACTIVE'], true) ? (string) post('status', 'ACTIVE') : 'ACTIVE',
            ], []],
            'business' => [[
                ...$base,
                'activity_name' => trim((string) post('activity_name', '')),
                'activity_type' => trim((string) post('activity_type', '')),
                'target_period' => trim((string) post('target_period', '')),
                'target_value' => $this->decimal(post('target_value', '0')),
                'status' => in_array((string) post('status', 'RUNNING'), ['PLANNED', 'RUNNING', 'DONE', 'PAUSED'], true) ? (string) post('status', 'RUNNING') : 'RUNNING',
            ], []],
            'budgets' => [[
                ...$base,
                'budget_year' => max(2000, (int) post('budget_year', date('Y'))),
                'budget_month' => (int) post('budget_month', 0),
                'budget_type' => in_array((string) post('budget_type', 'EXPENSE'), ['INCOME', 'EXPENSE', 'ASSET', 'CAPITAL'], true) ? (string) post('budget_type', 'EXPENSE') : 'EXPENSE',
                'category' => trim((string) post('category', '')),
                'account_id' => (int) post('account_id', 0),
                'amount' => $this->decimal(post('amount', '0')),
                'status' => in_array((string) post('status', 'ACTIVE'), ['DRAFT', 'ACTIVE', 'CLOSED'], true) ? (string) post('status', 'ACTIVE') : 'ACTIVE',
            ], []],
            'budget_plans' => [[
                ...$base,
                'plan_no' => trim((string) post('plan_no', '')),
                'plan_date' => trim((string) post('plan_date', date('Y-m-d'))),
                'plan_title' => trim((string) post('plan_title', '')),
                'activity_name' => trim((string) post('activity_name', '')),
                'status' => in_array((string) post('status', 'DRAFT'), ['DRAFT', 'APPROVED', 'REALIZED', 'CANCELLED'], true) ? (string) post('status', 'DRAFT') : 'DRAFT',
            ], $this->planItemsFromPost()],
            default => throw new InvalidArgumentException('Jenis data tidak valid.'),
        };
    }

    private function planItemsFromPost(): array
    {
        $names = (array) post('item_name', []);
        $qtys = (array) post('quantity', []);
        $units = (array) post('unit_name', []);
        $prices = (array) post('unit_price', []);
        $notes = (array) post('item_notes', []);
        $items = [];
        foreach ($names as $idx => $name) {
            $itemName = trim((string) $name);
            if ($itemName === '') {
                continue;
            }
            $qty = max(0, (float) $this->decimal($qtys[$idx] ?? '1'));
            $price = max(0, (float) $this->decimal($prices[$idx] ?? '0'));
            $items[] = [
                'item_name' => $itemName,
                'quantity' => (string) $qty,
                'unit_name' => trim((string) ($units[$idx] ?? 'unit')) ?: 'unit',
                'unit_price' => (string) $price,
                'total_amount' => (string) ($qty * $price),
                'notes' => trim((string) ($notes[$idx] ?? '')),
            ];
        }
        return $items;
    }

    private function validateInput(string $type, array $data, array $items): array
    {
        $errors = [];
        if (mb_strlen((string) ($data['notes'] ?? '')) > 700) {
            $errors[] = 'Catatan maksimal 700 karakter.';
        }
        if ($type === 'employees') {
            if ((string) $data['employee_name'] === '') {
                $errors[] = 'Nama karyawan wajib diisi.';
            }
            if ((string) $data['position_title'] === '') {
                $errors[] = 'Jabatan wajib diisi.';
            }
            if ((string) $data['email'] !== '' && !filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email karyawan tidak valid.';
            }
        }
        if ($type === 'business' && (string) $data['activity_name'] === '') {
            $errors[] = 'Nama aktivitas wajib diisi.';
        }
        if ($type === 'budgets') {
            if ((string) $data['category'] === '') {
                $errors[] = 'Kategori anggaran wajib diisi.';
            }
            if ((float) $data['amount'] <= 0) {
                $errors[] = 'Nominal anggaran harus lebih dari nol.';
            }
        }
        if ($type === 'budget_plans') {
            if ((string) $data['plan_no'] === '') {
                $errors[] = 'Nomor RAB wajib diisi.';
            }
            if ((string) $data['plan_title'] === '') {
                $errors[] = 'Judul RAB wajib diisi.';
            }
            if ($items === []) {
                $errors[] = 'Minimal satu item RAB wajib diisi.';
            }
        }
        return $errors;
    }

    private function formData(string $type, ?array $row, array $items): array
    {
        $defaults = ['business_unit_id' => (string) ($row['business_unit_id'] ?? current_business_unit_id()), 'notes' => (string) ($row['notes'] ?? '')];
        if ($type === 'employees') {
            return [
                ...$defaults,
                'employee_name' => old('employee_name', (string) ($row['employee_name'] ?? '')),
                'position_title' => old('position_title', (string) ($row['position_title'] ?? '')),
                'phone' => old('phone', (string) ($row['phone'] ?? '')),
                'email' => old('email', (string) ($row['email'] ?? '')),
                'status' => old('status', (string) ($row['status'] ?? 'ACTIVE')),
            ];
        }
        if ($type === 'business') {
            return [
                ...$defaults,
                'activity_name' => old('activity_name', (string) ($row['activity_name'] ?? '')),
                'activity_type' => old('activity_type', (string) ($row['activity_type'] ?? '')),
                'target_period' => old('target_period', (string) ($row['target_period'] ?? '')),
                'target_value' => old('target_value', (string) ($row['target_value'] ?? '0')),
                'status' => old('status', (string) ($row['status'] ?? 'RUNNING')),
            ];
        }
        if ($type === 'budgets') {
            return [
                ...$defaults,
                'budget_year' => old('budget_year', (string) ($row['budget_year'] ?? date('Y'))),
                'budget_month' => old('budget_month', (string) ($row['budget_month'] ?? '0')),
                'budget_type' => old('budget_type', (string) ($row['budget_type'] ?? 'EXPENSE')),
                'category' => old('category', (string) ($row['category'] ?? '')),
                'account_id' => old('account_id', (string) ($row['account_id'] ?? '0')),
                'amount' => old('amount', (string) ($row['amount'] ?? '0')),
                'status' => old('status', (string) ($row['status'] ?? 'ACTIVE')),
            ];
        }
        return [
            ...$defaults,
            'plan_no' => old('plan_no', (string) ($row['plan_no'] ?? $this->model()->nextPlanNo())),
            'plan_date' => old('plan_date', (string) ($row['plan_date'] ?? date('Y-m-d'))),
            'plan_title' => old('plan_title', (string) ($row['plan_title'] ?? '')),
            'activity_name' => old('activity_name', (string) ($row['activity_name'] ?? '')),
            'status' => old('status', (string) ($row['status'] ?? 'DRAFT')),
            'items' => $items,
        ];
    }

    private function readType(): string
    {
        $type = trim((string) get_query('type', 'employees'));
        return isset(self::PAGES[$type]) && $type !== 'budget_reports' ? $type : 'employees';
    }

    private function routeFor(string $type): string
    {
        return (string) (self::PAGES[$type]['route'] ?? '/business-employees');
    }

    private function createRoute(string $type): string
    {
        return '/business-operations/create?type=' . urlencode($type);
    }

    private function editRoute(string $type, int $id): string
    {
        return '/business-operations/edit?type=' . urlencode($type) . '&id=' . $id;
    }

    private function decimal(mixed $value): string
    {
        $raw = str_replace(['Rp', ' ', '.'], '', (string) $value);
        $raw = str_replace(',', '.', $raw);
        return is_numeric($raw) ? number_format((float) $raw, 2, '.', '') : '0.00';
    }
}
