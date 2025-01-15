<?php

namespace App\Http\Controllers;

use App\Models\BookingExtra;
use Illuminate\Http\Request;

class BookingExtraController extends Controller
{
    public function index()
    {
        return BookingExtra::all();
    }
}