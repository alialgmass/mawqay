<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3">
        <x-filament::section>
            <div class="text-sm text-gray-500">{{ __('ledger::ledger.vendor.available') }}</div>
            <div class="text-2xl font-semibold" dir="ltr">{{ $this->balance()->available()->format() }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">{{ __('ledger::ledger.balances.payout_pending') }}</div>
            <div class="text-2xl font-semibold" dir="ltr">{{ $this->balance()->payoutPending()->format() }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">{{ __('ledger::ledger.vendor.paid') }}</div>
            <div class="text-2xl font-semibold" dir="ltr">{{ $this->balance()->paid()->format() }}</div>
        </x-filament::section>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
