<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $guard = $request->query('guard_name', config('auth.defaults.guard', 'web'));

        $items = Permission::query()
            ->where('guard_name', $guard)
            ->when($q !== '', fn ($qr) => $qr->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
}