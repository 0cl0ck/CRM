<x-filament-panels::page>
    <div x-data wire:ignore.self class="flex overflow-x-auto gap-3 pb-4" style="width: 100%;">
        @foreach($statuses as $status)
            @include(static::$statusView)
        @endforeach

        <div wire:ignore>
            @include(static::$scriptsView)
        </div>
    </div>

    @unless($disableEditModal)
        <x-filament-kanban::edit-record-modal />
    @endunless
</x-filament-panels::page>