    <div x-show="showModal" x-cloak class="modal p-4" :class="{ 'modal-open': showModal }" @keydown.escape.window="closeModal()">
            <div class="modal-box w-[95vw] max-w-4xl p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
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
                                    <select x-model="roomForm.status" @change="onStatusChange()" required
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
                                         @input="onMaintenanceWindowInput()"
                                         @change="onMaintenanceWindowInput()"
                                           :disabled="!canEditStatusStart()"
                                           :readonly="!canEditStatusStart()"
                                         :style="canEditStatusStart() ? '' : 'cursor: not-allowed !important;'"
                                           x-init="flatpickr($el, { enableTime: true, dateFormat: 'Y-m-d H:i', minuteIncrement: 15, disableMobile: true })"
                                           placeholder="Select start date/time"
                                           :class="canEditStatusStart() ? 'bg-gray-50 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                                           class="w-full pl-[2.1rem] pr-2 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                                </div>
                                <p class="mt-1 text-xs text-gray-500" x-show="isMaintenanceOngoing()" x-cloak>
                                    Start date/time is locked while maintenance is ongoing.
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date/Time</label>
                                <div class="relative">
                                    <span class="absolute text-gray-400" style="left: 0.75rem; top: 0.65rem;">
                                        <i class="w-4 h-4 fa-icon fa-solid fa-calendar-days text-base leading-none"></i>
                                    </span>
                                    <input type="text" x-model="roomForm.status_end_at"
                                         @input="onMaintenanceWindowInput()"
                                         @change="onMaintenanceWindowInput()"
                                           :disabled="!canEditStatusEnd()"
                                           :readonly="!canEditStatusEnd()"
                                         :style="canEditStatusEnd() ? '' : 'cursor: not-allowed !important;'"
                                           x-init="flatpickr($el, { enableTime: true, dateFormat: 'Y-m-d H:i', minuteIncrement: 15, disableMobile: true })"
                                           placeholder="Select end date/time"
                                           :class="canEditStatusEnd() ? 'bg-gray-50 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                                           class="w-full pl-[2.1rem] pr-2 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50/70 p-4"
                             x-show="isEditing && isMaintenanceStatusSelected()"
                             x-cloak>
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-amber-900">Affected bookings preview</p>
                                <span x-show="previewWindowLoading" class="text-xs font-medium text-amber-700">Loading...</span>
                            </div>

                            <p class="mt-1 text-xs text-amber-700" x-show="!roomForm.status_start_at || !roomForm.status_end_at">
                                Fill both maintenance start and end date/time to preview affected bookings.
                            </p>

                            <p class="mt-1 text-xs text-red-600" x-show="previewWindowError" x-text="previewWindowError"></p>

                            <div class="mt-3 rounded-lg border border-amber-100 bg-white overflow-hidden"
                                 x-show="roomForm.status_start_at && roomForm.status_end_at && !previewWindowLoading && !previewWindowError">
                                <div class="px-3 py-2 text-xs font-semibold text-gray-700 border-b border-amber-100 bg-amber-50/40">
                                    Bookings (<span x-text="previewAffectedBookings.length"></span>)
                                </div>
                                <div class="max-h-40 overflow-y-auto divide-y divide-amber-50">
                                    <template x-if="previewAffectedBookings.length === 0">
                                        <p class="px-3 py-2 text-xs text-gray-500">No affected bookings for this maintenance window.</p>
                                    </template>
                                    <template x-for="booking in previewAffectedBookings" :key="booking.id">
                                        <div class="px-3 py-2">
                                            <p class="text-xs font-semibold text-gray-900" x-text="'#' + booking.id + ' - ' + (booking.user_name || booking.user_email || 'User')"></p>
                                            <p class="text-xs text-gray-600 mt-0.5" x-text="(booking.formatted_date || '') + ' ' + (booking.formatted_time || '')"></p>
                                        </div>
                                    </template>
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

