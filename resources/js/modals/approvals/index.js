import { createApprovalDetailsModalState } from './details';
import { createApprovalSuccessModalState } from './success';
import { createApprovalRejectModalState } from './reject';

export function createApprovalsApp() {
    return {
        ...createApprovalDetailsModalState(),
        ...createApprovalSuccessModalState(),
        ...createApprovalRejectModalState(),
    };
}

window.createApprovalsApp = createApprovalsApp;
window.approvalsApp = createApprovalsApp;
