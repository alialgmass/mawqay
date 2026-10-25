@if ($mime === 'application/pdf')
    <iframe src="{{ $url }}" title="{{ __('payments::review.actions.preview') }}" class="w-full" style="height: 70vh" data-receipt-preview></iframe>
@else
    <img src="{{ $url }}" alt="{{ __('payments::review.actions.preview') }}" class="mx-auto max-h-[70vh]" data-receipt-preview>
@endif
