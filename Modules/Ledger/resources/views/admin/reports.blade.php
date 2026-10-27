<x-filament-panels::page>
    <div class="flex flex-wrap items-end gap-4">
        <label class="text-sm">
            <span class="block">{{ __('ledger::reports.from') }}</span>
            <input type="date" wire:model.live="from" class="rounded border-gray-300 text-sm">
        </label>
        <label class="text-sm">
            <span class="block">{{ __('ledger::reports.until') }}</span>
            <input type="date" wire:model.live="until" class="rounded border-gray-300 text-sm">
        </label>
    </div>

    @php($report = $this->report())

    <x-filament::section>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-start">
                    <th class="text-start">{{ __('ledger::reports.store') }}</th>
                    <th class="text-start">{{ __('ledger::reports.orders') }}</th>
                    <th class="text-start">{{ __('ledger::reports.gmv') }}</th>
                    <th class="text-start">{{ __('ledger::reports.commission') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['store'] }}</td>
                        <td>{{ $row['orders'] }}</td>
                        <td dir="ltr" class="text-start">{{ $row['gmv'] }}</td>
                        <td dir="ltr" class="text-start">{{ $row['commission'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">{{ __('ledger::reports.empty') }}</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="font-semibold">
                    <td>{{ __('ledger::reports.total') }}</td>
                    <td>{{ $report['orders'] }}</td>
                    <td dir="ltr" class="text-start">{{ $report['gmv'] }}</td>
                    <td dir="ltr" class="text-start">{{ $report['commission'] }}</td>
                </tr>
            </tfoot>
        </table>
    </x-filament::section>
</x-filament-panels::page>
