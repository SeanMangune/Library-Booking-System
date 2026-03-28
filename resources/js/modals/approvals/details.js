function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/**
 * Updates the approvals page immediately after approve/reject (no full reload).
 */
function syncApprovalsPageAfterAction(bookingId, action) {
    if (!bookingId || !action) {
        return;
    }

    const card = document.querySelector(`[data-booking-id="${bookingId}"]`);
    if (card) {
        card.remove();
    }

    const bump = (el, delta) => {
        if (!el) {
            return;
        }
        const n = parseInt(String(el.textContent).trim(), 10);
        if (Number.isNaN(n)) {
            return;
        }
        el.textContent = String(Math.max(0, n + delta));
    };

    const pendingEl = document.querySelector('[data-approvals-stat="pending"]');
    const approvedEl = document.querySelector('[data-approvals-stat="approved"]');
    const rejectedEl = document.querySelector('[data-approvals-stat="rejected"]');

    if (action === 'approve') {
        bump(pendingEl, -1);
        bump(approvedEl, 1);
    } else if (action === 'reject') {
        bump(pendingEl, -1);
        bump(rejectedEl, 1);
    }

    window.dispatchEvent(new CustomEvent('app:notifications-refresh'));

    const list = document.querySelector('[data-role="approvals-bookings-list"]');
    if (list && !list.querySelector('[data-booking-id]')) {
        list.innerHTML = `
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i class="w-8 h-8 text-gray-400 fa-icon fa-solid fa-circle-check text-3xl leading-none"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No pending approvals</h3>
                <p class="mt-1 text-sm text-gray-500">All booking requests have been reviewed.</p>
            </div>
        `;
    }
}

export function createApprovalDetailsModalState() {
    return {
        showModal: false,
        selectedBooking: null,
        isLoading: false,
        actionType: null,
        showExceptionInput: false,
        exceptionReason: '',

        openApprovalModal(booking) {
            this.selectedBooking = booking;
            this.showExceptionInput = false;
            this.exceptionReason = '';
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.selectedBooking = null;
        },

        async approveBooking() {
            if (!this.selectedBooking) {
                return;
            }

            this.isLoading = true;
            this.actionType = 'approve';

            try {
                const response = await fetch(`/rooms/approvals/${this.selectedBooking.id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        reason: this.exceptionReason,
                    }),
                });

                const contentType = response.headers.get('content-type') || '';
                const data = contentType.includes('application/json')
                    ? await response.json()
                    : null;

                if (!response.ok || !data?.success) {
                    const fallbackMessage = response.status
                        ? `Failed to approve booking (HTTP ${response.status})`
                        : 'Failed to approve booking';
                    window.notifyApp?.('error', data?.message || fallbackMessage);
                    return;
                }

                const booking = data.booking || { ...this.selectedBooking };

                if (!booking.qr_code_url && booking.qr_token) {
                    booking.qr_code_url = `/bookings/qr/${booking.qr_token}`;
                }

                if (!booking.booking_status && booking.qr_status) {
                    booking.booking_status = booking.qr_status;
                }

                this.approvedBooking = booking;
                this.qrImageFailed = false;
                this.showModal = false;
                this.showSuccessModal = true;
                syncApprovalsPageAfterAction(this.selectedBooking.id, 'approve');
            } catch (error) {
                console.error('Error:', error);
                window.notifyApp?.('error', error?.message || 'An error occurred while approving the booking');
            } finally {
                this.isLoading = false;
                this.actionType = null;
            }
        },

        async rejectBooking() {
            if (!this.selectedBooking) {
                return;
            }

            this.isLoading = true;
            this.actionType = 'reject';

            try {
                const response = await fetch(`/rooms/approvals/${this.selectedBooking.id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                const contentType = response.headers.get('content-type') || '';
                const data = contentType.includes('application/json')
                    ? await response.json()
                    : null;

                if (!response.ok || !data?.success) {
                    const fallbackMessage = response.status
                        ? `Failed to reject booking (HTTP ${response.status})`
                        : 'Failed to reject booking';
                    window.notifyApp?.('error', data?.message || fallbackMessage);
                    return;
                }

                this.showModal = false;
                this.showRejectModal = true;
                syncApprovalsPageAfterAction(this.selectedBooking.id, 'reject');
            } catch (error) {
                console.error('Error:', error);
                window.notifyApp?.('error', error?.message || 'An error occurred while rejecting the booking');
            } finally {
                this.isLoading = false;
                this.actionType = null;
            }
        },
    };
}
