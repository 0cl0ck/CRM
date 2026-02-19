@php
    $borderColor = match ($record->status?->value ?? $record->status) {
        'identified' => 'border-l-gray-400',
        'qualified' => 'border-l-blue-400',
        'contacted' => 'border-l-amber-400',
        'meeting_set' => 'border-l-violet-400',
        'proposal_sent' => 'border-l-orange-400',
        'won' => 'border-l-emerald-400',
        'lost' => 'border-l-red-400',
        default => 'border-l-gray-400',
    };
@endphp

<div id="{{ $record->getKey() }}" wire:click="recordClicked('{{ $record->getKey() }}', {{ @json_encode($record) }})"
    class="p-2 rounded-lg border-l-4 {{ $borderColor }} bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow cursor-grab active:cursor-grabbing">
    {{-- Company name --}}
    <p class="font-semibold text-xs text-gray-900 dark:text-white truncate">
        {{ $record->company_name }}
    </p>

    {{-- Contact name --}}
    @if($record->contact_name)
        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 truncate">
            {{ $record->contact_name }}
        </p>
    @endif

    {{-- Budget badge --}}
    @if($record->budget)
        <div class="mt-1">
            <span class="inline-flex items-center px-1 py-0.5 rounded text-[9px] font-medium
                    {{ match ($record->budget?->value ?? $record->budget) {
            'low' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            'mid' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
            'high' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
            default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        } }}
                ">
                {{ $record->budget->label() }}
            </span>
        </div>
    @endif
</div>