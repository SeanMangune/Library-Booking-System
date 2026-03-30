@extends('layouts.app')

@section('title', 'Manage Rooms | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-500">Rooms</span>
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Manage</span>
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select x-model="filters.status" @change="applyFilters()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">All Statuses</option>
                    <option value="operational">Operational</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                <select x-model="filters.capacity" @change="applyFilters()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">All Capacities</option>
                    @foreach($capacities as $cap)
                    <option value="{{ $cap }}">{{ $cap }}+ people</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <select x-model="filters.location" @change="applyFilters()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">All Locations</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc }}">{{ $loc }}</option>
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
                        data-status="{{ $room->status }}"
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
                                @if($room->status === 'operational') bg-green-100 text-green-700
                                @elseif($room->status === 'maintenance') bg-yellow-100 text-yellow-700
                                @else bg-red-100 text-red-700 @endif">
                                {{ ucfirst($room->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <button @click='openEditModal(@json($room))'
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-pen-to-square text-base leading-none"></i>
                                </button>
                                <button @click='openDeleteModal(@json($room))'
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
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
    <div x-show="showModal" x-cloak class="modal p-4" :class="{ 'modal-open': showModal }" @keydown.escape.window="closeModal()">
            <div class="modal-box w-11/12 max-w-lg p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-700 px-6 py-6 rounded-t-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                        <i class="fa-solid fa-building-circle-check text-8xl text-white"></i>
                    </div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md shadow-lg">
                                <i class="w-6 h-6 text-white fa-icon fa-solid fa-building text-2xl leading-none"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-white tracking-tight" x-text="isEditing ? 'Edit Room' : 'Add New Room'"></h2>
                                <p class="text-indigo-100 mt-0.5 text-xs font-medium" x-text="isEditing ? 'Update room details and configurations.' : 'Add a new room to the library.'"></p>
                            </div>
                        </div>
                        <button @click="closeModal()" class="text-white/80 hover:text-white bg-white/10 p-2 rounded-xl hover:bg-white/20 transition-all">
                            <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submitRoom()" class="flex flex-col min-h-0">
                    <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                        <!-- Room Information -->
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-4">
                            <span class="w-1 h-4 bg-indigo-600 rounded"></span>
                            Room Information
                        </h3>
                        
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Room Name <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                        <i class="w-4 h-4 fa-icon fa-solid fa-tag text-base leading-none"></i>
                                    </span>
                                    <input type="text" x-model="roomForm.name" required
                                           placeholder="e.g., Conf. Room A"
                                           class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm max-w-full">
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Capacity <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                        <i class="w-4 h-4 fa-icon fa-solid fa-users text-base leading-none"></i>
                                    </span>
                                    <input type="number" x-model="roomForm.capacity" min="1" required
                                           class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm max-w-full">
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Location <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                        <i class="w-4 h-4 fa-icon fa-solid fa-location-dot text-base leading-none"></i>
                                    </span>
                                    <input type="text" x-model="roomForm.location"
                                           placeholder="e.g., 2F"
                                           class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm max-w-full">
                                </div>
                            </div>
                        </div>

                        <div class="mb-6 bg-gray-50/80 rounded-xl p-4 border border-gray-200">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative flex items-center">
                                    <input type="checkbox" x-model="roomForm.requires_approval"
                                           class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition-all">
                                </div>
                                <div>
                                    <span class="text-sm font-semibold text-gray-900 block">Requires Approval</span>
                                    <span class="text-xs text-gray-500">Bookings in this room will need librarian approval before confirmation.</span>
                                </div>
                            </label>
                        </div>

                        <!-- Initial Status -->
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-4">
                            <span class="w-1 h-4 bg-indigo-600 rounded"></span>
                            Initial Status
                        </h3>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                        <i class="w-4 h-4 fa-icon fa-solid fa-circle-check text-base leading-none"></i>
                                    </span>
                                    <select x-model="roomForm.status" required
                                            class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm appearance-none bg-white">
                                        <option value="">Choose status</option>
                                        <option value="operational">Operational</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date/Time</label>
                                <div class="relative">
                                    <span class="absolute text-gray-400" style="left: 0.75rem; top: 0.65rem;">
                                        <i class="w-4 h-4 fa-icon fa-solid fa-calendar-days text-base leading-none"></i>
                                    </span>
                                    <input type="text" x-model="roomForm.status_start_at"
                                           x-init="flatpickr($el, { enableTime: true, dateFormat: 'Y-m-d H:i', minuteIncrement: 15 })"
                                           placeholder="Select start date/time"
                                           class="w-full pl-[2.1rem] pr-2 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm bg-gray-50 cursor-pointer">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date/Time</label>
                                <div class="relative">
                                    <span class="absolute text-gray-400" style="left: 0.75rem; top: 0.65rem;">
                                        <i class="w-4 h-4 fa-icon fa-solid fa-calendar-days text-base leading-none"></i>
                                    </span>
                                    <input type="text" x-model="roomForm.status_end_at"
                                           x-init="flatpickr($el, { enableTime: true, dateFormat: 'Y-m-d H:i', minuteIncrement: 15 })"
                                           placeholder="Select end date/time"
                                           class="w-full pl-[2.1rem] pr-2 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm bg-gray-50 cursor-pointer">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-white shrink-0">
                        <button type="button" @click="closeModal()"
                                class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50">
                            <span class="flex items-center gap-2">
                                <i x-show="!isSubmitting" class="w-4 h-4 fa-icon fa-solid fa-floppy-disk text-base leading-none"></i>
                                <i x-show="isSubmitting" class="animate-spin w-4 h-4 fa-icon fa-solid fa-spinner text-base leading-none"></i>
                                <span x-text="isSubmitting ? 'Saving...' : (isEditing ? 'Update Room' : 'Add Room')"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
            <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeModal()">close</button>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="modal p-4" :class="{ 'modal-open': showDeleteModal }" @keydown.escape.window="closeDeleteModal()">
            <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-rose-600 to-red-700 px-6 py-6 rounded-t-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                        <i class="fa-solid fa-triangle-exclamation text-8xl text-white"></i>
                    </div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md shadow-lg">
                                <i class="w-6 h-6 text-white fa-icon fa-solid fa-trash-can text-2xl leading-none"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white tracking-tight">Delete Room</h2>
                                <p class="text-rose-100 text-sm">This action cannot be undone.</p>
                            </div>
                        </div>
                        <button @click="closeDeleteModal()" class="text-white/80 hover:text-white bg-white/10 p-2 rounded-xl hover:bg-white/20 transition-all">
                            <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="flex flex-col min-h-0">
                    <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-green-50 rounded-xl">
                                <div class="flex items-center gap-2 text-green-600 text-xs font-medium mb-1">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-circle-check text-base leading-none"></i>
                                    STATUS
                                </div>
                                <p class="text-gray-900 font-semibold" x-text="deleteRoom?.status ? deleteRoom.status.charAt(0).toUpperCase() + deleteRoom.status.slice(1) : ''"></p>
                            </div>
                            <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-100/50">
                                <div class="flex items-center gap-2 text-indigo-600 text-xs font-semibold mb-1">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-building text-base leading-none"></i>
                                    ROOM NAME
                                </div>
                                <p class="text-gray-900 font-bold" x-text="deleteRoom?.name"></p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <div class="flex items-center gap-2 text-gray-600 text-xs font-medium mb-1">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-location-dot text-base leading-none"></i>
                                    LOCATION
                                </div>
                                <p class="text-gray-900 font-semibold" x-text="deleteRoom?.location || '-'"></p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <div class="flex items-center gap-2 text-gray-600 text-xs font-medium mb-1">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-users text-base leading-none"></i>
                                    CAPACITY
                                </div>
                                <p class="text-gray-900 font-semibold" x-text="deleteRoom?.capacity"></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-white shrink-0">
                        <button @click="closeDeleteModal()"
                                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors">
                            Cancel
                        </button>
                        <button @click="confirmDelete()" :disabled="isDeleting"
                                class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 shadow-sm shadow-rose-600/20">
                            <span class="flex items-center justify-center gap-2">
                                <i x-show="!isDeleting" class="w-4 h-4 fa-icon fa-solid fa-trash-can text-base leading-none"></i>
                                <i x-show="isDeleting" class="animate-spin w-4 h-4 fa-icon fa-solid fa-spinner text-base leading-none"></i>
                                <span x-text="isDeleting ? 'Deleting...' : 'Delete Room'"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeDeleteModal()">close</button>
    </div>
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
            capacity: 'all',
            location: 'all'
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
                status_start_at: room.status_start_at || '',
                status_end_at: room.status_end_at || '',
            };
            this.showModal = true;
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
            if (this.filters.location !== 'all') params.append('location', this.filters.location);
            
            window.location.href = '{{ route("rooms.index") }}' + (params.toString() ? '?' + params.toString() : '');
        }
    }
}
</script>
@endpush
@endsection