<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ShippingMethod;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Events\PackagePrealerted;
use App\Services\DbService\PackageService;

class PackageController extends Controller
{
        protected $dbService;

    public function __construct(PackageService $dbService)
    {
        $this->dbService = $dbService;
    }

    public function store(StorePackageRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_id'] = Auth::id(); 
            $package = $this->dbService->createPackage($data);

            
            $this->dbService->createFirstStatusHistory($package->id);

            PackagePrealerted::dispatch($package);

            return redirect()->route('mis-paquetes')->with('success', 'Paquete creado exitosamente.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al crear el paquete: ' . $e->getMessage())->withInput();
        }
    }
    /**
     * Display user packages with search and filters
     */
    public function userPackages(Request $request)
    {
        $userId = Auth::id();
        $query = Package::where('user_id', $userId);
       
        // Búsqueda por tracking
        if ($request->filled('search')) {
            $query->where('tracking', 'like', '%' . $request->search . '%');
        }

        // Filtrar por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por método de envío
        if ($request->filled('shipping_method')) {
            $query->where('shipping_method_id', $request->shipping_method);
        }

        // Ordenar por fecha más reciente
        $packages = $query->with('shippingMethod', 'statusHistories')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

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

        $shippingMethods = ShippingMethod::where('active', true)->get();
        

        return view('mis-paquetes', compact('packages', 'statuses', 'shippingMethods'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shippingMethods = ShippingMethod::where('active', true)->get();
        return view('package-create', compact('shippingMethods'));
    }



    /**
     * Display the specified resource.
     */
    public function show(Package $package)
    {
        $this->authorize('view', $package);

        $package->load(['shippingMethod', 'statusHistories.changedBy', 'user']);

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

        $shippingMethods = ShippingMethod::where('active', true)->get();

        return view('package-details', compact('package', 'statuses', 'shippingMethods'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Package $package)
    {
        $this->authorize('update', $package);

        $shippingMethods = ShippingMethod::where('active', true)->get();

        return view('package-edit', compact('package', 'shippingMethods'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePackageRequest $request, Package $package)
    {
        try {
            $this->authorize('update', $package);

            $package->update($request->validated());

            return redirect()->route('package.show', $package)
                ->with('success', 'Paquete actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Package $package)
    {
        try {
            $this->authorize('delete', $package);

            $package->delete();

            return redirect()->route('mis-paquetes')
                ->with('success', 'Paquete eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }
}
