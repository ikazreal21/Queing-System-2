document.addEventListener("DOMContentLoaded", () => {

    // ================= MODAL =================
    const modal = document.querySelector(".archive-modal");
    const modalContent = document.querySelector(".archive-modal-content");
    const closeModalBtn = document.querySelectorAll(".archive-modal .close-btn");

    // Open modal when clicking a view button
    const viewButtons = document.querySelectorAll(".archive-view-btn");
    viewButtons.forEach(button => {
        button.addEventListener("click", () => {
            const recordId = button.dataset.id; // if you pass an ID
            openModal(recordId);
        });
    });

    function openModal(recordId) {
        // You can fetch data using recordId if needed
        modal.style.display = "block";
    }

    // Close modal on clicking close button
    closeModalBtn.forEach(btn => {
        btn.addEventListener("click", () => {
            modal.style.display = "none";
        });
    });

    // Close modal on clicking outside content
    window.addEventListener("click", e => {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });

    // ================= REPORT BUTTON =================
    const reportButtons = document.querySelectorAll(".archive-report-btn");
    reportButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            alert("Generating report..."); // replace with your report logic
            // Example: open a new window with PDF report
            // window.open(`generate_report.php?id=${btn.dataset.id}`, "_blank");
        });
    });

    // ================= SMOOTH TABLE SCROLL (optional) =================
    const tableScrollContainers = document.querySelectorAll(".archive-table-scroll");
    tableScrollContainers.forEach(container => {
        container.style.scrollBehavior = "smooth";
    });

});
