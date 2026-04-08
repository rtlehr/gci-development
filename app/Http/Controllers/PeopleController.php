<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PeopleController extends Controller
{
    public function index()
    {
        return inertia('People/Index');
    }

    public function show($id)
    {
        return inertia('People/Show', ['id' => $id]);
    }

    public function create()
    {
        return inertia('People/Create');
    }


    public function store(Request $request)
    {
        // Validate and store the new person
    }   

    public function edit($id)
    {
        return inertia('People/Edit', ['id' => $id]);
    }

    public function update(Request $request, $id)
    {
        // Validate and update the person
    }

    public function destroy($id)
    {
        // Delete the person
    }



}
