<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait PaginatesOrAll
{
    protected function paginateOrAll(Builder $query, Request $request)
    {
        $paginate = $request->input('paginate');

        if ($paginate === 'all') {
            $items = $query->latest()->get();
            return [
                "current_page" => 1,
                "data" => $items,
                "first_page_url" => null,
                "from" => $items->isEmpty() ? null : 1,
                "last_page" => 1,
                "last_page_url" => null,
                "links" => [],
                "next_page_url" => null,
                "path" => $request->url(),
                "per_page" => $items->count(),
                "prev_page_url" => null,
                "to" => $items->count(),
                "total" => $items->count(),
            ];
        }

        $perPage = is_numeric($paginate) ? min((int) $paginate, 100) : 10;

        return $query->latest()->paginate($perPage);
    }
}
