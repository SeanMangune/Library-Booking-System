export function createApprovalRejectModalState() {
    return {
        showRejectModal: this.$persist(false).as('approve_showRejectModal'),

        closeRejectModal() {
            this.showRejectModal = false;
            window.location.reload();
        },
    };
}
