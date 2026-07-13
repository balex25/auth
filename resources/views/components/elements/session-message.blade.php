@props([
    'message' => null,
    'type' => null,
])

@php
$messageTypes = ['error', 'warning', 'success', 'info'];

if ($message === null) {
    foreach ($messageTypes as $messageType) {
        if (session()->has($messageType)) {
            $message = session($messageType);
            $type = $messageType;
            break;
        }
    }
}

$type = in_array($type, $messageTypes, true) ? $type : 'info';
@endphp

@if($message)
    <div @class([
        'p-4 mb-2 rounded-md',
        'bg-red-50 dark:bg-red-600' => $type === 'error',
        'bg-orange-50 dark:bg-orange-600' => $type === 'warning',
        'bg-green-50 dark:bg-green-600' => $type === 'success',
        'bg-blue-50 dark:bg-blue-600' => $type === 'info',
    ]) role="alert">
        <div class="flex">
            <div class="shrink-0">
                @switch($type)
                    @case('success')
                        <svg aria-hidden="true" class="w-5 h-5 text-green-400 dark:text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        @break

                    @case('error')
                        <svg aria-hidden="true" class="w-5 h-5 text-red-400 dark:text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-2.293-10.707a1 1 0 00-1.414 1.414L7.586 10l-1.293 1.293a1 1 0 101.414 1.414L9 11.414l1.293 1.293a1 1 0 001.414-1.414L10.414 10l1.293-1.293a1 1 0 00-1.414-1.414L9 8.586 7.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        @break

                    @case('warning')
                        <svg aria-hidden="true" class="w-5 h-5 text-orange-400 dark:text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l6.518 11.596c.75 1.334-.213 2.98-1.742 2.98H3.48c-1.53 0-2.493-1.646-1.743-2.98L8.257 3.1zM11 14a1 1 0 11-2 0 1 1 0 012 0zm-1-2a1 1 0 01-1-1V7a1 1 0 112 0v4a1 1 0 01-1 1z" clip-rule="evenodd" />
                        </svg>
                        @break

                    @default
                        <svg aria-hidden="true" class="w-5 h-5 text-blue-400 dark:text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM9 9a1 1 0 012 0v5a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                        </svg>
                @endswitch
            </div>

            <div class="ml-3">
                <p @class([
                    'text-sm font-medium leading-5',
                    'text-red-800 dark:text-red-200' => $type === 'error',
                    'text-orange-800 dark:text-orange-200' => $type === 'warning',
                    'text-green-800 dark:text-green-200' => $type === 'success',
                    'text-blue-800 dark:text-blue-200' => $type === 'info',
                ])>
                    {{ $message }}
                </p>
            </div>
        </div>
    </div>
@endif
