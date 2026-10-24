<x-filament-panels::page>
    <x-filament::section :heading="$store->name">
        <p>
            {{ __('backoffice::panel.dashboard.status') }}:
            <strong data-store-status="{{ $store->status->value }}">{{ $statusLabel }}</strong>
        </p>

        <p class="mt-4">
            <a href="{{ $storeUrl }}" target="_blank" rel="noopener" class="underline">
                {{ __('backoffice::panel.dashboard.visit_store') }}
            </a>
        </p>
    </x-filament::section>
</x-filament-panels::page>
