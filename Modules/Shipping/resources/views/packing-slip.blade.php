<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('shipping::slip.title') }} {{ $order->number }}</title>
    <style>
        body { font-family: system-ui, "Segoe UI", Tahoma, sans-serif; margin: 2rem; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #999; padding: .4rem .6rem; text-align: right; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <h1>{{ __('shipping::slip.title') }} {{ $order->number }}</h1>
    <p>{{ $storeName }}</p>

    <h2>{{ __('shipping::slip.deliver_to') }}</h2>
    <p>
        {{ $order->shipping_address['recipient_name'] ?? '' }}<br>
        <span dir="ltr">{{ $order->shipping_address['phone'] ?? '' }}</span><br>
        {{ $order->shipping_address['governorate_name'] ?? '' }}، {{ $order->shipping_address['city'] ?? '' }}<br>
        {{ $order->shipping_address['address_line'] ?? '' }}
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('shipping::slip.product') }}</th>
                <th>{{ __('shipping::slip.sku') }}</th>
                <th>{{ __('shipping::slip.size_color') }}</th>
                <th>{{ __('shipping::slip.quantity') }}</th>
                <th>{{ __('shipping::slip.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td dir="ltr">{{ $item['sku'] }}</td>
                    <td>{{ $item['size'] }} / {{ $item['color'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td dir="ltr">{{ $item['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="no-print"><button onclick="window.print()">{{ __('shipping::slip.print') }}</button></p>
</body>
</html>
