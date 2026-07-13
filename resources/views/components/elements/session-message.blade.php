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
        'mb-6 p-4 text-sm rounded-lg',
        'bg-red-100 text-red-700' => $type == 'error',
        'bg-orange-100 text-orange-700' => $type == 'warning',
        'bg-green-100 text-green-700' => $type == 'success',
        'bg-blue-100 text-blue-700' => $type == 'info',
    ]) role="alert">
        {{ $message }}
    </div>
@endif
