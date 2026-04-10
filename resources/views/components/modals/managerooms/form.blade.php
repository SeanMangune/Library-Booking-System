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
                                     <input type="number" x-model="roomForm.capacity" min="5" required
                                           max="10"
                                           class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm max-w-full">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Room capacity must be between 5 and 10.</p>
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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center"
                                          :class="{
                                              'text-emerald-600': roomForm.status === 'operational',
                                              'text-amber-600': roomForm.status === 'maintenance',
                                              'text-red-600': roomForm.status === 'closed'
                                          }">
                                        <i class="w-4 h-4 fa-icon fa-solid"
                                           :class="{
                                               'fa-circle-check': roomForm.status === 'operational',
                                               'fa-screwdriver-wrench': roomForm.status === 'maintenance',
                                               'fa-circle-xmark': roomForm.status === 'closed'
                                           }"></i>
                                    </span>
                                    <select x-model="roomForm.status" @change="onStatusChange()" required
                                            :class="{
                                                'border-emerald-300 bg-emerald-50 text-emerald-800 focus:ring-emerald-500 focus:border-emerald-500': roomForm.status === 'operational',
                                                'border-amber-300 bg-amber-50 text-amber-800 focus:ring-amber-500 focus:border-amber-500': roomForm.status === 'maintenance',
                                                'border-red-300 bg-red-50 text-red-800 focus:ring-red-500 focus:border-red-500': roomForm.status === 'closed'
                                            }"
                                            class="w-full pl-9 pr-3 py-2.5 border rounded-lg text-sm appearance-none transition-colors bg-white">
                                        <option value="">Choose status</option>
                                        <option value="operational">Operational</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                                <div class="mt-2">
                                    <span class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-semibold ring-1"
                                          :class="{
                                              'bg-emerald-100 text-emerald-700 ring-emerald-200': roomForm.status === 'operational',
                                              'bg-amber-100 text-amber-700 ring-amber-200': roomForm.status === 'maintenance',
                                              'bg-red-100 text-red-700 ring-red-200': roomForm.status === 'closed'
                                          }">
                                        <span class="h-1.5 w-1.5 rounded-full"
                                              :class="{
                                                  'bg-emerald-500': roomForm.status === 'operational',
                                                  'bg-amber-500': roomForm.status === 'maintenance',
                                                  'bg-red-500': roomForm.status === 'closed'
                                              }"></span>
                                        <span x-text="roomForm.status ? (roomForm.status.charAt(0).toUpperCase() + roomForm.status.slice(1)) : 'Choose a status'"></span>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <div class="relative">
                                    <span class="absolute text-gray-400" style="left: 0.75rem; top: 0.65rem;">
                                        <i class="w-4 h-4 fa-icon fa-solid fa-calendar-days text-base leading-none"></i>
                                    </span>
                                    <input type="date" x-model="roomForm.status_start_at"
                                           @input="onMaintenanceWindowInput()"
                                           @change="onMaintenanceWindowInput()"
                                           :disabled="!isMaintenanceStatusSelected()"
                                           :class="isMaintenanceStatusSelected() ? 'bg-gray-50 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                                           class="w-full pl-[2.1rem] pr-2 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    Select date only. System uses fixed schedule of 8:00 AM to 7:00 PM.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50/70 p-4"
                             x-show="isEditing && isMaintenanceStatusSelected()"
                             x-cloak>
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-amber-900">Affected bookings preview</p>
                                <span x-show="previewWindowLoading" class="text-xs font-medium text-amber-700">Loading...</span>
                            </div>

                            <p class="mt-1 text-xs text-amber-700" x-show="!roomForm.status_start_at">
                                Select a maintenance date to preview affected bookings for 8:00 AM to 7:00 PM.
                            </p>

                            <p class="mt-1 text-xs text-red-600" x-show="previewWindowError" x-text="previewWindowError"></p>

                            <div class="mt-3 rounded-lg border border-amber-100 bg-white overflow-hidden"
                                 x-show="roomForm.status_start_at && !previewWindowLoading && !previewWindowError">
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

