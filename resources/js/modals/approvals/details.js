function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
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

                const data = await response.json();

                if (data.success) {
                    const booking = data.booking || { ...this.selectedBooking };

                    if (!booking.qr_code_url && booking.qr_token) {
                        booking.qr_code_url = `/bookings/qr/${booking.qr_token}`;
                    }

                    this.approvedBooking = booking;
                    this.qrImageFailed = false;
                    this.showModal = false;
                    this.showSuccessModal = true;
                } else {
                    window.notifyApp?.('error', data.message || 'Failed to approve booking');
                }
            } catch (error) {
                console.error('Error:', error);
                window.notifyApp?.('error', 'An error occurred');
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

                const data = await response.json();

                if (data.success) {
                    this.showModal = false;
                    this.showRejectModal = true;
                } else {
                    window.notifyApp?.('error', data.message || 'Failed to reject booking');
                }
            } catch (error) {
                console.error('Error:', error);
                window.notifyApp?.('error', 'An error occurred');
            } finally {
                this.isLoading = false;
                this.actionType = null;
            }
        },
    };
}
