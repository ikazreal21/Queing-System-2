/* =========== TOGGLE NAVIGATION BAR =========== */
const navMenu = document.getElementById('navMenu');
const toggleBtn = document.getElementById('toggleBtn');

function myMenuFunction() {
    if (!navMenu) return;
    if (navMenu.className === 'nav-menu') {
        navMenu.className += ' responsive';
        toggleBtn.className = 'uil uil-multiply';
    } else {
        navMenu.className = 'nav-menu';
        toggleBtn.className = 'uil uil-bars';
    }
}

function closeMenu() {
    if (!navMenu) return;
    navMenu.className = 'nav-menu';
}

document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', closeMenu);
});

/* =========== CHANGE HEADER ON SCROLL =========== */
window.addEventListener('scroll', headerShadow);
window.addEventListener('load', headerShadow);

function headerShadow() {
    const navHeader = document.getElementById('header');
    if (!navHeader) return;

    if (window.scrollY > 50) {
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

/* =========== VIEW DETAILS MODAL (Attachments only) =========== */
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("detailsModal");
    if (!modal) return;

    const closeModal = modal.querySelector(".close");
    const attachmentContainer = document.getElementById("attachmentContainer");

    const requestID = document.getElementById("requestID");
    const firstName = document.getElementById("firstName");
    const lastName = document.getElementById("lastName");
    const studentNumber = document.getElementById("studentNumber");
    const section = document.getElementById("section");
    const lastSchoolYear = document.getElementById("lastSchoolYear");
    const lastSemesterAttended = document.getElementById("lastSemesterAttended");
    const documents = document.getElementById("documents");
    const notes = document.getElementById("notes");

    document.querySelectorAll(".viewDetails").forEach(button => {
        button.addEventListener("click", function (e) {
            e.preventDefault(); // Prevent scroll jump

            // Populate modal details
            requestID.textContent = button.dataset.requestId || '';
            firstName.textContent = button.dataset.requestFirstName || '';
            lastName.textContent = button.dataset.requestLastName || '';
            studentNumber.textContent = button.dataset.requestStudentNumber || '';
            section.textContent = button.dataset.requestSection || '';
            lastSchoolYear.textContent = button.dataset.requestLastSchoolYear || '';
            lastSemesterAttended.textContent = button.dataset.requestLastSemester || '';
            documents.textContent = button.dataset.requestDocuments || '';
            notes.textContent = button.dataset.requestNotes || '';

            // Clear previous attachments
            attachmentContainer.innerHTML = '';

            // Show attachments if available
            let attachments = [];
            try { attachments = JSON.parse(button.dataset.requestAttachments); } 
            catch (err) { attachments = []; }

            if (attachments.length > 0 && attachments[0] !== "") {
                attachments.forEach(file => {
                    const a = document.createElement("a");
                    a.href = file;
                    a.target = "_blank";
                    a.textContent = file;
                    a.style.display = "block";
                    attachmentContainer.appendChild(a);
                });
            } else {
                attachmentContainer.textContent = "No attachments.";
            }

            // Show modal
            modal.style.display = "block";
        });
    });

    // Close modal when clicking close button
    closeModal.addEventListener("click", () => modal.style.display = "none");

    // Close modal when clicking outside the modal content
    window.addEventListener("click", (e) => {
        if (e.target === modal) modal.style.display = "none";
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const notifBtn = document.getElementById("notifBtn");
    const notifDropdown = document.getElementById("notifDropdown");
    const notifCount = document.getElementById("notifCount");
    const notifList = document.getElementById("notifList");
    const audio = new Audio("assets/notif.mp3");

    const today = new Date().toISOString().slice(0, 10);

    // === DAILY RESET ===
    const lastDay = localStorage.getItem("notifLastDay");
    if (lastDay !== today) {
        localStorage.setItem("notifLastDay", today);
        localStorage.removeItem("seenNotifications");
        notifCount.textContent = "0";
        notifList.innerHTML = "";
    }

    let seenIds = new Set(JSON.parse(localStorage.getItem("seenNotifications") || "[]"));
    let fetchedData = [];
    let soundInterval = null;

    function fetchNotifications() {
        fetch("notifications.php")
            .then(r => r.json())
            .then(data => {
                if (!Array.isArray(data)) return;

                // 🔥 TODAY ONLY
                fetchedData = data.filter(
                    req => req.created_at && req.created_at.slice(0, 10) === today
                );

                // 🔥 COUNT = UNSEEN TODAY ONLY
                const unseenToday = fetchedData.filter(
                    req => !seenIds.has(req.id)
                );

                notifCount.textContent = unseenToday.length;

                if (unseenToday.length > 0) {
                    notifBtn.style.color = "#008c45";

                    if (notifDropdown.style.display !== "block" && !soundInterval) {
                        audio.currentTime = 0;
                        audio.play().catch(() => {});
                        soundInterval = setInterval(() => {
                            audio.currentTime = 0;
                            audio.play().catch(() => {});
                        }, 5000);
                    }
                }
            })
            .catch(() => {});
    }

    notifBtn.addEventListener("click", () => {
        const isOpen = notifDropdown.style.display === "block";
        notifDropdown.style.display = isOpen ? "none" : "block";

        if (!isOpen) {
            if (soundInterval) {
                clearInterval(soundInterval);
                soundInterval = null;
            }

            notifList.innerHTML = "";

            fetchedData.forEach(req => {
                const li = document.createElement("li");
                li.dataset.id = req.id;

                if (!seenIds.has(req.id)) li.classList.add("new-notif");

                li.innerHTML = `
                    <div class="notif-user">${req.first_name} ${req.last_name}</div>
                    <div class="notif-type-dept">
                        ${req.walk_in == 1 ? "Walk-In" : "Online"} - Dept: ${req.department}
                    </div>
                `;

                notifList.prepend(li);
                seenIds.add(req.id);
            });

            localStorage.setItem("seenNotifications", JSON.stringify([...seenIds]));
            notifCount.textContent = "0";
        }
    });

    setInterval(fetchNotifications, 2000);
    fetchNotifications();
});
