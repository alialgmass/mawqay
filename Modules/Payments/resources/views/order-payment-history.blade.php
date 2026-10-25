<x-filament::section :heading="__('payments::review.payment.history')" data-payment-history>
    @forelse ($payments as $payment)
        <div class="grid gap-1 py-2 text-sm md:grid-cols-6">
            <span>#{{ $payment['attempt'] }}</span>
            <span>{{ $payment['status'] }}</span>
            <span dir="ltr">{{ $payment['amount'] }}</span>
            <span dir="ltr">{{ $payment['reference'] ?? '—' }}@if ($payment['duplicate']) <strong>({{ __('payments::review.payment.duplicate') }})</strong>@endif</span>
            <span>{{ $payment['reviewer'] ? $payment['reviewer'].' — '.$payment['reviewedAt'] : $payment['submittedAt'] }}</span>
            <a href="{{ $payment['receiptUrl'] }}" target="_blank" rel="noopener" class="underline">{{ __('payments::review.actions.preview') }}</a>
            @if ($payment['reason'])
                <span class="md:col-span-6">{{ __('payments::review.payment.rejection_reason') }}: {{ $payment['reason'] }}</span>
            @endif
        </div>
    @empty
        <p class="text-sm">{{ __('payments::review.payment.none') }}</p>
    @endforelse
</x-filament::section>
