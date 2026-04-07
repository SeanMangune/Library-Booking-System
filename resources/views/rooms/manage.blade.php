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

    <!-- Affected Bookings Modal -->
    <div x-show="showAffectedBookingsModal" x-cloak class="modal modal-open p-4" @keydown.escape.window="closeAffectedBookingsModal(true)">
        <div class="modal-box w-[95vw] max-w-3xl p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
            <div class="bg-linear-to-r from-amber-500 to-orange-600 px-6 py-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-15 pointer-events-none">
                    <i class="fa-solid fa-triangle-exclamation text-7xl text-white"></i>
                </div>
                <div class="relative z-10 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-white text-xl font-black tracking-tight">Affected Bookings</h3>
                        <p class="text-amber-100 mt-1 text-sm">These bookings overlap the maintenance window and must be manually rescheduled.</p>
                    </div>
                    <button @click="closeAffectedBookingsModal(true)" class="text-white/80 hover:text-white bg-white/10 p-2 rounded-xl hover:bg-white/20 transition-all">
                        <i class="w-5 h-5 fa-icon fa-solid fa-xmark text-xl leading-none"></i>
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                <template x-if="affectedMaintenanceWindow">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <p class="font-semibold">Maintenance window</p>
                        <p class="mt-1" x-text="formatDateTimeLabel(affectedMaintenanceWindow.start_at) + ' to ' + formatDateTimeLabel(affectedMaintenanceWindow.end_at)"></p>
                    </div>
                </template>

                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700">
                        Booking list (<span x-text="affectedBookings.length"></span>)
                    </div>
                    <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                        <template x-for="booking in affectedBookings" :key="booking.id">
                            <div class="px-4 py-3">
                                <p class="text-sm font-semibold text-gray-900" x-text="'#' + booking.id + ' - ' + (booking.user_name || booking.user_email || 'User')"></p>
                                <p class="text-xs text-gray-600 mt-1" x-text="(booking.formatted_date || '') + ' ' + (booking.formatted_time || '')"></p>
                                <p class="text-xs text-gray-500 mt-1" x-show="booking.title" x-text="booking.title"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-white shrink-0">
                <button type="button"
                        @click="closeAffectedBookingsModal(true)"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                    Done
                </button>
            </div>
        </div>
        <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeAffectedBookingsModal(true)">close</button>
    </div>
</div>

@push('scripts')
<script>
function roomManagement() {
    return {
        showModal: false,
        showDeleteModal: false,
        showAffectedBookingsModal: false,
        isEditing: false,
        isSubmitting: false,
        isDeleting: false,
        searchQuery: '',
        editingRoomId: null,
        deleteRoom: null,
        affectedBookings: [],
        affectedMaintenanceWindow: null,
        previewAffectedBookings: [],
        previewWindowLoading: false,
        previewWindowError: '',
        previewRequestTimer: null,
        
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
        },

        init() {},

        openAddModal() {
            this.isEditing = false;
            this.editingRoomId = null;
            this.resetAffectedBookingsPreview();
            this.roomForm = {
                name: '',
                capacity: 10,
                location: '',
                status: 'operational',
                requires_approval: false,
                status_start_at: '',
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
                status_start_at: this.normalizeDateForInput(room.status_start_at),
            };
            this.showModal = true;
            this.onMaintenanceWindowInput();
        },

        normalizeDateForInput(value) {
            if (!value) {
                return '';
            }

            // Extract date directly and avoid timezone shifts.
            const str = String(value);
            const match = str.match(/(\d{4})-(\d{2})-(\d{2})/);

            if (!match) {
                return str;
            }

            return `${match[1]}-${match[2]}-${match[3]}`;
        },

        isMaintenanceStatusSelected() {
            return this.roomForm.status === 'maintenance';
        },

        buildMaintenanceWindow(dateValue) {
            const selectedDate = String(dateValue || '').trim();
            if (!/^\d{4}-\d{2}-\d{2}$/.test(selectedDate)) {
                return null;
            }

            return {
                startAt: `${selectedDate} 08:00:00`,
                endAt: `${selectedDate} 17:00:00`,
            };
        },

        onStatusChange() {
            if (this.isMaintenanceStatusSelected()) {
                this.onMaintenanceWindowInput();
                return;
            }

            this.roomForm.status_start_at = '';
            this.resetAffectedBookingsPreview();
        },

        buildRoomPayload() {
            const payload = {
                ...this.roomForm,
                capacity: Number(this.roomForm.capacity || 0),
            };

            const maintenanceWindow = this.buildMaintenanceWindow(this.roomForm.status_start_at);
            if (payload.status === 'maintenance' && maintenanceWindow) {
                payload.status_start_at = maintenanceWindow.startAt;
                payload.status_end_at = maintenanceWindow.endAt;
            } else {
                payload.status_start_at = '';
                payload.status_end_at = '';
            }

            return payload;
        },

        closeModal() {
            this.showModal = false;
            this.resetAffectedBookingsPreview();
        },

        resetAffectedBookingsPreview() {
            if (this.previewRequestTimer) {
                clearTimeout(this.previewRequestTimer);
                this.previewRequestTimer = null;
            }

            this.previewAffectedBookings = [];
            this.previewWindowLoading = false;
            this.previewWindowError = '';
        },

        onMaintenanceWindowInput() {
            if (this.previewRequestTimer) {
                clearTimeout(this.previewRequestTimer);
                this.previewRequestTimer = null;
            }

            if (!this.isEditing || !this.isMaintenanceStatusSelected()) {
                this.resetAffectedBookingsPreview();
                return;
            }

            const selectedDate = String(this.roomForm.status_start_at || '').trim();
            const maintenanceWindow = this.buildMaintenanceWindow(selectedDate);

            if (!selectedDate) {
                this.resetAffectedBookingsPreview();
                return;
            }

            if (!maintenanceWindow) {
                this.previewAffectedBookings = [];
                this.previewWindowError = 'Please choose a valid maintenance date.';
                return;
            }

            this.previewWindowError = '';
            this.previewRequestTimer = window.setTimeout(() => {
                this.fetchAffectedBookingsPreview(maintenanceWindow.startAt, maintenanceWindow.endAt);
            }, 250);
        },

        async fetchAffectedBookingsPreview(startValue, endValue) {
            if (!this.editingRoomId) {
                return;
            }

            this.previewWindowLoading = true;
            this.previewWindowError = '';

            try {
                const params = new URLSearchParams({
                    status_start_at: startValue,
                    status_end_at: endValue,
                });

                const response = await fetch(`/rooms/manage/${this.editingRoomId}/affected-bookings?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Unable to load affected bookings.');
                }

                this.previewAffectedBookings = Array.isArray(data.affected_bookings)
                    ? data.affected_bookings
                    : [];
            } catch (error) {
                console.error('Affected booking preview error:', error);
                this.previewAffectedBookings = [];
                this.previewWindowError = 'Unable to load affected bookings preview.';
            } finally {
                this.previewWindowLoading = false;
            }
        },

        openAffectedBookingsModal(payload) {
            this.affectedBookings = Array.isArray(payload?.affected_bookings)
                ? payload.affected_bookings
                : [];
            this.affectedMaintenanceWindow = payload?.maintenance_window || null;
            this.showAffectedBookingsModal = this.affectedBookings.length > 0;
        },

        closeAffectedBookingsModal(shouldReload = true) {
            this.showAffectedBookingsModal = false;
            this.affectedBookings = [];
            this.affectedMaintenanceWindow = null;

            if (shouldReload) {
                window.location.reload();
            }
        },

        formatDateTimeLabel(value) {
            if (!value) {
                return '';
            }

            const parsed = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(parsed.getTime())) {
                return String(value);
            }

            return parsed.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            });
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
                const payload = this.buildRoomPayload();

                if (!Number.isFinite(payload.capacity) || payload.capacity < 5 || payload.capacity > 10) {
                    window.notifyApp?.('error', 'Room capacity must be between 5 and 10.');
                    return;
                }

                if (payload.status === 'maintenance' && !payload.status_start_at) {
                    window.notifyApp?.('error', 'Please select a start date for maintenance.');
                    return;
                }

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
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                const affectedBookings = Array.isArray(data?.affected_bookings) ? data.affected_bookings : [];

                if (response.ok && data.success !== false) {
                    window.notifyApp?.('success', data.message || 'Room saved successfully.');
                    this.closeModal();

                    if (affectedBookings.length > 0) {
                        this.openAffectedBookingsModal(data);
                        return;
                    }

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
