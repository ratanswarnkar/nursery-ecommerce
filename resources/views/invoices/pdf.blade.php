<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tax Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 28px 32px;
            size: a4 portrait;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #2b2d42;
            margin: 0;
            padding: 0;
        }
        .header-table, .meta-table, .address-table, .items-table, .totals-table, .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .brand-title {
            font-size: 18px;
            font-weight: bold;
            color: #1b4332;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .brand-subtitle {
            font-size: 10px;
            color: #555;
            line-height: 1.4;
        }
        .doc-title {
            font-size: 20px;
            font-weight: bold;
            color: #2d6a4f;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .doc-meta {
            text-align: right;
            font-size: 10px;
            color: #444;
            line-height: 1.5;
        }
        .doc-meta strong {
            color: #111;
        }
        .divider {
            border-bottom: 2px solid #2d6a4f;
            margin: 14px 0 16px 0;
        }
        .address-box {
            padding: 10px 12px;
            background-color: #f8faf9;
            border: 1px solid #d8f3dc;
            border-radius: 4px;
            min-height: 95px;
        }
        .address-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #2d6a4f;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            border-bottom: 1px dashed #b7e4c7;
            padding-bottom: 3px;
        }
        .address-content {
            font-size: 10px;
            line-height: 1.45;
            color: #333;
        }
        .items-table {
            margin-top: 18px;
            border: 1px solid #d8e2dc;
        }
        .items-table th {
            background-color: #1b4332;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            border: 1px solid #1b4332;
        }
        .items-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #e9ecef;
            border-left: 1px solid #f1f3f5;
            border-right: 1px solid #f1f3f5;
            font-size: 10px;
        }
        .items-table tr:nth-child(even) td {
            background-color: #fafbfc;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .item-name {
            font-weight: bold;
            color: #111;
        }
        .item-variant {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }
        .item-sku {
            font-size: 8.5px;
            color: #888;
        }
        .summary-container {
            margin-top: 14px;
            width: 100%;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 8.5px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-paid {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        .badge-status {
            background-color: #e2e3e5;
            color: #41464b;
            border: 1px solid #d3d6d8;
        }
        .totals-table {
            width: 55%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 10px;
        }
        .totals-table tr.grand-total td {
            font-size: 12px;
            font-weight: bold;
            color: #1b4332;
            border-top: 2px solid #2d6a4f;
            border-bottom: 2px solid #2d6a4f;
            padding: 7px 8px;
            background-color: #f1faee;
        }
        .footer-section {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #d8e2dc;
            font-size: 9px;
            color: #6c757d;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="brand-title">{{ $business['name'] ?? 'SUGANDHA FARMS AND NURSERY' }}</div>
                <div class="brand-subtitle">
                    {{ $business['address'] ?? 'Mann Enclave, near Gurukul, Vill, Khera Khurd, Delhi, 110082' }}<br>
                    Phone: {{ $business['phone'] ?? '098111 14365' }} &bull; {{ $business['country'] ?? 'India' }}
                </div>
            </td>
            <td style="width: 45%;">
                <div class="doc-title">TAX INVOICE</div>
                <div class="doc-meta">
                    <strong>Invoice No:</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Invoice Date:</strong> {{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y, h:i A') : date('d M Y') }}<br>
                    <strong>Order Number:</strong> {{ $invoice->order->order_number ?? 'N/A' }}<br>
                    <strong>Order Date:</strong> {{ $invoice->order && $invoice->order->created_at ? $invoice->order->created_at->format('d M Y') : 'N/A' }}<br>
                    <strong>Payment:</strong> <span class="badge badge-paid">{{ strtoupper($invoice->payment_status_snapshot) }}</span> &nbsp;
                    <strong>Status:</strong> <span class="badge badge-status">{{ strtoupper($invoice->order_status_snapshot) }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Billing & Shipping Information -->
    <table class="address-table">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div class="address-box">
                    <div class="address-title">Billed To</div>
                    <div class="address-content">
                        <strong>{{ $invoice->billing_address_snapshot['full_name'] ?? ($invoice->customer_snapshot['name'] ?? 'Customer') }}</strong><br>
                        @if(!empty($invoice->billing_address_snapshot['address_line1']))
                            {{ $invoice->billing_address_snapshot['address_line1'] }}<br>
                        @endif
                        @if(!empty($invoice->billing_address_snapshot['address_line2']))
                            {{ $invoice->billing_address_snapshot['address_line2'] }}<br>
                        @endif
                        @if(!empty($invoice->billing_address_snapshot['city']) || !empty($invoice->billing_address_snapshot['postal_code']))
                            {{ $invoice->billing_address_snapshot['city'] ?? '' }}{{ !empty($invoice->billing_address_snapshot['state']) ? ', ' . $invoice->billing_address_snapshot['state'] : '' }} - {{ $invoice->billing_address_snapshot['postal_code'] ?? '' }}<br>
                        @endif
                        Phone: {{ $invoice->billing_address_snapshot['phone'] ?? ($invoice->customer_snapshot['phone'] ?? 'N/A') }}
                        @if(!empty($invoice->customer_snapshot['email']))
                            <br>Email: {{ $invoice->customer_snapshot['email'] }}
                        @endif
                    </div>
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;">
                <div class="address-box">
                    <div class="address-title">Shipped To</div>
                    <div class="address-content">
                        <strong>{{ $invoice->shipping_address_snapshot['full_name'] ?? ($invoice->customer_snapshot['name'] ?? 'Recipient') }}</strong><br>
                        @if(!empty($invoice->shipping_address_snapshot['address_line1']))
                            {{ $invoice->shipping_address_snapshot['address_line1'] }}<br>
                        @endif
                        @if(!empty($invoice->shipping_address_snapshot['address_line2']))
                            {{ $invoice->shipping_address_snapshot['address_line2'] }}<br>
                        @endif
                        @if(!empty($invoice->shipping_address_snapshot['city']) || !empty($invoice->shipping_address_snapshot['postal_code']))
                            {{ $invoice->shipping_address_snapshot['city'] ?? '' }}{{ !empty($invoice->shipping_address_snapshot['state']) ? ', ' . $invoice->shipping_address_snapshot['state'] : '' }} - {{ $invoice->shipping_address_snapshot['postal_code'] ?? '' }}<br>
                        @endif
                        Phone: {{ $invoice->shipping_address_snapshot['phone'] ?? ($invoice->customer_snapshot['phone'] ?? 'N/A') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 45%;">Item Description</th>
                <th style="width: 15%;">SKU</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 12%;" class="text-right">Unit Price</th>
                <th style="width: 13%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items_snapshot ?? [] as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $item['product_name'] ?? 'Product' }}</div>
                        @if(!empty($item['variant_name']))
                            <div class="item-variant">Variant: {{ $item['variant_name'] }}</div>
                        @endif
                    </td>
                    <td><span class="item-sku">{{ $item['sku'] ?? 'N/A' }}</span></td>
                    <td class="text-center">{{ $item['quantity'] ?? 1 }}</td>
                    <td class="text-right">&#8377;{{ number_format((float) ($item['price'] ?? 0), 2) }}</td>
                    <td class="text-right">&#8377;{{ number_format((float) ($item['total'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 14px; color: #888;">No items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Totals & Payment Summary -->
    <div class="summary-container">
        <table style="width: 100%;">
            <tr>
                <td style="width: 45%; vertical-align: top; padding-right: 15px;">
                    <div style="font-size: 9.5px; color: #555; background-color: #fdfefe; border: 1px solid #e9ecef; border-radius: 4px; padding: 10px;">
                        <strong style="color: #2d6a4f;">Payment Terms & Notes:</strong><br>
                        This transaction has been successfully processed and verified. All plants and botanical products are inspected for quality prior to dispatch.<br><br>
                        <em>Currency: Indian Rupee ({{ $invoice->currency }})</em>
                    </div>
                </td>
                <td style="width: 55%; vertical-align: top;">
                    <table class="totals-table">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-right">&#8377;{{ number_format((float) $invoice->subtotal, 2) }}</td>
                        </tr>
                        @if((float) $invoice->discount_amount > 0)
                            <tr>
                                <td style="color: #2d6a4f;">Discount:</td>
                                <td class="text-right" style="color: #2d6a4f;">- &#8377;{{ number_format((float) $invoice->discount_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Shipping & Handling:</td>
                            <td class="text-right">
                                @if((float) $invoice->shipping_amount > 0)
                                    &#8377;{{ number_format((float) $invoice->shipping_amount, 2) }}
                                @else
                                    <span style="color: #2d6a4f; font-weight: bold;">FREE</span>
                                @endif
                            </td>
                        </tr>
                        @if((float) $invoice->tax_amount > 0)
                            <tr>
                                <td>Taxes:</td>
                                <td class="text-right">&#8377;{{ number_format((float) $invoice->tax_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="grand-total">
                            <td>Grand Total:</td>
                            <td class="text-right">&#8377;{{ number_format((float) $invoice->grand_total, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer Notice -->
    <div class="footer-section">
        Thank you for choosing <strong>Sugandha Farms and Nursery</strong> for your green living needs.<br>
        This is a computer-generated tax invoice and requires no physical signature.<br>
        For inquiries regarding your order, please contact our support at <strong>{{ $business['phone'] ?? '098111 14365' }}</strong>.
    </div>

</body>
</html>
