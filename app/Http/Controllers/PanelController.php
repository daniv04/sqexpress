<?php

namespace App\Http\Controllers;

use App\Enums\PackageStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PanelController extends Controller
{
    /**
     * Display the user's panel.
     */
    public function index()
    {
        $user = Auth::user();
        $packages = $user->packages()->visibleToClient()->get();

        $statuses = PackageStatus::labels();

        return view('panel', compact('packages', 'statuses'));
    }
}
