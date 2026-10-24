<x-filament-panels::page>
    <section aria-label="{{ __('theming::appearance.themes') }}">
        <h2 class="mb-3 text-base font-semibold">{{ __('theming::appearance.themes') }}</h2>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->themes() as $theme)
                <button
                    type="button"
                    wire:key="theme-{{ $theme['slug'] }}"
                    wire:click="chooseTheme('{{ $theme['slug'] }}')"
                    data-theme="{{ $theme['slug'] }}"
                    class="rounded-lg border p-3 text-start {{ $theme['selected'] ? 'border-primary-600 ring-2 ring-primary-600' : 'border-gray-300' }}"
                >
                    <img src="{{ $theme['previewUrl'] }}" alt="{{ $theme['name'] }}" class="mb-2 w-full rounded" />
                    <span class="font-medium">{{ $theme['name'] }}</span>
                    <span class="text-sm text-gray-500">{{ $theme['version'] }}</span>
                    @if ($theme['live'])
                        <span data-live-theme class="ms-2 rounded bg-green-100 px-2 text-xs text-green-800">{{ __('theming::appearance.live') }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </section>

    <form wire:submit="publish" class="space-y-4">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button type="submit">{{ __('theming::appearance.publish') }}</x-filament::button>
            <x-filament::button
                tag="a"
                color="gray"
                :href="$this->previewUrl()"
                target="_blank"
                rel="noopener"
            >
                {{ __('theming::appearance.preview') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
