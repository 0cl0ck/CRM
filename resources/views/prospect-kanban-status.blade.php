@props(['status'])

<div class="w-[260px] shrink-0 flex flex-col">
    @include(static::$headerView)

    <div data-status-id="{{ $status['id'] }}"
        class="flex flex-col flex-1 gap-2 p-2 bg-gray-100/50 dark:bg-gray-800/60 rounded-xl min-h-[300px]">
        @foreach($status['records'] as $record)
            @include(static::$recordView)
        @endforeach
    </div>
</div>