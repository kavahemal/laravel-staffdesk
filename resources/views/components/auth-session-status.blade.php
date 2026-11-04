@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-success-500 bg-success-50 p-4 text-sm text-gray-600']) }} role="status">
        {{ $status }}
    </div>
@endif
