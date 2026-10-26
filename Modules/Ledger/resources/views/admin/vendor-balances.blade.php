<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($this->totals() as $label => $value)
            <x-filament::section>
                <div class="text-sm text-gray-500">{{ $label }}</div>
                <div class="text-2xl font-semibold">{{ $value }}</div>
            </x-filament::section>
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
