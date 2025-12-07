/* =================== FLASH MESSAGE =================== */
function showFlashMessage(message, type = "success") {
    const flash = document.createElement("div");
    flash.className = `flash-message ${type}`;
    flash.textContent = message;
    document.body.appendChild(flash);

    setTimeout(() => flash.classList.add("show"), 10);
    setTimeout(() => {
        flash.classList.remove("show");
        setTimeout(() => flash.remove(), 300);
    }, 3000);
}
function showInputModal(title = "Enter reason") {
    return new Promise((resolve) => {
        const modal = document.getElementById('decline-modal');
        const textarea = document.getElementById('decline-reason');
        const submitBtn = document.getElementById('decline-submit');
        const cancelBtn = document.getElementById('decline-cancel');

        if (!modal || !textarea || !submitBtn || !cancelBtn) return resolve(null);

        modal.style.display = 'flex';
        textarea.value = '';
        textarea.focus();

        const cleanup = () => {
            modal.style.display = 'none';
            submitBtn.removeEventListener('click', submitHandler);
            cancelBtn.removeEventListener('click', cancelHandler);
        };

        const submitHandler = () => {
            cleanup();
            resolve(textarea.value.trim() || "No reason provided");
        };
        const cancelHandler = () => {
            cleanup();
            resolve(null);
        };

        submitBtn.addEventListener('click', submitHandler);
        cancelBtn.addEventListener('click', cancelHandler);

        // Close modal on click outside
        modal.addEventListener('click', function outsideClick(e) {
            if (e.target === modal) {
                cleanup();
                resolve(null);
                modal.removeEventListener('click', outsideClick);
            }
        });
    });
}
document.addEventListener('click', function (e) {
    const target = e.target;
    const row = target.closest('tr');
    const requestId = row?.dataset.requestId;

    if (!row || !requestId) return;

    // Decline
    if (target.classList.contains('decline-btn')) {
        e.preventDefault();

        (async () => {  // <- async IIFE
            const reason = await showInputModal('Enter reason for declining:');
            if (reason === null) return;

            const declineReason = reason.trim() === '' ? 'No reason provided' : reason.trim();
            const res = await postRequest('update_request.php', {
                request_id: requestId,
                action: 'decline',
                reason: declineReason
            });
            if (!res.success) return showFlashMessage(res.message || 'Failed to decline request', 'error');

            row.remove();
            showFlashMessage('Request declined successfully!', 'success');
        })();
    }
});

/* =================== TOGGLE NAVIGATION BAR =================== */
let navMenu = document.getElementById('navMenu');
let toggleBtn = document.getElementById('toggleBtn');

function myMenuFunction() {
    if (!navMenu) return;
    if (navMenu.className === 'nav-menu') {
        navMenu.className += ' responsive';
        if (toggleBtn) toggleBtn.className = 'uil uil-multiply';
    } else {
        navMenu.className = 'nav-menu';
        if (toggleBtn) toggleBtn.className = 'uil uil-bars';
    }
}

function closeMenu() {
    if (navMenu) navMenu.className = 'nav-menu';
}
document.querySelectorAll('.nav-link').forEach(link => link.addEventListener('click', closeMenu));

/* =================== HEADER SHADOW ON SCROLL =================== */
function headerShadow() {
    const navHeader = document.getElementById('header');
    if (!navHeader) return;
    if (document.body.scrollTop > 50 || document.documentElement.scrollTop > 50) {
        navHeader.style.boxShadow = '0 4px 10px #000000BB';
        navHeader.style.height = '70px';
        navHeader.style.lineHeight = '70px';
        navHeader.style.background = '#cfcfcf';
        navHeader.style.backdropFilter = 'blur(8px)';
    } else {
        navHeader.style.boxShadow = 'none';
        navHeader.style.height = '90px';
        navHeader.style.lineHeight = '90px';
        navHeader.style.background = '#fff';
        navHeader.style.backdropFilter = 'blur(0px)';
    }
}
window.addEventListener('scroll', headerShadow);
window.addEventListener('load', headerShadow);

/* =================== LIGHTBOX =================== */
const lightboxOverlay = document.getElementById('lightboxOverlay');
const lightboxImage = document.getElementById('lightboxImage');
const lightboxPDF = document.getElementById('lightboxPDF');
const attachmentSelector = document.getElementById('attachmentSelector');
const closeLightbox = document.getElementById('closeLightbox');

function displayAttachment(attachmentData) {
    let url = '';

    // Handle Cloudinary JSON format with url property
    if (typeof attachmentData === 'object' && attachmentData.url) {
        url = attachmentData.url;
    } else if (typeof attachmentData === 'string') {
        // Direct URL string
        url = attachmentData;
    }

    if (!url) return;

    // Check if it's an image by URL extension
    if (isImageFile(url)) {
        lightboxPDF.style.display = 'none';
        lightboxImage.style.display = 'block';
        lightboxImage.src = url;
    } else {
        // For PDFs
        lightboxImage.style.display = 'none';
        lightboxPDF.style.display = 'block';
        lightboxPDF.src = url;
    }
}

function parseAttachments(attachmentString) {
    if (!attachmentString) return [];

    try {
        // Try to parse as JSON first (Cloudinary format)
        const parsed = JSON.parse(attachmentString);
        if (Array.isArray(parsed)) {
            return parsed;
        }
        return [parsed];
    } catch (e) {
        // Fallback: comma-separated URLs (old format)
        return attachmentString.split(',').map(a => a.trim()).filter(a => a);
    }
}

function getFileExtension(url) {
    // Extract extension from URL (handle Cloudinary URLs with query params)
    const path = url.split('?')[0]; // Remove query parameters
    const extension = path.split('.').pop().toLowerCase();
    return extension;
}

function isImageFile(url) {
    const ext = getFileExtension(url);
    return ['jpg', 'jpeg', 'png'].includes(ext);
}

document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('view-btn')) {
        const attachmentString = e.target.dataset.attachment;
        const attachments = parseAttachments(attachmentString);

        if (attachments.length === 0) return;

        console.log('Parsed Attachments:', attachments);

        attachmentSelector.innerHTML = '';
        attachments.forEach((att, index) => {
            const option = document.createElement('option');
            option.value = JSON.stringify(att); // Store the full object as JSON

            // Display name
            let displayName = '';
            if (typeof att === 'object') {
                displayName = att.original_name || `Attachment ${index + 1}`;
            } else {
                displayName = attachments.length > 1 ? `Attachment ${index + 1}` : att;
            }

            option.textContent = displayName;
            attachmentSelector.appendChild(option);
        });

        displayAttachment(attachments[0]);
        lightboxOverlay.style.display = 'flex';
    }

    if (e.target === closeLightbox) {
        lightboxOverlay.style.display = 'none';
        lightboxImage.src = '';
        lightboxPDF.src = '';
    }
});

attachmentSelector.addEventListener('change', () => {
    const selected = attachmentSelector.value;
    if (selected) {
        try {
            const attachmentData = JSON.parse(selected);
            displayAttachment(attachmentData);
        } catch (e) {
            // Fallback for old format
            displayAttachment(selected);
        }
    }
});

/* =================== GENERIC POST FUNCTION =================== */
async function postRequest(url, data) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await res.json();
    } catch (err) {
        console.error(err);
        return { success: false, message: 'Request failed' };
    }
}

/* =================== PROCESSING COUNTDOWN =================== */
function startProcessingCountdownForRow(row) {
    const countdownCell = row.querySelector('.countdown');
    if (!countdownCell) return;

    const processingEnd = row.dataset.processingEnd;
    if (!processingEnd) {
        countdownCell.textContent = '-- : -- : --';
        return;
    }

    if (row.dataset.timerId) clearInterval(row.dataset.timerId);

    const interval = setInterval(async () => {
        const now = Date.now();
        const endTime = new Date(processingEnd).getTime();
        let remaining = Math.floor((endTime - now) / 1000);

        if (remaining <= 0) {
            clearInterval(interval);
            countdownCell.textContent = '00 : 00 : 00';

            const requestId = row.dataset.requestId;
            if (!requestId) return;

            const data = await postRequest('update_request.php', { request_id: requestId, action: 'finish', auto: true });
            if (data.success) {
                row.remove();
                const toBeClaim = document.querySelector('#claimed-table tbody');
                if (!toBeClaim) return;

                const tr = row.cloneNode(true);
                tr.querySelector('.countdown')?.remove();
                const td = tr.querySelector('td:last-child');
                td.innerHTML = `<input type="date" class="claim-date" data-request="${requestId}" value="">`;
                toBeClaim.appendChild(tr);
                setupClaimDateInputs();
                showFlashMessage('Request moved to claim automatically!', 'success');
            }
        } else {
            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;
            countdownCell.textContent =
                String(hours).padStart(2, '0') + ' : ' +
                String(minutes).padStart(2, '0') + ' : ' +
                String(seconds).padStart(2, '0');
        }
    }, 500);

    row.dataset.timerId = interval;
}

function startProcessingCountdown() {
    document.querySelectorAll('#processing-box tbody tr').forEach(startProcessingCountdownForRow);
}
document.addEventListener('DOMContentLoaded', startProcessingCountdown);


/* =================== CLAIM DATE HANDLING =================== */
function setupClaimDateInputs() {
    document.querySelectorAll('.claim-date').forEach(input => {
        input.addEventListener('change', async () => {
            const requestId = input.dataset.request;
            const claimDate = input.value;
            if (!requestId || !claimDate) return;

            try {
                const res = await postRequest('update_request.php', {
                    request_id: requestId,
                    action: 'update_claim_date',
                    claim_date: claimDate
                });

                if (res.success) {
                    showFlashMessage(res.message, 'success');
                } else {
                    showFlashMessage('Failed: ' + res.message, 'error');
                }
            } catch (err) {
                console.error("Error updating claim date:", err);
                showFlashMessage('Something went wrong.', 'error');
            }
        });
    });
}
setupClaimDateInputs();

/* =================== APPROVE / FINISH / PENDING / DECLINE BUTTONS =================== */
document.addEventListener('click', async function (e) {
    const target = e.target;
    const row = target.closest('tr');
    const requestId = row?.dataset.requestId;

    if (!row || !requestId) return;

    // Approve
    if (target.classList.contains('approve-btn')) {
        e.preventDefault();

        const res = await postRequest('update_request.php', { request_id: requestId, action: 'approve' });
        if (!res.success) return showFlashMessage(res.message || 'Failed to approve request', 'error');

        const processingTable = document.querySelector('#processing-box tbody');
        if (!processingTable) return;

        row.dataset.scheduledDate = res.request.scheduled_date;
        let countdownCell = row.querySelector('.countdown');
        if (!countdownCell) {
            countdownCell = row.insertCell(-1);
            countdownCell.className = 'countdown';
        }

        processingTable.appendChild(row);
        startProcessingCountdownForRow(row);
        showFlashMessage('Request approved successfully!', 'success');
    }

    // Finish
    if (target.classList.contains('finish-btn')) {
        e.preventDefault();

        const res = await postRequest('update_request.php', { request_id: requestId, action: 'finish' });
        if (!res.success) return showFlashMessage(res.message || 'Failed to proceed.', 'error');

        const claimedTable = document.querySelector('#claimed-table tbody');
        if (!claimedTable) return;

        row.querySelector('.countdown')?.remove();
        const td = row.querySelector('td:last-child');
        td.innerHTML = `<input type="date" class="claim-date" data-request="${requestId}" value="">`;
        claimedTable.appendChild(row);
        setupClaimDateInputs();
        showFlashMessage('Request moved to claim successfully!', 'success');
    }

    // Pending
    if (target.classList.contains('pending-btn')) {
        if (row.dataset.walkin === "1") return;
        e.preventDefault();

        const res = await postRequest('update_request.php', { request_id: requestId, action: 'pending' });
        if (!res.success) return showFlashMessage(res.message || 'Failed to revert request.', 'error');

        const pendingTable = document.querySelector('#pending-box tbody');
        if (pendingTable) pendingTable.appendChild(row);
        showFlashMessage('Request sent back to pending!', 'success');
    }

    // Decline
    if (target.classList.contains('decline-btn')) {
        e.preventDefault();

        // Use your custom input modal
        const reason = await showInputModal('Enter reason for declining:', 'Reason...');
        if (reason === null) return; // user canceled

        const declineReason = reason.trim() === '' ? 'No reason provided' : reason.trim();
        const res = await postRequest('update_request.php', { request_id: requestId, action: 'decline', reason: declineReason });
        if (!res.success) return showFlashMessage(res.message || 'Failed to decline request', 'error');

        row.remove();
        showFlashMessage('Request declined successfully!', 'success');
    }
});


/* =================== COMPLETED REQUESTS =================== */
const completedPicker = document.getElementById('completed-date-picker');
if (completedPicker) {
    const savedDate = localStorage.getItem('completedDate');
    completedPicker.value = savedDate || new Date().toISOString().split('T')[0];

    async function fetchCompleted(date) {
        if (!date) return;

        const res = await postRequest('fetch_completed.php', { completed_date: date });
        const tbody = document.querySelector('#completed-table tbody');
        tbody.innerHTML = '';

        if (res.success && res.requests.length > 0) {
            res.requests.forEach(req => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${req.id}</td>
                    <td>${req.first_name} ${req.last_name}</td>
                    <td>${req.documents}</td>
                    <td>${req.attachment ? `<button class="action-btn view-btn" data-attachment="${req.attachment}">View</button>` : 'No attachment'}</td>
                    <td>${req.completed_date || ''}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr></tr>`;
            showFlashMessage('', 'info');
        }
    }

    fetchCompleted(completedPicker.value);

    completedPicker.addEventListener('change', () => {
        localStorage.setItem('completedDate', completedPicker.value);
        fetchCompleted(completedPicker.value);
    });
}

/* =================== WALK-IN MODAL & CONFIRMATION =================== */
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("walkin-modal-unique");
    const openBtn = document.getElementById("walkin-all-btn");
    const closeBtn = document.getElementById("walkin-close-unique");
    const form = document.getElementById("walkin-form-unique");

    const confirmModal = document.getElementById("walkin-confirm-modal");
    const confirmClose = document.getElementById("walkin-confirm-close");
    const confirmCancel = document.getElementById("walkin-confirm-cancel");
    const confirmSubmit = document.getElementById("walkin-confirm-submit");
    const confirmDetails = document.getElementById("walkin-confirm-details");

    const showModal = () => {
        modal.style.display = "block";
        localStorage.setItem("walkinModalOpen", "true");
    };
    const hideModal = () => {
        modal.style.display = "none";
        localStorage.setItem("walkinModalOpen", "false");
    };

    openBtn.addEventListener("click", showModal);
    closeBtn.addEventListener("click", hideModal);
    window.addEventListener("click", (e) => { if (e.target === modal) hideModal(); });

    if (localStorage.getItem("walkinModalOpen") === "true") modal.style.display = "block";

    const saveFormData = () => {
        const data = {};
        form.querySelectorAll("input, select, textarea").forEach(el => {
            if (el.type === "file") return;
            if (el.type === "checkbox") data[el.name + "_" + el.value] = el.checked;
            else data[el.name] = el.value;
        });
        localStorage.setItem("walkinFormData", JSON.stringify(data));
    };

    const loadFormData = () => {
        const data = JSON.parse(localStorage.getItem("walkinFormData") || "{}");
        form.querySelectorAll("input, select, textarea").forEach(el => {
            if (el.type === "file") return;
            if (el.type === "checkbox") el.checked = data[el.name + "_" + el.value] || false;
            else el.value = data[el.name] || "";
        });
    };

    form.addEventListener("input", saveFormData);
    loadFormData();

    form.addEventListener("submit", (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        let html = "<ul>";
        for (const [key, value] of formData.entries()) {
            if (key === "attachment[]" || key === "documents[]") continue;
            html += `<li><strong>${key}:</strong> ${value}</li>`;
        }
        const documents = formData.getAll("documents[]");
        if (documents.length) html += `<li><strong>Documents:</strong> ${documents.join(", ")}</li>`;
        html += "</ul>";

        confirmDetails.innerHTML = html;
        confirmModal.style.display = "block";
    });

    confirmClose.addEventListener("click", () => confirmModal.style.display = "none");
    confirmCancel.addEventListener("click", () => confirmModal.style.display = "none");
    confirmSubmit.addEventListener("click", () => {
        confirmModal.style.display = "none";
        form.submit();
        showFlashMessage('Walk-in request submitted successfully!', 'success');
    });

    window.addEventListener("click", (e) => { if (e.target === confirmModal) confirmModal.style.display = "none"; });
});
