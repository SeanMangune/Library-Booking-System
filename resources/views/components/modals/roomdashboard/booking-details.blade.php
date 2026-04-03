        <div x-show="showModal" 
             x-cloak
               class="modal p-4"
               :class="{ 'modal-open': showModal }"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
                 <div x-show="showModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="modal-box w-11/12 max-w-lg p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col"
                     @click.stop>
                    
                    <!-- Purple Header -->
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-white">Booking Request Details</h2>
                                <p class="text-purple-200 text-sm">Review and take action</p>
                            </div>
                            <button @click="closeModal()" 
                                    class="p-2 rounded-lg hover:bg-white/20 transition-colors">
                                <i class="w-5 h-5 text-white fa-icon fa-solid fa-xmark text-xl leading-none"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                        <!-- Status Badge (for non-pending) -->
                        <div x-show="selectedBooking?.status !== 'pending'" class="mb-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="{
                                      'bg-emerald-50 text-emerald-700 border border-emerald-200': selectedBooking?.status === 'approved',
                                      'bg-red-50 text-red-700 border border-red-200': selectedBooking?.status === 'rejected'
                                  }"
                                  x-text="selectedBooking?.status?.charAt(0).toUpperCase() + selectedBooking?.status?.slice(1)"></span>
                        </div>
                            
                            <!-- Capacity Exceeded Warning -->
                            <template x-if="selectedBooking?.exceeds_capacity && selectedBooking?.status === 'pending'">
                                <div class="p-4 bg-purple-50 border border-purple-200 rounded-xl mb-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-users text-xl leading-none"></i>
                                        <span class="text-sm font-semibold text-purple-800">Capacity Exceeded</span>
                                    </div>
                                    <p class="text-sm text-purple-700 mb-3" x-text="'This booking requests ' + selectedBooking?.attendees + ' attendees but the room capacity is ' + selectedBooking?.room_capacity + '.'"></p>
                                    
                                    <!-- Show textarea when exception is being requested -->
                                    <div x-show="showExceptionInput" class="mb-3">
                                        <textarea 
                                            x-model="exceptionReason"
                                            placeholder="Enter the reason for capacity exception..."
                                            class="w-full p-3 border border-purple-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-300 resize-none"
                                            rows="3"></textarea>
                                    </div>
                                    
                                    <!-- Toggle button -->
                                    <button x-show="!showExceptionInput"
                                            @click="showExceptionInput = true"
                                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        Request Exception Reason
                                    </button>
                                </div>
                            </template>
                            
                            <!-- Conflict Warning -->
                            <template x-if="selectedBooking?.has_conflict">
                                <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl">
                                    <i class="w-5 h-5 text-red-500 shrink-0 mt-0.5 fa-icon fa-solid fa-triangle-exclamation text-xl leading-none"></i>
                                    <div>
                                        <p class="text-sm font-medium text-red-800">Scheduling Conflict</p>
                                        <p class="text-xs text-red-600 mt-0.5" x-text="'Conflicts with booking #' + selectedBooking?.conflicts_with"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Booking Details Grid with Icons -->
                        <div class="grid grid-cols-2 gap-3 mb-6">
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-building text-xl leading-none"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Room</p>
                                    <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.room_name"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-calendar-days text-xl leading-none"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Date</p>
                                    <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.formatted_date || selectedBooking?.date"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-clock text-xl leading-none"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Time</p>
                                    <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.time"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-clock text-xl leading-none"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Duration</p>
                                    <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.duration"></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Requester Info -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Requestor Information</h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="w-4 h-4 text-purple-500 fa-icon fa-solid fa-user text-base leading-none"></i>
                                    <span class="text-gray-900" x-text="selectedBooking?.user_name"></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="w-4 h-4 text-purple-500 fa-icon fa-solid fa-envelope text-base leading-none"></i>
                                    <span class="text-gray-500" x-text="selectedBooking?.user_email"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Attendees -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl mb-6">
                            <div class="flex items-center gap-2">
                                <i class="w-5 h-5 text-purple-500 fa-icon fa-solid fa-users text-xl leading-none"></i>
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
                        
                        <!-- Reason -->
                        <div x-show="selectedBooking?.reason" class="mb-6 p-4 bg-gray-50 rounded-xl">
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Reason for Booking</h3>
                            <p class="text-sm text-gray-600" x-text="selectedBooking?.reason"></p>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div x-show="selectedBooking?.status === 'pending'" class="flex gap-3">
                            <button @click="approveBooking()"
                                    :disabled="isLoading || (selectedBooking?.exceeds_capacity && !showExceptionInput)"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:from-gray-400 disabled:to-gray-500">
                                <i x-show="!isLoading || actionType !== 'approve'" class="w-5 h-5 fa-icon fa-solid fa-circle-check text-xl leading-none"></i>
                                <i x-show="isLoading && actionType === 'approve'" class="animate-spin w-5 h-5 fa-icon fa-solid fa-spinner text-xl leading-none"></i>
                                <span x-text="isLoading && actionType === 'approve' ? 'Approving...' : (showExceptionInput ? 'Approve Exception' : 'Approve Booking')"></span>
                            </button>
                            <button @click="rejectBooking()"
                                    :disabled="isLoading"
                                    class="flex items-center justify-center gap-2 px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <i x-show="!isLoading || actionType !== 'reject'" class="w-5 h-5 fa-icon fa-solid fa-circle-xmark text-xl leading-none"></i>
                                <i x-show="isLoading && actionType === 'reject'" class="animate-spin w-5 h-5 fa-icon fa-solid fa-spinner text-xl leading-none"></i>
                                <span x-text="isLoading && actionType === 'reject' ? 'Rejecting...' : 'Reject'"></span>
                            </button>
                        </div>
                        
                        <div x-show="selectedBooking?.status !== 'pending'" class="p-4 rounded-xl text-center"
                             :class="{
                                 'bg-emerald-50 text-emerald-700': selectedBooking?.status === 'approved',
                                 'bg-red-50 text-red-700': selectedBooking?.status === 'rejected'
                             }">
                            <p class="text-sm font-medium">
                                This booking has been <span x-text="selectedBooking?.status"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
                <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeModal()">close</button>
</div>

