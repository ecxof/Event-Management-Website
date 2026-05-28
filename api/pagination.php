<?php

declare(strict_types=1);

// Read page and limit from the query string while enforcing sensible defaults and max limit.
function readPaginationParams(int $defaultLimit = 6, int $maxLimit = 50): array
{
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);

    $page = $page !== false && $page !== null && $page > 0 ? $page : 1;
    $limit = $limit !== false && $limit !== null && $limit > 0 ? $limit : $defaultLimit;
    $limit = min($limit, $maxLimit);

    return [
        'page' => $page,
        'limit' => $limit,
        'skip' => ($page - 1) * $limit,
    ];
}

// Build the pagination metadata object returned by list endpoints.
function paginationMeta(int $page, int $limit, int $total): array
{
    $totalPages = max(1, (int) ceil($total / $limit));
    $currentPage = min($page, $totalPages);

    return [
        'page' => $currentPage,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => $totalPages,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
    ];
}

// Clamp a requested page to the actual available page range before querying data.
function clampPagination(array $pagination, int $total): array
{
    $totalPages = max(1, (int) ceil($total / $pagination['limit']));
    $page = min($pagination['page'], $totalPages);

    return [
        ...$pagination,
        'page' => $page,
        'skip' => ($page - 1) * $pagination['limit'],
    ];
}
