@extends('layouts.app')

@section('title', 'Manage Rooms | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Room Management</span>
@endsection

@section('content')
<div x-data="roomManagement()" x-init="init()">
    <!-- Header Banner -->
    <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl border border-indigo-500/20 shadow-lg p-6 sm:p-8 relative overflow-hidden group/header mb-6">
        <div class="absolute -right-4 -bottom-4 opacity-20 transform rotate-12 group-hover/header:scale-110 transition-transform duration-500 pointer-events-none">
            <i class="fa-solid fa-list-check text-9xl text-white"></i>
        </div>
        <div class="relative z-10 w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">Manage Rooms</h1>
                <p class="text-indigo-100 mt-2 text-base">Configure capacities, statuses, and details for all library rooms.</p>
            </div>
            <button @click="openAddModal()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 text-sm font-semibold rounded-xl transition-all shadow-sm backdrop-blur-sm">
                <i class="w-4 h-4 fa-icon fa-solid fa-plus text-base leading-none"></i>
                Add New Room
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select x-model="filters.status" @change="applyFilters()"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer">
                    <option value="all">All Statuses</option>
                    <option value="operational">Operational</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                <select x-model="filters.capacity" @change="applyFilters()"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer">
                    <option value="all">All Capacities</option>
                    @foreach($capacities as $cap)
                    <option value="{{ $cap }}">{{ $cap }}+ people</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Room Management Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Room List</h2>
            <div class="relative">
                <input type="text" x-model="searchQuery" @input="searchRooms()" placeholder="Search rooms..."
                       class="w-48 xl:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                <i class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 fa-icon fa-solid fa-magnifying-glass text-base leading-none"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Room Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Capacity</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($rooms as $room)
                    <tr class="room-row hover:bg-gray-50 transition-colors" 
                        data-name="{{ strtolower($room->name) }}"
                        data-status="{{ $room->effective_status }}"
                        data-capacity="{{ $room->capacity }}"
                        data-location="{{ $room->location }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $room->name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600">{{ $room->location ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600">{{ $room->capacity }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                @if($room->effective_status === 'operational') bg-green-100 text-green-700
                                @elseif($room->effective_status === 'maintenance') bg-yellow-100 text-yellow-700
                                @else bg-red-100 text-red-700 @endif">
                                {{ ucfirst($room->effective_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <button @click='openEditModal(@json($room))'
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-pen-to-square text-base leading-none"></i>
                                </button>
                                <button @click='openDeleteModal(@json($room))'
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-trash-can text-base leading-none"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <i class="w-12 h-12 text-gray-300 mx-auto mb-3 fa-icon fa-solid fa-building text-5xl leading-none"></i>
                            <p class="text-sm text-gray-500">No rooms found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Room Modal -->
    <x-modals.managerooms.form />

    <!-- Delete Confirmation Modal -->
    <x-modals.managerooms.delete-confirmation />
</div>

@push('scripts')
<script>
function roomManagement() {
    return {
        showModal: false,
        showDeleteModal: false,
        isEditing: false,
        isSubmitting: false,
        isDeleting: false,
        searchQuery: '',
        editingRoomId: null,
        deleteRoom: null,
        
        filters: {
            status: 'all',
            capacity: 'all'
        },

        roomForm: {
            name: '',
            capacity: 10,
            location: '',
            status: 'operational',
            requires_approval: false,
            status_start_at: '',
            status_end_at: '',
        },

        init() {},

        openAddModal() {
            this.isEditing = false;
            this.editingRoomId = null;
            this.roomForm = {
                name: '',
                capacity: 10,
                location: '',
                status: 'operational',
                requires_approval: false,
                status_start_at: '',
                status_end_at: '',
            };
            this.showModal = true;
        },

        openEditModal(room) {
            this.isEditing = true;
            this.editingRoomId = room.id;
            this.roomForm = {
                name: room.name,
                capacity: room.capacity,
                location: room.location || '',
                status: room.status,
                requires_approval: room.requires_approval || false,
                status_start_at: this.normalizeDateTimeForInput(room.status_start_at),
                status_end_at: this.normalizeDateTimeForInput(room.status_end_at),
            };
            this.showModal = true;
        },

        normalizeDateTimeForInput(value) {
            if (!value) {
                return '';
            }

            // Extract date/time parts directly via regex to avoid timezone conversion
            const str = String(value);
            const match = str.match(/(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);

            if (!match) {
                return str;
            }

            return `${match[1]}-${match[2]}-${match[3]} ${match[4]}:${match[5]}`;
        },

        parseDateTime(value) {
            if (!value) {
                return null;
            }

            const normalized = String(value).replace(' ', 'T');
            const parsed = new Date(normalized);

            return Number.isNaN(parsed.getTime()) ? null : parsed;
        },

        isMaintenanceStatusSelected() {
            return this.roomForm.status === 'maintenance';
        },

        isMaintenanceOngoing() {
            if (!this.isMaintenanceStatusSelected()) {
                return false;
            }

            const startAt = this.parseDateTime(this.roomForm.status_start_at);
            const endAt = this.parseDateTime(this.roomForm.status_end_at);
            const now = new Date();

            if (!startAt || now < startAt) {
                return false;
            }

            return !endAt || now < endAt;
        },

        canEditStatusStart() {
            return this.isMaintenanceStatusSelected() && !this.isMaintenanceOngoing();
        },

        canEditStatusEnd() {
            return this.isMaintenanceStatusSelected();
        },

        onStatusChange() {
            if (this.roomForm.status === 'maintenance') {
                return;
            }

            this.roomForm.status_start_at = '';
            this.roomForm.status_end_at = '';
        },

        closeModal() {
            this.showModal = false;
        },

        openDeleteModal(room) {
            this.deleteRoom = room;
            this.showDeleteModal = true;
        },

        closeDeleteModal() {
            this.showDeleteModal = false;
            this.deleteRoom = null;
        },

        async submitRoom() {
            this.isSubmitting = true;
            try {
                const url = this.isEditing 
                    ? `/rooms/manage/${this.editingRoomId}` 
                    : '/rooms/manage';
                const method = this.isEditing ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.roomForm)
                });

                const data = await response.json();
                
                if (response.ok && data.success !== false) {
                    window.notifyApp?.('success', data.message || 'Room saved successfully.');
                    this.closeModal();
                    window.setTimeout(() => {
                        window.location.reload();
                    }, 850);
                } else {
                    let errMsg = data.message || 'An error occurred';
                    if (data.errors && Object.values(data.errors).length > 0) {
                        errMsg = Object.values(data.errors)[0][0];
                    }
                    window.notifyApp?.('error', errMsg);
                }
            } catch (error) {
                console.error('Error:', error);
                window.notifyApp?.('error', 'An error occurred while saving the room');
            } finally {
                this.isSubmitting = false;
            }
        },

        async confirmDelete() {
            if (!this.deleteRoom) return;
            
            this.isDeleting = true;
            try {
                const response = await fetch(`/rooms/manage/${this.deleteRoom.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    window.notifyApp?.('success', data.message || 'Room deleted successfully.');
                    this.closeDeleteModal();
                    window.setTimeout(() => {
                        window.location.reload();
                    }, 850);
                } else {
                    window.notifyApp?.('error', data.message || 'Cannot delete room');
                }
            } catch (error) {
                console.error('Error:', error);
                window.notifyApp?.('error', 'An error occurred while deleting the room');
            } finally {
                this.isDeleting = false;
            }
        },

        searchRooms() {
            const query = this.searchQuery.toLowerCase();
            document.querySelectorAll('.room-row').forEach(row => {
                const name = row.dataset.name;
                row.style.display = name.includes(query) ? '' : 'none';
            });
        },

        applyFilters() {
            const params = new URLSearchParams();
            if (this.filters.status !== 'all') params.append('status', this.filters.status);
            if (this.filters.capacity !== 'all') params.append('capacity', this.filters.capacity);
            
            window.location.href = '{{ route("rooms.index") }}' + (params.toString() ? '?' + params.toString() : '');
        }
    }
}
</script>
@endpush
@endsection
