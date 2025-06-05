<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schema;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchemaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $schemas = Schema::latest()->paginate(10);
        return Inertia::render('AdminDashboardPages/Schema/Index', [
            'schemas' => $schemas,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('AdminDashboardPages/Schema/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        Schema::create([
            'name' => $request->name,
            'type' => $request->type,
            'content' => $request->content,
            'is_active' => $request->input('is_active', true),
        ]);

        return redirect()->route('admin.schemas.index')->with('success', 'Schema created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Schema $schema)
    {
        // Not typically used for admin CRUD, redirect to edit or index.
        return redirect()->route('admin.schemas.edit', $schema);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schema $schema)
    {
        return Inertia::render('AdminDashboardPages/Schema/Edit', [
            'schema' => $schema,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Schema $schema)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $schema->update([
            'name' => $request->name,
            'type' => $request->type,
            'content' => $request->content,
            'is_active' => $request->input('is_active', $schema->is_active),
        ]);

        return redirect()->route('admin.schemas.index')->with('success', 'Schema updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schema $schema)
    {
        $schema->delete();
        return redirect()->route('admin.schemas.index')->with('success', 'Schema deleted successfully.');
    }
}
