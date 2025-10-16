<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SimpananController extends Controller
{
    public function index(Request $r){
        $savings = $r->user()->savings()->get();
        return response()->json(['savings'=>$savings]);
    }
}
