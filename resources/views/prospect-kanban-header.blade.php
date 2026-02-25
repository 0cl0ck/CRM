@php
    $statusColor = match ($status['id']) {
        'identified' => 'bg-gray-400',
        'qualified' => 'bg-blue-400',
        'contacted' => 'bg-amber-400',
        'meeting_set' => 'bg-violet-400',
        'proposal_sent' => 'bg-orange-400',
        'won' => 'bg-emerald-400',
        'lost' => 'bg-red-400',
        default => 'bg-gray-400',
    };
    $count = count($status['records']);
@endphp

<div class="flex items-center justify-between mb-2 px-1">
    <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full {{ $statusColor }}"></span>
        <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-200">
            {{ $status['title'] }}
        </h3>
    </div>
    <span class="text-xs font-medium text-gray-400 bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full">
        {{ $count }}
    </span>
</div>