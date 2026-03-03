<?php

namespace App\Http\Controllers;

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
        $packages = $user->packages;

        $statuses = [
            'prealerted' => 'Prealertado',
            'received_in_warehouse' => 'Recibido en Bodega',
            'assigned_flight' => 'Asignado a Vuelo',
            'received_in_customs' => 'Recibido en Aduanas',
            'received_in_business' => 'Recibido en Negocio',
            'ready_to_deliver' => 'Listo para Entregar',
            'delivered' => 'Entregado',
            'canceled' => 'Cancelado',
        ];

        return view('panel', compact('packages', 'statuses'));
    }
}
