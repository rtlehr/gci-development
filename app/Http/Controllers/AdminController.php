<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\CurrentUser;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        // 🔒 Protect this method
        abort_if(
            !CurrentUser::hasPermission($request, 'view_admin'),
            403,
            'Unauthorized'
        );

        return inertia('Admin/Index');
    }
}