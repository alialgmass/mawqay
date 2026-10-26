<x-filament-widgets::widget>
    <x-filament::section :heading="__('ledger::ledger.reconciliation.heading')">
        <x-slot name="afterHeader">
            {{ $this->runNowAction }}
        </x-slot>

        @php($run = $this->latest())

        @if ($run === null)
            <p data-reconciliation="never">{{ __('ledger::ledger.reconciliation.never') }}</p>
        @else
            <p data-reconciliation="{{ $run->status->value }}">
                <strong>{{ $run->passed() ? __('ledger::ledger.reconciliation.passed') : __('ledger::ledger.reconciliation.failed') }}</strong>
                — {{ __('ledger::ledger.reconciliation.last_run') }}: {{ $run->created_at->toDateTimeString() }}
                @unless ($run->passed())
                    ({{ __('ledger::ledger.reconciliation.mismatch_count', ['count' => count($run->mismatches ?? [])]) }})
                @endunless
            </p>

            @unless ($run->passed())
                <ul class="mt-3 list-disc ps-5 text-sm">
                    @foreach ($run->mismatches ?? [] as $mismatch)
                        <li>{{ $mismatch['message'] }}</li>
                    @endforeach
                </ul>
            @endunless
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
