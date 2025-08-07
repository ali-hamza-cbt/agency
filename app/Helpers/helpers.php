<?php

use Illuminate\Support\Facades\Auth;
use App\Models\User;

if (!function_exists('currentAccount')) {
    /**
     * Get the current main account context for the authenticated user.
     *
     * - If the user is an independent role (e.g., super_admin, retailer), returns self.
     * - If the user is a child of another user (e.g., admin under agency), returns the parent account.
     *
     * @return \App\Models\User|null
     */
    function currentAccount(): ?User
    {
        $user = Auth::user();

        if ($user?->role === 'super_admin') return $user;
        if ($user?->role === 'agency') return $user;
        if ($user?->role === 'admin') return $user->agency;

        return null;
    }
}
