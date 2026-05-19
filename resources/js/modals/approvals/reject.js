export function createApprovalRejectModalState() {
    return {
        showRejectModal: false,

        closeRejectModal() {
            this.showRejectModal = false;
        },
    };
}
