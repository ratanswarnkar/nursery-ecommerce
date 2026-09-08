<?php

namespace App\Services\Invoice;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\Invoice\IneligibleForInvoiceException;
use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected InvoiceNumberGenerator $numberGenerator
    ) {}

    /**
     * Authoritative verified business details.
     */
    public function getBusinessDetails(): array
    {
        return [
            'name' => 'SUGANDHA FARMS AND NURSERY',
            'address' => 'Mann Enclave, near Gurukul, Vill, Khera Khurd, Delhi, 110082',
            'phone' => '098111 14365',
            'country' => 'India',
        ];
    }

    /**
     * Check if an invoice already exists for the order.
     */
    public function hasExistingInvoice(Order $order): bool
    {
        return $order->invoice()->exists() || Invoice::where('order_id', $order->id)->exists();
    }

    /**
     * Strict eligibility check for generating a tax invoice:
     * - If an invoice already exists: true (historical document is always retrievable).
     * - If no invoice exists: true ONLY when payment_status === PAID and status !== CANCELLED.
     * - Unpaid PENDING, FAILED, and CANCELLED orders without an invoice are strictly false.
     */
    public function canGenerateInvoice(Order $order): bool
    {
        if ($this->hasExistingInvoice($order)) {
            return true;
        }

        if ($order->status === OrderStatus::CANCELLED) {
            return false;
        }

        return $order->payment_status === PaymentStatus::PAID;
    }

    /**
     * Retrieve existing immutable invoice or generate a new one if eligible.
     * Guaranteed immutable: once created, existing invoice data is never mutated.
     */
    public function getOrCreateInvoiceForOrder(Order $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // Immutability: If invoice already exists, immediately return it
            $existingInvoice = Invoice::where('order_id', $lockedOrder->id)->first();
            if ($existingInvoice) {
                return $existingInvoice;
            }

            // Enforce strict eligibility server-side
            if (! $this->canGenerateInvoice($lockedOrder)) {
                throw IneligibleForInvoiceException::forOrder(
                    $lockedOrder->order_number,
                    'Tax invoice can only be generated for orders with payment status PAID.'
                );
            }

            $lockedOrder->loadMissing(['items', 'customer']);

            // Customer snapshot from authoritative order/customer data
            $customerSnapshot = [
                'customer_id' => $lockedOrder->customer_id,
                'name' => $lockedOrder->customer_name ?: ($lockedOrder->customer?->name ?? 'Valued Customer'),
                'phone' => $lockedOrder->customer_phone ?: ($lockedOrder->customer?->phone ?? ''),
                'email' => $lockedOrder->customer_email ?: ($lockedOrder->customer?->email ?? null),
            ];

            // Shipping and billing address snapshots
            $shippingAddressSnapshot = $lockedOrder->shipping_address_json ?? [];
            $billingAddressSnapshot = $lockedOrder->billing_address_json ?: $shippingAddressSnapshot;

            // Historical line items snapshot exclusively from OrderItem records
            $itemsSnapshot = $lockedOrder->items->map(function ($item) {
                return [
                    'order_item_id' => $item->id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'sku' => $item->sku,
                    'price' => (string) $item->price,
                    'quantity' => (int) $item->quantity,
                    'subtotal' => (string) $item->subtotal,
                    'tax_amount' => (string) $item->tax_amount,
                    'discount_amount' => (string) $item->discount_amount,
                    'total' => (string) $item->total,
                ];
            })->values()->all();

            $invoiceNumber = $this->numberGenerator->generate();

            return Invoice::create([
                'order_id' => $lockedOrder->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => Carbon::now(),
                'currency' => $lockedOrder->currency ?? 'INR',
                'subtotal' => $lockedOrder->subtotal,
                'tax_amount' => $lockedOrder->tax_amount,
                'shipping_amount' => $lockedOrder->shipping_amount,
                'discount_amount' => $lockedOrder->discount_amount,
                'grand_total' => $lockedOrder->grand_total,
                'customer_snapshot' => $customerSnapshot,
                'billing_address_snapshot' => $billingAddressSnapshot,
                'shipping_address_snapshot' => $shippingAddressSnapshot,
                'items_snapshot' => $itemsSnapshot,
                'payment_status_snapshot' => $lockedOrder->payment_status instanceof PaymentStatus
                    ? $lockedOrder->payment_status->value
                    : (string) $lockedOrder->payment_status,
                'order_status_snapshot' => $lockedOrder->status instanceof OrderStatus
                    ? $lockedOrder->status->value
                    : (string) $lockedOrder->status,
                'metadata' => null,
            ]);
        });
    }

    /**
     * Render the PDF binary content for an invoice.
     */
    public function renderPdf(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'business' => $this->getBusinessDetails(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Download or stream response for invoice PDF.
     */
    public function downloadPdfResponse(Invoice $invoice, ?string $filename = null): Response
    {
        $filename = $filename ?? "invoice-{$invoice->invoice_number}.pdf";

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'business' => $this->getBusinessDetails(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }
}
