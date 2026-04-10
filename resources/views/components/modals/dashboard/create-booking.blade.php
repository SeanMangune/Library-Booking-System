@props(['rooms' => collect()])

    <div x-show="showBookingModal" x-cloak class="modal p-4" :class="{ 'modal-open': showBookingModal }" @keydown.escape.window="closeBookingModal()">
        <div class="modal-box w-11/12 max-w-2xl p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-6 py-6 rounded-t-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                        <i class="fa-solid fa-calendar-plus text-8xl text-white"></i>
                    </div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md shadow-lg">
                                <i class="w-6 h-6 text-white fa-icon fa-solid fa-calendar-days text-2xl leading-none"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-white tracking-tight">User Verification Portal</h2>
                                <p class="text-teal-50 text-xs font-medium opacity-90 uppercase tracking-widest mt-0.5">ID Scanning & Verification Required</p>
                            </div>
                        </div>
                        <button @click="closeBookingModal()" class="text-white/80 hover:text-white bg-white/10 p-2 rounded-xl hover:bg-white/20 transition-all">
                            <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form method="POST" enctype="multipart/form-data" @submit.prevent="submitBooking()" class="flex flex-col min-h-0">
                    @csrf
                    <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-4">
                                <span class="w-1 h-4 bg-teal-600 rounded"></span>
                                Booking Information
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Purpose <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" x-model="bookingForm.purpose" required
                                           placeholder="e.g., Group study, Thesis consultation"
                                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Book for User <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" x-model="bookingForm.user_name" required
                                           :value="verifiedRegistrationName || bookingForm.user_name || ''"
                                           placeholder="Enter user name..."
                                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                </div>

                                <div x-show="!isStaffUser" class="rounded-xl border border-gray-200 bg-gray-50/80 p-4">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                                        QC ID Verification <span class="text-red-500">*</span>
                                    </label>
                                    <p class="text-xs text-gray-600 mb-2">Upload a clear photo of a Quezon City Citizen ID. The system will read the card using OCR and reject non-QC IDs.</p>
                                    <input type="file" name="qcid_image" accept="image/*" :required="!isStaffUser"
                                        class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer focus:ring-2 focus:ring-teal-500 focus:border-teal-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
                                        @change="handleQcIdUpload($event)">

                                    <template x-if="qcIdPreviewUrl">
                                        <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                                            <img :src="qcIdPreviewUrl" alt="QC ID preview" class="h-48 w-full object-cover">
                                        </div>
                                    </template>

                                    <div x-show="qcIdIsProcessing" x-cloak class="rounded-2xl border border-teal-200 bg-teal-50 px-4 py-4 mt-4">
                                        <div class="flex items-center justify-between gap-4">
                                            <div>
                                                <p class="text-sm font-semibold text-teal-800" x-text="qcIdStatusMessage || 'Reading QC ID…'"></p>
                                                <p class="text-xs text-teal-700 mt-1">OCR is extracting text and checking the QC ID layout.</p>
                                            </div>
                                            <div class="text-lg font-extrabold text-teal-700" x-text="Math.round(qcIdProgress) + '%' "></div>
                                        </div>
                                        <div class="mt-3 h-2 rounded-full bg-teal-100 overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 transition-all duration-200" :style="`width: ${Math.round(qcIdProgress)}%`"></div>
                                        </div>
                                    </div>

                                    <div x-show="qcIdError" x-cloak class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 mt-4" x-text="qcIdError"></div>

                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm mt-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">Verification snapshot</p>
                                                <p class="text-xs text-gray-500">Detected details from the uploaded card.</p>
                                            </div>
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                                :class="qcIdVerification?.is_valid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                                x-text="qcIdVerification?.is_valid ? 'QC ID verified' : 'Waiting for upload'"></span>
                                        </div>
                                        <dl class="mt-4 space-y-3 text-sm">
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-gray-500">Cardholder</dt>
                                                <dd class="text-right font-semibold text-gray-900" x-text="qcIdVerification?.cardholder_name || '—'"></dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-gray-500">ID number</dt>
                                                <dd class="text-right font-semibold text-gray-900" x-text="qcIdVerification?.id_number || '—'"></dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-gray-500">Validity</dt>
                                                <dd class="text-right font-semibold text-gray-900" x-text="qcIdVerification?.valid_until || '—'"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea x-model="bookingForm.description" rows="3"
                                              placeholder="Add any additional details..."
                                              class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 resize-none"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-4">
                                <span class="w-1 h-4 bg-teal-600 rounded"></span>
                                Schedule & Room
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Date <span class="text-red-500">*</span>
                                        <span class="text-xs font-normal text-gray-500">(Step 1)</span>
                                    </label>
                                    <select x-model="bookingForm.date" required
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                        <option value="">Select an available date</option>
                                        <template x-for="dateOption in bookingDateOptions" :key="dateOption.value">
                                            <option :value="dateOption.value" x-text="dateOption.label"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Time Slot <span class="text-red-500">*</span>
                                        <span class="text-xs font-normal text-gray-500">(Step 2)</span>
                                    </label>
                                    <select x-model="bookingForm.time_slot" required
                                            :disabled="!bookingForm.date || isLoadingAvailability"
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 disabled:bg-gray-100 disabled:text-gray-400">
                                        <option value="" x-text="!bookingForm.date ? 'Select date first' : 'Select an available time slot'"></option>
                                        <template x-for="slot in bookingTimeSlots" :key="slot.value">
                                            <option :value="slot.value"
                                                    :disabled="slot.disabled"
                                                    x-text="slot.disabled ? `${slot.label} (Unavailable)` : slot.label"></option>
                                        </template>
                                    </select>
                                    <div x-show="isLoadingAvailability" x-cloak class="mt-2 text-xs text-gray-500">
                                        Refreshing live availability...
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Room <span class="text-red-500">*</span>
                                        <span class="text-xs font-normal text-gray-500">(Step 3)</span>
                                    </label>
                                    <select x-model="bookingForm.room_id" required
                                            :disabled="!bookingForm.time_slot || isLoadingAvailability"
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 disabled:bg-gray-100 disabled:text-gray-400">
                                        <option value="" x-text="!bookingForm.time_slot ? 'Select time first' : 'Select an available room'"></option>
                                        <template x-for="room in availableRooms" :key="`room-${room.id}`">
                                            <option :value="String(room.id)" x-text="room.name + ' (Capacity: ' + (room.standard_limit || room.capacity || '-') + ')'" ></option>
                                        </template>
                                    </select>
                                    <div x-show="availabilityError" x-cloak class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-2 text-xs font-medium text-amber-800" x-text="availabilityError"></div>
                                </div>

                                <div>
                                    <div x-show="isLoadingTimeConflictSuggestions" x-cloak class="mt-2 text-xs text-gray-500">
                                        Checking nearby available slots...
                                    </div>
                                    <div x-show="pendingWarning && !timeConflictMessage" x-cloak class="mt-2 rounded-lg border border-yellow-300 bg-yellow-50 p-3">
                                        <p class="text-xs font-medium text-yellow-800" x-text="pendingWarning"></p>
                                    </div>
                                    <div x-show="timeConflictMessage || timeConflictSuggestions.length" x-cloak class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                        <p class="text-xs font-medium text-amber-800" x-text="timeConflictMessage"></p>

                                        <div x-show="timeConflictSuggestions.length" x-cloak class="mt-2 flex flex-wrap gap-2">
                                            <template x-for="suggestedSlot in timeConflictSuggestions" :key="suggestedSlot.value">
                                                <button type="button"
                                                        @click="applySuggestedTimeSlot(suggestedSlot.value)"
                                                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                                        :class="suggestedSlot.hasPending
                                                            ? 'border-yellow-400 bg-yellow-50 text-yellow-800 hover:bg-yellow-100'
                                                            : 'border-amber-300 bg-white text-amber-800 hover:bg-amber-100'"
                                                        :title="suggestedSlot.hasPending ? 'This slot has a pending reservation' : ''">
                                                    <span x-text="suggestedSlot.label"></span>
                                                    <span x-show="suggestedSlot.hasPending" class="ml-1 inline-block w-2 h-2 rounded-full bg-yellow-400" title="Pending reservation"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Number of Attendees <span class="text-red-500">*</span>
                                    </label>
                                     <select x-model.number="bookingForm.attendees" required
                                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                        <template x-for="count in Array.from({ length: 8 }, (_, index) => index + 5)" :key="`attendee-${count}`">
                                            <option :value="count"
                                                    :disabled="attendeeInputMax && count > Number(attendeeInputMax)"
                                                    x-text="attendeeInputMax && count > Number(attendeeInputMax) ? `${count} (Unavailable)` : count"></option>
                                        </template>
                                     </select>
                                     <p x-show="attendeeGuidance" x-cloak class="mt-1 text-xs text-gray-500" x-text="attendeeGuidance"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-white shrink-0">
                        <p x-show="!isStaffUser && !hasVerifiedRegistration && !qcIdVerification?.is_valid" x-cloak class="mr-auto text-sm text-amber-600">
                            Upload and verify a QC ID before creating the booking.
                        </p>
                        <button type="button" @click="closeBookingModal()"
                                class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting || (!isStaffUser && !hasVerifiedRegistration && !qcIdVerification?.is_valid)"
                                class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="flex items-center gap-2">
                                <i x-show="isSubmitting" class="animate-spin w-4 h-4 fa-icon fa-solid fa-spinner text-base leading-none"></i>
                                <span x-text="isSubmitting ? 'Creating...' : 'Create Booking'"></span>
                            </span>
                        </button>
                    </div>
                </form>
        </div>
        <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeBookingModal()">close</button>
</div>

