<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactFormController extends Controller
{
    public function store(Request $request)
    {
        // Validate the form submission
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:2000'
        ]);

        // If validation fails, return back with errors
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create the contact submission
        $submission = ContactSubmission::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'message' => $request->input('message')
        ]);

        // Optional: Add a notification or email logic here
        // For example, you might want to send an admin notification

        // Redirect back with a success message
        return back()->with('success', 'Your message has been sent successfully!');
    }

    // Optional: Add a method to list submissions in admin panel
    public function index()
    {
        $submissions = ContactSubmission::latest()->paginate(20);
        return view('admin.contact-submissions', compact('submissions'));
    }
}