@props(['status'])

<div class="flex-1 min-w-0 mb-5 md:min-h-full flex flex-col">
    @include(static::$headerView)

    <div data-status-id="{{ $status['id'] }}"
        class="flex flex-col flex-1 gap-2 p-2 bg-gray-200 dark:bg-gray-800 rounded-xl min-h-[200px]">
        @foreach($status['records'] as $record)
            @include(static::$recordView)
        @endforeach
    </div>
</div>