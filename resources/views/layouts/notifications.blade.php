{{-- Header bell dropdown body. Rendered on page load and returned by `notifications.bell` when the header polls – keep it free of Alpine directives. --}}
<div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
    <h5 class="text-base font-semibold text-gray-800">Notifications</h5>
    @if ($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="text-theme-xs font-medium text-brand-500 hover:text-brand-600">Mark all as read</button>
        </form>
    @endif
</div>

<div class="max-h-96 overflow-y-auto">
    @forelse ($notifications as $notification)
        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
            @csrf
            <button type="submit" @class([
                'flex w-full items-start gap-3 border-b border-gray-100 px-4 py-3 text-start last:border-b-0 hover:bg-gray-50',
                'bg-brand-50/40' => $notification->unread(),
            ])>
                <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-brand-500' => $notification->unread(), 'bg-transparent' => $notification->read()])></span>
                <span class="flex-1">
                    <span @class(['block text-sm', 'text-gray-800' => $notification->unread(), 'text-gray-500' => $notification->read()])>{{ $notification->data['message'] ?? '' }}</span>
                    <span class="mt-1 block text-theme-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                </span>
            </button>
        </form>
    @empty
        <p class="px-4 py-8 text-center text-sm text-gray-500">No notifications yet.</p>
    @endforelse
</div>
