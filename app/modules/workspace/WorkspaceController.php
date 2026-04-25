<?php

declare(strict_types=1);

final class WorkspaceController extends Controller
{
    private function wantsJson(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    private function redirectBack(string $fallback = '/dashboard'): never
    {
        $referer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
        if ($referer !== '') {
            $this->redirect($referer);
        }

        $this->redirect($fallback);
    }

    private function model(): WorkspaceModel
    {
        return new WorkspaceModel(Database::getInstance(db_config()));
    }

    public function search(): void
    {
        if (!Auth::check()) {
            json_response(['ok' => false, 'message' => 'Unauthenticated'], 401);
        }

        $query = trim((string) get_query('q', ''));
        $results = [
            'menus' => [],
            'journals' => [],
            'accounts' => [],
            'periods' => [],
            'units' => [],
            'users' => [],
        ];

        foreach (workspace_menu_index() as $item) {
            if ($query === '' || stripos((string) ($item['title'] ?? ''), $query) !== false) {
                $results['menus'][] = $item;
            }
        }
        $results['menus'] = array_slice($results['menus'], 0, 8);

        if ($query !== '') {
            $results['journals'] = array_map(
                static fn (array $row): array => [
                    'title' => (string) ($row['journal_no'] ?? '-'),
                    'subtitle' => (string) ($row['description'] ?? ''),
                    'meta' => (string) ($row['journal_date'] ?? ''),
                    'path' => '/journals/detail?id=' . (int) ($row['id'] ?? 0),
                ],
                $this->model()->searchJournals($query)
            );
            $results['accounts'] = array_map(
                static fn (array $row): array => [
                    'title' => (string) (($row['account_code'] ?? '-') . ' - ' . ($row['account_name'] ?? '')),
                    'subtitle' => (string) ($row['account_type'] ?? ''),
                    'path' => '/coa',
                ],
                $this->model()->searchAccounts($query)
            );
            $results['periods'] = array_map(
                static fn (array $row): array => [
                    'title' => (string) (($row['period_code'] ?? '-') . ' - ' . ($row['period_name'] ?? '')),
                    'subtitle' => 'Status: ' . (string) ($row['status'] ?? '-'),
                    'path' => '/periods/checklist?id=' . (int) ($row['id'] ?? 0),
                ],
                $this->model()->searchPeriods($query)
            );
            $results['units'] = array_map(
                static fn (array $row): array => [
                    'title' => (string) (($row['unit_code'] ?? '-') . ' - ' . ($row['unit_name'] ?? '')),
                    'path' => '/business-units',
                ],
                $this->model()->searchBusinessUnits($query)
            );
            $results['users'] = array_map(
                static fn (array $row): array => [
                    'title' => (string) ($row['full_name'] ?? '-'),
                    'subtitle' => (string) (($row['username'] ?? '-') . ' · ' . ($row['role_name'] ?? '')),
                    'path' => '/user-accounts/edit?id=' . (int) ($row['id'] ?? 0),
                ],
                $this->model()->searchUsers($query)
            );
        }

        json_response([
            'ok' => true,
            'query' => $query,
            'results' => $results,
        ]);
    }

    public function toggleFavorite(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            if ($this->wantsJson()) {
                json_response(['ok' => false, 'message' => 'Token keamanan tidak valid.'], 419);
            }
            flash('error', 'Token keamanan tidak valid. Silakan muat ulang halaman lalu coba lagi.');
            $this->redirectBack();
        }

        $title = trim((string) post('title', ''));
        $path = trim((string) post('path', ''));
        if ($path === '') {
            if ($this->wantsJson()) {
                json_response(['ok' => false, 'message' => 'Path favorit belum dikirim.'], 422);
            }
            flash('error', 'Halaman favorit belum dapat disimpan karena path tidak ditemukan.');
            $this->redirectBack();
        }

        $favorites = workspace_favorite_pages();
        $normalizedPath = workspace_normalize_path($path);
        $exists = false;
        $updated = [];
        foreach ($favorites as $item) {
            if ((string) ($item['path'] ?? '') === $normalizedPath) {
                $exists = true;
                continue;
            }
            $updated[] = $item;
        }

        if (!$exists) {
            array_unshift($updated, [
                'title' => $title !== '' ? $title : 'Favorit',
                'path' => $normalizedPath,
                'saved_at' => date('Y-m-d H:i:s'),
            ]);
        }

        workspace_preference_put('favorite_pages', array_slice($updated, 0, 10));

        if (!$this->wantsJson()) {
            flash('success', !$exists ? 'Halaman berhasil disimpan ke favorit.' : 'Halaman dihapus dari favorit.');
            $this->redirectBack($normalizedPath);
        }

        json_response([
            'ok' => true,
            'favorited' => !$exists,
            'items' => workspace_favorite_pages(),
        ]);
    }

    public function saveFilter(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            if ($this->wantsJson()) {
                json_response(['ok' => false, 'message' => 'Token keamanan tidak valid.'], 419);
            }
            flash('error', 'Token keamanan tidak valid. Silakan muat ulang halaman lalu coba lagi.');
            $this->redirectBack();
        }

        $name = trim((string) post('name', ''));
        $path = trim((string) post('path', ''));
        $label = trim((string) post('label', ''));

        if ($name === '' || $path === '') {
            if ($this->wantsJson()) {
                json_response(['ok' => false, 'message' => 'Nama filter dan path wajib diisi.'], 422);
            }
            flash('error', 'Nama filter dan path wajib diisi.');
            $this->redirectBack();
        }

        $filters = workspace_saved_filters();
        $normalizedPath = workspace_normalize_path($path);
        $filters = array_values(array_filter($filters, static fn (array $item): bool => (string) ($item['name'] ?? '') !== $name));
        array_unshift($filters, [
            'name' => $name,
            'label' => $label !== '' ? $label : $name,
            'path' => $normalizedPath,
            'saved_at' => date('Y-m-d H:i:s'),
        ]);

        workspace_preference_put('saved_filters', array_slice($filters, 0, 12));

        if (!$this->wantsJson()) {
            flash('success', 'Filter berhasil disimpan.');
            $this->redirectBack($normalizedPath);
        }

        json_response([
            'ok' => true,
            'items' => workspace_saved_filters(),
        ]);
    }
}
