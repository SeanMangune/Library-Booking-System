<div x-show="showModal" x-cloak class="modal p-4" :class="{ 'modal-open': showModal }" @keydown.escape.window="closeModal()">
    <div class="modal-box w-11/12 max-w-lg p-0 bg-white rounded-2xl shadow-2xl" @click.stop>
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white">Booking Request Details</h2>
                    <p class="text-purple-200 text-sm">Review and take action</p>
                </div>
                <button @click="closeModal()" class="text-white/80 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6">
            <template x-if="selectedBooking?.requires_capacity_permission">
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl mb-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-7-4a1 1 0 10-2 0v4a1 1 0 102 0V6zm-1 8a1.25 1.25 0 100-2.5A1.25 1.25 0 0010 14z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-semibold text-blue-800">Collaborative Room Permission</span>
                    </div>
                    <p class="text-sm text-blue-700 mb-3" x-text="'Collaborative rooms allow up to ' + selectedBooking?.standard_capacity_limit + ' attendees by default. This request asks for ' + selectedBooking?.attendees + ' attendees and needs librarian approval.'"></p>

                    <div x-show="showExceptionInput" class="mb-3">
                        <textarea x-model="exceptionReason"
                                  placeholder="Enter the approval note for allowing the extra attendees..."
                                  class="w-full p-3 border border-blue-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-300 resize-none"
                                  rows="3"></textarea>
                    </div>

                    <button x-show="!showExceptionInput" @click="showExceptionInput = true"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Add approval note
                    </button>
                </div>
            </template>

            <template x-if="selectedBooking?.exceeds_capacity">
                <div class="p-4 bg-purple-50 border border-purple-200 rounded-xl mb-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                        </svg>
                        <span class="text-sm font-semibold text-purple-800">Capacity Exceeded</span>
                    </div>
                    <p class="text-sm text-purple-700 mb-3" x-text="'This booking requests ' + selectedBooking?.attendees + ' attendees but the room capacity is ' + selectedBooking?.room_capacity + '.'"></p>

                    <div x-show="showExceptionInput" class="mb-3">
                        <textarea x-model="exceptionReason"
                                  placeholder="Enter the reason for capacity exception..."
                                  class="w-full p-3 border border-purple-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-300 resize-none"
                                  rows="3"></textarea>
                    </div>

                    <button x-show="!showExceptionInput && !selectedBooking?.requires_capacity_permission" @click="showExceptionInput = true"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Request Exception Reason
                    </button>
                </div>
            </template>

            <template x-if="selectedBooking?.has_conflict">
                <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl mb-4">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-800">Scheduling Conflict</p>
                        <p class="text-xs text-red-600 mt-0.5">This booking conflicts with an existing reservation.</p>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Room</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.room_name"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Date</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.formatted_date || selectedBooking?.date"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Time</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.formatted_time || selectedBooking?.time"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Duration</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.duration || 'N/A'"></p>
                    </div>
                </div>
            </div>

            <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Requestor Information</h3>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="text-gray-900" x-text="selectedBooking?.user_name"></span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-gray-500" x-text="selectedBooking?.user_email"></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl mb-6">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <div>
                        <p class="text-xs text-gray-500">Attendees</p>
                        <p class="text-sm font-semibold" :class="selectedBooking?.exceeds_capacity ? 'text-purple-600' : 'text-gray-900'" x-text="selectedBooking?.attendees + ' people'"></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Room Capacity</p>
                    <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.room_capacity + ' people'"></p>
                </div>
            </div>

            <template x-if="selectedBooking?.purpose">
                <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Purpose</h3>
                    <p class="text-sm text-gray-600" x-text="selectedBooking?.purpose"></p>
                </div>
            </template>

            <template x-if="selectedBooking?.description">
                <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Description</h3>
                    <p class="text-sm text-gray-600" x-text="selectedBooking?.description"></p>
                </div>
            </template>

            <div class="flex gap-3">
                <button @click="approveBooking()"
                        :disabled="isLoading || ((selectedBooking?.exceeds_capacity || selectedBooking?.requires_capacity_permission) && !showExceptionInput)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="!isLoading || actionType !== 'approve'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg x-show="isLoading && actionType === 'approve'" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="isLoading && actionType === 'approve' ? 'Approving...' : (showExceptionInput ? 'Approve with Note' : 'Approve')"></span>
                </button>
                <button @click="rejectBooking()"
                        :disabled="isLoading"
                        class="flex items-center justify-center gap-2 px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium transition-all disabled:opacity-50">
                    <svg x-show="!isLoading || actionType !== 'reject'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg x-show="isLoading && actionType === 'reject'" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="isLoading && actionType === 'reject' ? 'Rejecting...' : 'Reject'"></span>
                </button>
            </div>
        </div>
    </div>
    <button type="button" class="modal-backdrop bg-black/30 backdrop-blur-sm transition-opacity" @click="closeModal()">close</button>
</div>
