/* =========== SAFE STAFF DASHBOARD JS =========== */

document.addEventListener("DOMContentLoaded", function () {

    /* ====== NAVIGATION BAR ====== */
    const navMenu = document.getElementById('navMenu');
    const toggleBtn = document.getElementById('toggleBtn');

    function myMenuFunction() {
        if (!navMenu || !toggleBtn) return;
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

    if (toggleBtn) toggleBtn.addEventListener('click', myMenuFunction);
    document.querySelectorAll('.nav-link').forEach(link => link.addEventListener('click', closeMenu));

    /* ====== HEADER SHADOW ON SCROLL ====== */
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

    window.addEventListener('scroll', headerShadow);
    window.addEventListener('load', headerShadow);

    /* ====== VIEW DETAILS MODAL ====== */
    const modal = document.getElementById("detailsModal");
    if (modal) {
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
                e.preventDefault();

                if (requestID) requestID.textContent = button.dataset.requestId || '';
                if (firstName) firstName.textContent = button.dataset.requestFirstName || '';
                if (lastName) lastName.textContent = button.dataset.requestLastName || '';
                if (studentNumber) studentNumber.textContent = button.dataset.requestStudentNumber || '';
                if (section) section.textContent = button.dataset.requestSection || '';
                if (lastSchoolYear) lastSchoolYear.textContent = button.dataset.requestLastSchoolYear || '';
                if (lastSemesterAttended) lastSemesterAttended.textContent = button.dataset.requestLastSemester || '';
                if (documents) documents.textContent = button.dataset.requestDocuments || '';
                if (notes) notes.textContent = button.dataset.requestNotes || '';

                if (attachmentContainer) {
                    attachmentContainer.innerHTML = '';
                    let attachments = [];
                    try { attachments = JSON.parse(button.dataset.requestAttachments); } catch { attachments = []; }

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
                }

                modal.style.display = "block";
            });
        });

        if (closeModal) closeModal.addEventListener("click", () => modal.style.display = "none");
        window.addEventListener("click", e => { if (e.target === modal) modal.style.display = "none"; });
    }

    /* ====== NOTIFICATIONS ====== */
    const notifBtn = document.getElementById("notifBtn");
    const notifDropdown = document.getElementById("notifDropdown");
    const notifCount = document.getElementById("notifCount");
    const notifList = document.getElementById("notifList");
    const audio = new Audio("assets/notif.mp3");

    if (notifBtn && notifDropdown && notifCount && notifList) {
        const today = new Date().toISOString().split("T")[0];
        const lastDay = localStorage.getItem("notifLastDay");

        if (lastDay !== today) {
            localStorage.removeItem("seenNotifications");
            localStorage.setItem("notifLastDay", today);
            notifList.innerHTML = "";
            notifCount.textContent = "0";
        }

        const knownRequestIds = new Set(JSON.parse(localStorage.getItem("seenNotifications") || "[]"));
        const countedRequestIds = new Set();
        let newNotifications = 0;
        let soundInterval = null;
        let fetchedData = [];

        function fetchNotifications() {
            fetch("notifications.php")
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data) || data.length === 0) return;

                    let newRequestFound = false;

                    data.forEach(req => {
                        if (!fetchedData.some(d => d.id === req.id)) fetchedData.push(req);

                        if (!knownRequestIds.has(req.id) && !countedRequestIds.has(req.id)) {
                            newNotifications++;
                            notifCount.textContent = newNotifications;
                            notifBtn.style.color = "#008c45";
                            countedRequestIds.add(req.id);
                            newRequestFound = true;
                        }
                    });

                    if (newRequestFound && notifDropdown.style.display !== "block" && !soundInterval) {
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

        notifBtn.addEventListener("click", () => {
            const isOpen = notifDropdown.style.display === "block";
            notifDropdown.style.display = isOpen ? "none" : "block";

            if (!isOpen) {
                if (soundInterval) { clearInterval(soundInterval); soundInterval = null; }
                notifList.innerHTML = "";

                fetchedData.forEach(req => {
                    const li = document.createElement("li");
                    li.dataset.id = req.id;
                    if (!knownRequestIds.has(req.id)) li.classList.add("new-notif");

                    const type = req.walk_in == 1 ? "Walk-In" : "Online";
                    li.innerHTML = `<div class="notif-user">${req.first_name} ${req.last_name}</div>
                                    <div class="notif-type-dept">${type} - Dept: ${req.department}</div>`;
                    notifList.prepend(li);
                    knownRequestIds.add(req.id);
                });

                localStorage.setItem("seenNotifications", JSON.stringify([...knownRequestIds]));
                newNotifications = 0;
                notifCount.textContent = "0";
                countedRequestIds.clear();
            }
        });

        setInterval(fetchNotifications, 2000);
        fetchNotifications();
    }

});
