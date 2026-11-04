<x-app-layout>
    <x-slot name="header">Leave types</x-slot>

    <x-slot name="actions">
        <x-primary-button :href="route('leave-types.create')">
            <x-heroicon-o-plus />
            Add leave type
        </x-primary-button>
    </x-slot>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <x-filter-bar :action="route('leave-types.index')" :filtered="$search !== ''" button="Search">
            <x-search-input :value="$search" placeholder="Search leave type…" />
        </x-filter-bar>

        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead class="border-b border-gray-100">
                    <tr class="text-start text-xs font-medium text-gray-500">
                        <x-sort-header field="name" default="name">Name</x-sort-header>
                        <x-sort-header field="quota" default="name">Yearly quota</x-sort-header>
                        <th class="px-5 py-3 text-start sm:px-6">Status</th>
                        <th class="px-5 py-3 sm:px-6"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($leaveTypes as $leaveType)
                        <tr class="transition hover:bg-gray-50/50">
                            <td class="px-5 py-4 font-medium text-gray-800 sm:px-6">{{ $leaveType->name }}</td>
                            <td class="px-5 py-4 text-gray-500 sm:px-6">{{ $leaveType->yearly_quota === null ? 'Unlimited' : $leaveType->yearly_quota.' days' }}</td>
                            <td class="px-5 py-4 sm:px-6">
                                <x-badge :color="$leaveType->is_active ? 'success' : 'gray'">{{ $leaveType->is_active ? 'Active' : 'Inactive' }}</x-badge>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('leave-types.edit', $leaveType) }}" title="Edit" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-500">
                                        <x-heroicon-o-pencil-square class="size-5" />
                                    </a>

                                    <form method="POST" action="{{ route('leave-types.destroy', $leaveType) }}" data-confirm="Delete leave type?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete" class="rounded-lg p-2 text-gray-500 hover:bg-error-50 hover:text-error-500">
                                            <x-heroicon-o-trash class="size-5" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-gray-500 sm:px-6">{{ $search !== '' ? 'No leave types match your search.' : 'No leave types yet.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $leaveTypes->links() }}</div>
</x-app-layout>
