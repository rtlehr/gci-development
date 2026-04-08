<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PositionsController extends Controller
{
    public function index()
    {
        return inertia('Positions/Index');
    }

    public function create()
    {
        return inertia('Positions/Create');
    }

    public function store(Request $request)
    {
        // Validate and store the new person
    }   
    
}
