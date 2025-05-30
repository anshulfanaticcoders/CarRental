<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeaderFooterScript;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HeaderFooterScriptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $script = HeaderFooterScript::first(); // Get the first (should be only) script
        $headerFooterScripts = $script ? HeaderFooterScript::where('id', $script->id)->paginate(1) : HeaderFooterScript::paginate(1); // Paginate for consistency with view

        return Inertia::render('AdminDashboardPages/Settings/HeaderFooter/Index', [
            'headerFooterScripts' => $headerFooterScripts,
            'existingScript' => $script, // Pass the script itself, or null
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $existingScript = HeaderFooterScript::first();
        if ($existingScript) {
            return redirect()->route('admin.header-footer-scripts.edit', $existingScript->id)
                             ->with('info', 'A script entry already exists. You can edit it here.');
        }
        return Inertia::render('AdminDashboardPages/Settings/HeaderFooter/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $existingScript = HeaderFooterScript::first();
        if ($existingScript) {
            return redirect()->route('admin.header-footer-scripts.edit', $existingScript->id)
                             ->with('error', 'Cannot create new script. One already exists. Please edit the existing one.');
        }

        $request->validate([
            'header_script' => 'nullable|string',
            'footer_script' => 'nullable|string',
        ]);

        HeaderFooterScript::create($request->only('header_script', 'footer_script'));

        return redirect()->route('admin.header-footer-scripts.index')
                         ->with('success', 'Header/Footer script created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(HeaderFooterScript $headerFooterScript)
    {
        // Ensure we always edit the first one if accessed directly by ID that might not be the first.
        $scriptToEdit = HeaderFooterScript::first();
        if (!$scriptToEdit) {
             // This case should ideally not be reached if create() redirects.
            return redirect()->route('admin.header-footer-scripts.create')->with('info', 'No script found. Create one.');
        }
        // If an ID is passed, but it's not the first one (somehow), redirect to edit the first one.
        // Or, if the passed ID is the first one, proceed.
        if ($scriptToEdit->id !== $headerFooterScript->id && HeaderFooterScript::count() > 0) {
             return redirect()->route('admin.header-footer-scripts.edit', $scriptToEdit->id);
        }
        return redirect()->route('admin.header-footer-scripts.edit', $scriptToEdit->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HeaderFooterScript $headerFooterScript) // Route model binding
    {
        // Ensure we are always editing the *first* script entry if multiple somehow exist
        // or if a specific ID is requested.
        $scriptToEdit = HeaderFooterScript::first();

        if (!$scriptToEdit) {
            // If no script exists at all, redirect to create page.
            return redirect()->route('admin.header-footer-scripts.create')
                             ->with('info', 'No script found. Please create one.');
        }

        // If the requested $headerFooterScript via route model binding is not the $scriptToEdit (the first one),
        // then redirect to the edit page of the $scriptToEdit.
        if ($scriptToEdit->id !== $headerFooterScript->id) {
            return redirect()->route('admin.header-footer-scripts.edit', $scriptToEdit->id);
        }
        
        return Inertia::render('AdminDashboardPages/Settings/HeaderFooter/Edit', [
            'headerFooterScript' => $scriptToEdit // Pass the first script
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HeaderFooterScript $headerFooterScript)
    {
         // Ensure we are always updating the *first* script entry.
        $scriptToUpdate = HeaderFooterScript::first();

        if (!$scriptToUpdate) {
            return redirect()->route('admin.header-footer-scripts.create')
                             ->with('error', 'No script found to update. Please create one first.');
        }
        // If the $headerFooterScript from route model binding is not the first one,
        // still update the first one.
        if ($scriptToUpdate->id !== $headerFooterScript->id) {
             // This is an edge case, ideally the edit form always points to the first script.
            // We'll update $scriptToUpdate.
        }

        $request->validate([
            'header_script' => 'nullable|string',
            'footer_script' => 'nullable|string',
        ]);

        $scriptToUpdate->update($request->only('header_script', 'footer_script'));

        return redirect()->route('admin.header-footer-scripts.index')
                         ->with('success', 'Header/Footer script updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HeaderFooterScript $headerFooterScript)
    {
        // Ensure we are always deleting the *first* script entry.
        $scriptToDelete = HeaderFooterScript::first();
        
        if ($scriptToDelete) {
            // If the $headerFooterScript from route model binding is not the first one,
            // still delete the first one.
             if ($scriptToDelete->id !== $headerFooterScript->id && HeaderFooterScript::count() > 0) {
                // Log this unusual situation if necessary
             }
            $scriptToDelete->delete();
            return redirect()->route('admin.header-footer-scripts.index')
                             ->with('success', 'Header/Footer script deleted successfully.');
        }
        
        return redirect()->route('admin.header-footer-scripts.index')
                         ->with('error', 'No script found to delete.');
    }
}
