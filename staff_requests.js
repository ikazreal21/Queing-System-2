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

function displayAttachment(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    if (ext === 'pdf') {
        lightboxImage.style.display = 'none';
        lightboxPDF.style.display = 'block';
        lightboxPDF.src = 'uploads/' + filename;
    } else {
        lightboxPDF.style.display = 'none';
        lightboxImage.style.display = 'block';
        lightboxImage.src = 'uploads/' + filename;
    }
}

document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('view-btn')) {
        const attachments = e.target.dataset.attachment.split(',').map(a => a.trim()).filter(a => a);
        if (attachments.length === 0) return;

        attachmentSelector.innerHTML = '';
        attachments.forEach((att, index) => {
            const option = document.createElement('option');
            option.value = att;
            option.textContent = attachments.length > 1 ? `Attachment ${index + 1}` : att;
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
    if (selected) displayAttachment(selected);
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

    const scheduledDate = row.dataset.scheduledDate;
    if (!scheduledDate) {
        countdownCell.textContent = '-- : -- : --';
        return;
    }

    if (row.dataset.timerId) clearInterval(row.dataset.timerId);

    const interval = setInterval(async () => {
        const now = Date.now();
        const endTime = new Date(scheduledDate).getTime();
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
                    claim_date: claimDate // ✅ only send date, no status
                });

                if (res.success) {
                    alert(res.message); // backend will say whether it's In Queue Now or To Be Claimed
                } else {
                    alert('Failed: ' + res.message);
                }
            } catch (err) {
                console.error("Error updating claim date:", err);
                alert('Something went wrong.');
            }
        });
    });
}
setupClaimDateInputs();


/* =================== APPROVE / FINISH / PENDING / DECLINE BUTTONS =================== */
document.addEventListener('click', async function(e) {
    const target = e.target;
    const row = target.closest('tr');
    const requestId = row?.dataset.requestId;

    if (!row || !requestId) return;

    // Approve
    if (target.classList.contains('approve-btn')) {
        e.preventDefault();
        if (!confirm('Approve this request?')) return;

        const res = await postRequest('update_request.php', { request_id: requestId, action: 'approve' });
        if (!res.success) return alert(res.message || 'Failed to approve request');

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
    }

    // Proceed to Claim / Finish
    if (target.classList.contains('finish-btn')) {
        e.preventDefault();
        if (!confirm('Proceed this request to claim?')) return;

        const res = await postRequest('update_request.php', { request_id: requestId, action: 'finish' });
        if (!res.success) return alert(res.message || 'Failed to proceed.');

        const claimedTable = document.querySelector('#claimed-table tbody');
        if (!claimedTable) return;

        row.querySelector('.countdown')?.remove();
        const td = row.querySelector('td:last-child');
        td.innerHTML = `<input type="date" class="claim-date" data-request="${requestId}" value="">`;
        claimedTable.appendChild(row);
        setupClaimDateInputs();
    }

    // Back to Pending (disabled for walk-ins)
    if (target.classList.contains('pending-btn')) {
        if (row.dataset.walkin === "1") return;
        e.preventDefault();
        if (!confirm('Send this request back to pending?')) return;

        const res = await postRequest('update_request.php', { request_id: requestId, action: 'pending' });
        if (!res.success) return alert(res.message || 'Failed to revert request.');

        const pendingTable = document.querySelector('#pending-box tbody');
        if (pendingTable) pendingTable.appendChild(row);
    }

    // Decline
    if (target.classList.contains('decline-btn')) {
        e.preventDefault();
        const reason = prompt('Enter reason for declining:', '');
        if (reason === null) return;

        const declineReason = reason.trim() === '' ? 'No reason provided' : reason.trim();
        const res = await postRequest('update_request.php', { request_id: requestId, action: 'decline', reason: declineReason });
        if (!res.success) return alert(res.message || 'Failed to decline request');
        row.remove();
    }
});

/* =================== COMPLETED REQUESTS =================== */
const completedPicker = document.getElementById('completed-date-picker');
if (completedPicker) {
    const savedDate = localStorage.getItem('completedDate');
    if (savedDate) {
        completedPicker.value = savedDate;
    } else {
        const today = new Date();
        completedPicker.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;
    }

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
            tbody.innerHTML = `<tr><td colspan="5">No records found for selected date.</td></tr>`;
        }
    }

    fetchCompleted(completedPicker.value);

    completedPicker.addEventListener('change', () => {
        localStorage.setItem('completedDate', completedPicker.value);
        fetchCompleted(completedPicker.value);
    });
}
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("walkin-modal-unique");
    const openBtn = document.getElementById("walkin-all-btn");
    const closeBtn = document.getElementById("walkin-close-unique");
    const form = document.getElementById("walkin-form-unique");

    // Confirmation modal elements
    const confirmModal = document.getElementById("walkin-confirm-modal");
    const confirmClose = document.getElementById("walkin-confirm-close");
    const confirmCancel = document.getElementById("walkin-confirm-cancel");
    const confirmSubmit = document.getElementById("walkin-confirm-submit");
    const confirmDetails = document.getElementById("walkin-confirm-details");

    // Show/hide main modal
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
    window.addEventListener("click", (e) => {
        if (e.target === modal) hideModal();
    });

    // Restore main modal state
    if (localStorage.getItem("walkinModalOpen") === "true") {
        modal.style.display = "block";
    }

    // Persist form inputs in localStorage
    const saveFormData = () => {
        const data = {};
        form.querySelectorAll("input, select, textarea").forEach(el => {
            if (el.type === "file") return; // ⬅ skip file inputs
            if (el.type === "checkbox") {
                data[el.name + "_" + el.value] = el.checked;
            } else {
                data[el.name] = el.value;
            }
        });
        localStorage.setItem("walkinFormData", JSON.stringify(data));
    };

    const loadFormData = () => {
        const data = JSON.parse(localStorage.getItem("walkinFormData") || "{}");
        form.querySelectorAll("input, select, textarea").forEach(el => {
            if (el.type === "file") return; // ⬅ skip file inputs
            if (el.type === "checkbox") {
                el.checked = data[el.name + "_" + el.value] || false;
            } else {
                el.value = data[el.name] || "";
            }
        });
    };

    form.addEventListener("input", saveFormData);
    loadFormData();

    // Intercept form submit to show confirmation modal
    form.addEventListener("submit", (e) => {
        e.preventDefault(); // stop default submission

        // Gather form data for preview
        const formData = new FormData(form);
        let html = "<ul>";
        for (const [key, value] of formData.entries()) {
            if (key === "attachment[]" || key === "documents[]") continue; // ⬅ skip files + documents in loop
            html += `<li><strong>${key}:</strong> ${value}</li>`;
        }
        const documents = formData.getAll("documents[]");
        if (documents.length) html += `<li><strong>Documents:</strong> ${documents.join(", ")}</li>`;
        html += "</ul>";

        confirmDetails.innerHTML = html;
        confirmModal.style.display = "block"; // show confirmation modal
    });

    // Confirmation modal buttons
    confirmClose.addEventListener("click", () => confirmModal.style.display = "none");
    confirmCancel.addEventListener("click", () => confirmModal.style.display = "none");
    confirmSubmit.addEventListener("click", () => {
        confirmModal.style.display = "none";
        form.submit(); // finally submit
    });

    // Close confirmation modal if clicked outside
    window.addEventListener("click", (e) => {
        if (e.target === confirmModal) confirmModal.style.display = "none";
    });
});