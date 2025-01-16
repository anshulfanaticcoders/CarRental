<?php

namespace App\Http\Controllers;

use App\Models\BookingAddon;

use Illuminate\Http\Request;

class BookingAddonController extends Controller
{
    public function index()
    {
        return BookingAddon::all();
    }
}