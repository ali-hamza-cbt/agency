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
            return $query->latest()->get();
        }

        $perPage = is_numeric($paginate) ? min((int) $paginate, 100) : 10;

        return $query->latest()->paginate($perPage);
    }
}
