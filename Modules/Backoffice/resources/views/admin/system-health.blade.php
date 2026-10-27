<x-filament-panels::page>
    <x-filament::section>
        <dl class="grid gap-4 md:grid-cols-2">
            @foreach ($this->rows() as $row)
                <div data-health-check class="flex items-center justify-between rounded border p-3">
                    <dt>{{ $row['label'] }}</dt>
                    <dd class="flex items-center gap-2">
                        <span>{{ $row['value'] }}</span>
                        <strong data-ok="{{ $row['ok'] ? '1' : '0' }}" class="{{ $row['ok'] ? 'text-success-600' : 'text-danger-600' }}">
                            {{ $row['ok'] ? __('backoffice::panel.health.ok') : __('backoffice::panel.health.attention') }}
                        </strong>
                    </dd>
                </div>
            @endforeach
        </dl>
    </x-filament::section>
</x-filament-panels::page>
