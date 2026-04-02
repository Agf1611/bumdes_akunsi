<?php
declare(strict_types=1);

function listing_per_page_options(): array
{
    return [10, 50, 100];
}

function listing_resolve_per_page(int $default = 10): int
{
    $allowed = listing_per_page_options();
    $value = (int) get_query('per_page', (string) $default);
    return in_array($value, $allowed, true) ? $value : $default;
}

function listing_resolve_page(): int
{
    $page = (int) get_query('page', '1');
    return $page > 0 ? $page : 1;
}

function listing_paginate(array $items, int $defaultPerPage = 10): array
{
    $total = count($items);
    $perPage = listing_resolve_per_page($defaultPerPage);
    $page = listing_resolve_page();
    $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = max(0, ($page - 1) * $perPage);
    $pagedItems = array_slice($items, $offset, $perPage);

    return [
        'items' => $pagedItems,
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'from' => $total === 0 ? 0 : ($offset + 1),
        'to' => $total === 0 ? 0 : min($total, $offset + count($pagedItems)),
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
        'prev_page' => $page > 1 ? ($page - 1) : 1,
        'next_page' => $page < $totalPages ? ($page + 1) : $totalPages,
    ];
}

function listing_query_string(array $overrides = [], array $exclude = []): string
{
    $query = $_GET;
    foreach ($exclude as $key) {
        unset($query[$key]);
    }
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
            continue;
        }
        $query[$key] = (string) $value;
    }
    return http_build_query($query);
}
