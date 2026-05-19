function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function postJsonWithFallback(urls, body) {
    const uniqueUrls = [...new Set((urls || []).filter(Boolean))];
    let lastResponse = null;
    let lastData = null;

    for (const url of uniqueUrls) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(body || {}),
        });

        const contentType = response.headers.get('content-type') || '';
        const data = contentType.includes('application/json')
            ? await response.json()
            : null;

        if ((response.status === 404 || response.status === 405) && url !== uniqueUrls[uniqueUrls.length - 1]) {
            lastResponse = response;
            lastData = data;
            continue;
        }

        return { response, data };
    }

    return { response: lastResponse, data: lastData };
}

function removeBookingCardFromPendingList(bookingId) {
    if (!bookingId) {
        return;
    }

    const cards = document.querySelectorAll('.booking-card[data-booking]');
    let removedAny = false;

    cards.forEach((card) => {
        try {
            const booking = JSON.parse(card.dataset.booking || '{}');
            if (Number(booking.id) === Number(bookingId)) {
                card.remove();
                removedAny = true;
            }
        } catch (error) {
            // Ignore malformed dataset payloads and continue scanning.
        }
    });

    if (removedAny) {
        const remainingCards = document.querySelectorAll('.booking-card[data-booking]').length;
        if (remainingCards === 0) {
            // All cards removed — let the page reflect the empty state naturally
            const container = document.querySelector('.space-y-5');
            if (container) {
                container.innerHTML = `
                    <div class="bg-white rounded-3xl border-2 border-dashed border-gray-200 p-16 text-center">
                        <div class="w-20 h-20 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-6">
                            <i class="fa-solid fa-inbox text-gray-300 text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 mb-2">Inbox Zero! No pending approvals</h3>
                        <p class="text-base text-gray-500 max-w-sm mx-auto">All requests have been reviewed. Take a break!</p>
                    </div>
                `;
            }
        }
    }
}

export function createApprovalDetailsModalState() {
    return {
        showModal: false,
        selectedBooking: null,
        isLoading: false,
        actionType: null,
        rejectionReason: '',
        decisionPassword: '',


        openApprovalModal(booking) {
            this.selectedBooking = booking;
            this.rejectionReason = '';
            this.decisionPassword = '';
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.selectedBooking = null;
            this.rejectionReason = '';
            this.decisionPassword = '';
        },

        async approveBooking() {
            if (!this.selectedBooking) {
                return;
            }

            const reason = String(this.rejectionReason || '').trim();
            if (!reason) {
                window.notifyApp?.('error', 'An approval note is required.');
                return;
            }

            const password = String(this.decisionPassword || '');
            if (!password) {
                window.notifyApp?.('error', 'Password is required to approve.');
                return;
            }

            this.isLoading = true;
            this.actionType = 'approve';

            try {
                const { response, data } = await postJsonWithFallback([
                    this.selectedBooking?.approve_url,
                    `/rooms/approvals/${this.selectedBooking.id}/approve`,
                    `/approvals/${this.selectedBooking.id}/approve`,
                    `/bookings/${this.selectedBooking.id}/approve`,
                ], {
                    reason,
                    password,
                });

                if (!response.ok || !data?.success) {
                    const fallbackMessage = response.status
                        ? `Failed to approve booking (HTTP ${response.status})`
                        : 'Failed to approve booking';
                    const message = data?.message || fallbackMessage;
                    window.notifyApp?.('error', message);

                    if (response.status === 422 && /automatically rejected/i.test(String(message))) {
                        removeBookingCardFromPendingList(this.selectedBooking.id);
                    }
                    return;
                }

                const booking = data.booking || { ...this.selectedBooking };

                // Use encrypted QR code payload for the QR code URL if available
                if (!booking.qr_code_url && booking.qr_code_encrypted) {
                    booking.qr_code_url = `/bookings/qr/${booking.qr_code_encrypted}?format=png`;
                } else if (!booking.qr_code_url && booking.qr_token) {
                    // fallback for legacy
                    booking.qr_code_url = `/bookings/qr/${booking.qr_token}?format=png`;
                }

                if (!booking.booking_status && booking.qr_status) {
                    booking.booking_status = booking.qr_status;
                }

                this.approvedBooking = booking;
                removeBookingCardFromPendingList(this.selectedBooking.id);
                this.qrImageFailed = false;
                this.rejectionReason = '';
                this.decisionPassword = '';
                this.showModal = false;
                this.showSuccessModal = true;
                window.notifyApp?.('success', 'Booking approved successfully.');
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

            const reason = String(this.rejectionReason || '').trim();
            if (!reason) {
                window.notifyApp?.('error', 'A rejection note is required.');
                return;
            }

            const password = String(this.decisionPassword || '');
            if (!password) {
                window.notifyApp?.('error', 'Password is required to reject.');
                return;
            }

            this.isLoading = true;
            this.actionType = 'reject';

            try {
                const { response, data } = await postJsonWithFallback([
                    this.selectedBooking?.reject_url,
                    `/rooms/approvals/${this.selectedBooking.id}/reject`,
                    `/approvals/${this.selectedBooking.id}/reject`,
                    `/bookings/${this.selectedBooking.id}/reject`,
                ], {
                    reason,
                    password,
                });

                if (!response.ok || !data?.success) {
                    const fallbackMessage = response.status
                        ? `Failed to reject booking (HTTP ${response.status})`
                        : 'Failed to reject booking';
                    window.notifyApp?.('error', data?.message || fallbackMessage);
                    return;
                }

                removeBookingCardFromPendingList(this.selectedBooking.id);
                this.showModal = false;
                this.showRejectModal = true;
                this.rejectionReason = '';
                this.decisionPassword = '';
                window.notifyApp?.('error', 'Booking has been rejected.');
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
