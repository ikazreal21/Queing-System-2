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
                    a.href = "uploads/" + file;
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

    // === DAILY RESET ===
    const today = new Date().toISOString().split("T")[0];
    const lastDay = localStorage.getItem("notifLastDay");

    if (lastDay !== today) {
        localStorage.removeItem("seenNotifications");
        localStorage.setItem("notifLastDay", today);
        notifList.innerHTML = "";
        notifCount.textContent = "0";
    }

    // === LOAD SEEN NOTIFICATION IDS ===
    const knownRequestIds = new Set(
        JSON.parse(localStorage.getItem("seenNotifications") || "[]")
    );

    const countedRequestIds = new Set(); // counted this session only
    let newNotifications = 0;
    let soundInterval = null;
    let fetchedData = [];

    // === FETCH NOTIFICATIONS ===
    function fetchNotifications() {
        fetch("notifications.php")
            .then(res => res.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) return;

                let newRequestFound = false;

                data.forEach(req => {
                    // store fetched requests
                    if (!fetchedData.some(d => d.id === req.id)) {
                        fetchedData.push(req);
                    }

                    // count only unseen + not yet counted this session
                    if (
                        !knownRequestIds.has(req.id) &&
                        !countedRequestIds.has(req.id)
                    ) {
                        newNotifications++;
                        notifCount.textContent = newNotifications;
                        notifBtn.style.color = "#008c45"; // green bell
                        countedRequestIds.add(req.id);
                        newRequestFound = true;
                    }
                });

                // play sound only if dropdown is closed
                if (
                    newRequestFound &&
                    notifDropdown.style.display !== "block" &&
                    !soundInterval
                ) {
                    audio.currentTime = 0;
                    audio.play().catch(() => {});

                    soundInterval = setInterval(() => {
                        audio.currentTime = 0;
                        audio.play().catch(() => {});
                    }, 5000);
                }
            })
            .catch(err => console.error(err));
    }

    // === DROPDOWN TOGGLE ===
    notifBtn.addEventListener("click", () => {
        const isOpen = notifDropdown.style.display === "block";
        notifDropdown.style.display = isOpen ? "none" : "block";

        if (!isOpen) {
            // stop sound
            if (soundInterval) {
                clearInterval(soundInterval);
                soundInterval = null;
            }

            notifList.innerHTML = "";

            fetchedData.forEach(req => {
                const li = document.createElement("li");
                li.dataset.id = req.id;

                if (!knownRequestIds.has(req.id)) {
                    li.classList.add("new-notif");
                }

                const type = req.walk_in == 1 ? "Walk-In" : "Online";

                li.innerHTML = `
                    <div class="notif-user">${req.first_name} ${req.last_name}</div>
                    <div class="notif-type-dept">
                        ${type} - Dept: ${req.department}
                    </div>
                `;

                notifList.prepend(li);
                knownRequestIds.add(req.id);
            });

            // persist seen notifications
            localStorage.setItem(
                "seenNotifications",
                JSON.stringify([...knownRequestIds])
            );

            // reset counters
            newNotifications = 0;
            notifCount.textContent = "0";
            countedRequestIds.clear();
        }
    });

    // === POLLING ===
    setInterval(fetchNotifications, 2000);
    fetchNotifications();
});