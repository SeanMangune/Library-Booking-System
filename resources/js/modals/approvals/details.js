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
            window.location.reload();
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
        approvalsReloadTimer: null,

        queueApprovalsReload(delayMs = 1200) {
            if (this.approvalsReloadTimer) {
                return;
            }

            this.approvalsReloadTimer = window.setTimeout(() => {
                window.location.reload();
            }, delayMs);
        },

        openApprovalModal(booking) {
            this.selectedBooking = booking;
            this.rejectionReason = '';
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.selectedBooking = null;
            this.rejectionReason = '';
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
                });

                if (!response.ok || !data?.success) {
                    const fallbackMessage = response.status
                        ? `Failed to approve booking (HTTP ${response.status})`
                        : 'Failed to approve booking';
                    window.notifyApp?.('error', data?.message || fallbackMessage);
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
                this.showModal = false;
                this.showSuccessModal = true;
                this.queueApprovalsReload(1500);
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
                });

                if (!response.ok || !data?.success) {
                    const fallbackMessage = response.status
                        ? `Failed to reject booking (HTTP ${response.status})`
                        : 'Failed to reject booking';
                    window.notifyApp?.('error', data?.message || fallbackMessage);
                    return;
                }

                this.showModal = false;
                this.showRejectModal = true;
                this.rejectionReason = '';
                this.queueApprovalsReload();
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
