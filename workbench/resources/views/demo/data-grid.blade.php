<x-filament-panels::page>
    <form wire:submit="save" class="grid gap-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">{{ __('demo::demo.actions.save') }}</x-filament::button>
        </div>
    </form>

    @if ($this->savedState !== null)
        <x-filament::section :heading="__('demo::demo.sections.saved_state')" collapsible>
            <pre data-saved-state class="overflow-x-auto text-xs">{{ json_encode($this->savedState, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </x-filament::section>
    @endif
</x-filament-panels::page>
