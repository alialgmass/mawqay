<x-filament::section :heading="__('shipping::shipment.heading')" data-shipment-panel>
    @if ($shipment)
        <dl class="grid gap-2 text-sm md:grid-cols-4">
            <div><dt class="font-medium">{{ __('shipping::shipment.courier') }}</dt><dd>{{ $shipment->courier_name }}</dd></div>
            <div><dt class="font-medium">{{ __('shipping::shipment.tracking_number') }}</dt><dd dir="ltr">{{ $shipment->tracking_number ?? '—' }}</dd></div>
            <div><dt class="font-medium">{{ __('shipping::shipment.shipped_at') }}</dt><dd>{{ $shipment->shipped_at->toDateTimeString() }}</dd></div>
            <div><dt class="font-medium">{{ __('shipping::shipment.delivered_at') }}</dt><dd>{{ $shipment->delivered_at?->toDateTimeString() ?? '—' }}</dd></div>
            @if ($shipment->tracking_url)
                <div class="md:col-span-4"><a href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener" class="underline">{{ __('shipping::shipment.track') }}</a></div>
            @endif
            @if ($shipment->note)
                <div class="md:col-span-4">{{ $shipment->note }}</div>
            @endif
        </dl>
    @else
        <p class="text-sm">{{ __('shipping::shipment.none') }}</p>
    @endif

    @if ($slipUrl)
        <p class="mt-3"><a href="{{ $slipUrl }}" target="_blank" rel="noopener" class="underline">{{ __('shipping::shipment.packing_slip') }}</a></p>
    @endif
</x-filament::section>
