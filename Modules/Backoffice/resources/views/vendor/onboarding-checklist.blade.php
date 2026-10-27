<x-filament-widgets::widget>
    <x-filament::section :heading="__('backoffice::panel.checklist.heading')" data-onboarding-checklist>
        <ul class="space-y-2 text-sm">
            @foreach ($this->steps() as $label => $done)
                <li data-done="{{ $done ? '1' : '0' }}" class="flex items-center gap-2">
                    <span aria-hidden="true">{{ $done ? '✔' : '○' }}</span>
                    <span class="{{ $done ? 'line-through text-gray-500' : '' }}">{{ $label }}</span>
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
