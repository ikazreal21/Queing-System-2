// Get the modal
var modal = document.getElementById("addStaffModal");

// Get the "Add Staff" container
var addStaffContainer = document.getElementById("addStaffContainer");

// Get the <span> element to close the modal
var closeBtn = document.querySelector(".close-btn");

// When the "Add Staff" container is clicked, open the modal
addStaffContainer.addEventListener("click", function() {
    modal.style.display = "block";
});

// When the user clicks on <span> (close button), close the modal
closeBtn.addEventListener("click", function() {
    modal.style.display = "none";
});

// When the user clicks outside the modal, close it
window.addEventListener("click", function(event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
});
document.addEventListener("DOMContentLoaded", () => {
    // Edit buttons
    document.querySelectorAll(".edit-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const row = this.closest("tr");
            const staffId = row.getAttribute("data-id");
            const firstName = row.children[0].innerText;
            const lastName = row.children[1].innerText;
            const email = row.children[2].innerText;
            const counterNo = row.children[3].innerText;
            const departments = row.children[4].innerText.split(", ");

            // Fill modal
            document.getElementById("editStaffId").value = staffId;
            document.getElementById("editFirstName").value = firstName;
            document.getElementById("editLastName").value = lastName;
            document.getElementById("editEmail").value = email;
            document.getElementById("editCounterNo").value = counterNo;

            // Set departments
            const deptSelect = document.getElementById("editDepartments");
            for (let option of deptSelect.options) {
                option.selected = departments.includes(option.text);
            }

            document.getElementById("editStaffModal").style.display = "block";
        });
    });

    // Delete buttons
    document.querySelectorAll(".remove-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const row = this.closest("tr");
            const staffId = row.getAttribute("data-id");

            document.getElementById("deleteStaffId").value = staffId;
            document.getElementById("deleteStaffModal").style.display = "block";
        });
    });

    // Close modals
    document.querySelectorAll(".close-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            this.closest(".modal").style.display = "none";
        });
    });
});
