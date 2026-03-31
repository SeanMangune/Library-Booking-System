import { createApprovalDetailsModalState } from './details';
import { createApprovalSuccessModalState } from './success';
import { createApprovalRejectModalState } from './reject';

export function createApprovalsApp() {
    return {
        ...createApprovalDetailsModalState(),
        ...createApprovalSuccessModalState(),
        ...createApprovalRejectModalState(),

        init() {
            if (this.showModal || this.showSuccessModal || this.showRejectModal) {
                document.body.style.overflow = 'hidden';
            }
        }
    };
}

window.createApprovalsApp = createApprovalsApp;
window.approvalsApp = createApprovalsApp;
