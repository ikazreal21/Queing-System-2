// Get the modal
var modal = document.getElementById("detailsModal");

// Get all the "View Details" buttons
var buttons = document.querySelectorAll(".view-details-btn");

// Get the <span> element to close the modal
var closeBtn = document.querySelector(".close-btn");

// Add event listener to each button to open the modal
buttons.forEach(function(button) {
    button.addEventListener("click", function() {
        modal.style.display = "block";
    });
});

// Add event listener to the close button to close the modal
closeBtn.addEventListener("click", function() {
    modal.style.display = "none";
});

// Add event listener to close the modal if the user clicks outside the modal content
window.addEventListener("click", function(event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
});// Get the modal element
var modal = document.getElementById("detailsModal");

// Get all the "View Details" buttons
var buttons = document.querySelectorAll(".view-details-btn");

// Get the <span> element to close the modal
var closeBtn = document.querySelector(".close-btn");

// Function to populate modal fields from button data attributes
function populateModal(button) {
    document.getElementById("modalRequestId").textContent = button.getAttribute("data-request-id");
    document.getElementById("modalName").textContent = button.getAttribute("data-name");
    document.getElementById("modalStudentNumber").textContent = button.getAttribute("data-student-number");
    document.getElementById("modalSection").textContent = button.getAttribute("data-section");
    document.getElementById("modalSchoolYear").textContent = button.getAttribute("data-school-year");
    document.getElementById("modalSemester").textContent = button.getAttribute("data-semester");
    document.getElementById("modalDocuments").textContent = button.getAttribute("data-documents");
    document.getElementById("modalNotes").textContent = button.getAttribute("data-notes");
    document.getElementById("modalStatus").textContent = button.getAttribute("data-status");
    document.getElementById("modalReasons").textContent = button.getAttribute("data-reasons");
    document.getElementById("modalSubmitted").textContent = button.getAttribute("data-submitted");
}

// Add click event to each button
buttons.forEach(function(button) {
    button.addEventListener("click", function() {
        populateModal(button);
        modal.style.display = "block";
    });
});

// Close modal when 'x' is clicked
closeBtn.addEventListener("click", function() {
    modal.style.display = "none";
});

// Close modal when clicking outside the modal content
window.addEventListener("click", function(event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
});
