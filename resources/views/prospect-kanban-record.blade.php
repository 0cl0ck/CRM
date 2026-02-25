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
    class="p-3 rounded-lg border-l-4 {{ $borderColor }} bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-all cursor-grab active:cursor-grabbing">
    {{-- Company name --}}
    <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">
        {{ $record->company_name }}
    </p>

    {{-- Contact name --}}
    @if($record->contact_name)
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
            {{ $record->contact_name }}
        </p>
    @endif

    {{-- Info badges row --}}
    <div class="flex flex-wrap items-center gap-1 mt-2">
        {{-- Budget badge --}}
        @if($record->budget)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium
                            {{ match ($record->budget?->value ?? $record->budget) {
                'low' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                'high' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            } }}
                            ">
                    {{ $record->budget->label() }}
                </span>
        @endif

        {{-- Revenue --}}
        @if($record->revenue)
            <span
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                {{ number_format($record->revenue, 0, ',', ' ') }} €
            </span>
        @endif

        {{-- Lighthouse Performance --}}
        @if($record->lh_performance)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium
                            {{ match (true) {
                $record->lh_performance >= 90 => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                $record->lh_performance >= 50 => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                default => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
            } }}
                            ">
                    LH {{ $record->lh_performance }}
                </span>
        @endif
    </div>
</div>