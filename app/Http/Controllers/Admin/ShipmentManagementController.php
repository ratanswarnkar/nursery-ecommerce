<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShippingStatus;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentManagementController extends Controller
{
    /**
     * Display a listing of all shipments and tracking information.
     */
    public function index(Request $request): View
    {
        $query = Shipment::with(['order.customer'])->latest();

        // Search tracking number, carrier, or order number
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('carrier', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by shipping status
        if ($status = $request->query('status')) {
            $query->where('shipping_status', $status);
        }

        // Metrics
        $metrics = [
            'total' => Shipment::count(),
            'fulfilled' => Shipment::where('shipping_status', ShippingStatus::FULFILLED)->count(),
            'in_progress' => Shipment::where('shipping_status', ShippingStatus::PARTIALLY_FULFILLED)->count(),
            'returned' => Shipment::where('shipping_status', ShippingStatus::RETURNED)->count(),
        ];

        $shipments = $query->paginate(15)->withQueryString();
        $statuses = ShippingStatus::cases();

        return view('admin.shipments.index', compact('shipments', 'metrics', 'statuses'));
    }
}
