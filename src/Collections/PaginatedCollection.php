<?php

namespace Ifm\Collections;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

if (!defined("ABSPATH")) {
    exit();
}

class PaginatedCollection extends Collection
{
    public function paginate(
        ?int $perPage = null,
        ?int $page = null,
        string $pageName = "page"
    ): LengthAwarePaginator {
        $perPage ??= 15;
        $page ??= LengthAwarePaginator::resolveCurrentPage($pageName);

        $total = $this->count();

        $items = $this->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            "path" => LengthAwarePaginator::resolveCurrentPath(),
            "pageName" => $pageName,
        ]);
    }
}
