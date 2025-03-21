<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $faqs = Faq::when($search, function ($query) use ($search) {
            $query->where('question', 'like', "%{$search}%")
                ->orWhere('answer', 'like', "%{$search}%");
        })->latest()->get();

        return Inertia::render('AdminDashboardPages/Settings/Faq/Index', [
            'faqs' => $faqs,
            'search' => $search
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        Faq::create([
            'question' => $request->input('question'),
            'answer' => $request->input('answer')
        ]);

        return redirect()->route('admin.settings.faq.index')->with('success', 'FAQ created successfully.');
    }

    public function update(Request $request, Faq $faq)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        $faq->update([
            'question' => $request->input('question'),
            'answer' => $request->input('answer')
        ]);

        return redirect()->route('admin.settings.faq.index')->with('success', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return redirect()->route('admin.settings.faq.index')->with('success', 'FAQ deleted successfully.');
    }


    public function getFaqs()
    {
        $faqs = Faq::orderBy('created_at', 'asc')->get();
        return response()->json($faqs);
    }


}
