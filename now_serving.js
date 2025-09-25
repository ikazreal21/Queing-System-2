document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(".container");

    // Date picker for Completed requests
    const completedPicker = document.getElementById("completed-date-picker");
    completedPicker.addEventListener("change", () => {
        const selectedDate = completedPicker.value;
        if (!selectedDate) return;

        fetch("fetch_completed.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ completed_date: selectedDate })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return alert("Failed to fetch completed: " + (data.message || ""));
            const completedList = document.getElementById("completed-list");
            completedList.innerHTML = "";

            data.requests.forEach(req => {
                const card = document.createElement("div");
                card.classList.add("card");
                card.id = `req-${req.id}`;
                card.innerHTML = `
                    <span><strong>ID:</strong> <span class="value">${req.id}</span></span>
                    <span><strong>Name:</strong> <span class="value">${req.first_name} ${req.last_name}</span></span>
                    <span><strong>Documents:</strong> <span class="value">${req.documents}</span></span>
                    <span><strong>Notes:</strong> <span class="value">${req.notes}</span></span>
                    <span><strong>Status:</strong> <span class="value">${req.status}</span></span>
                    <span>Claimed / Completed</span>
                `;
                completedList.appendChild(card);
            });
        })
        .catch(err => console.error("Error fetching completed:", err));
    });

    // Event delegation for Serve / Back / Claim buttons
    container.addEventListener("click", e => {
        const btn = e.target.closest(".btn-serve, .btn-back, .btn-claim");
        if (!btn) return;

        e.stopPropagation();
        const id = btn.dataset.id;
        let action = "";

        if (btn.classList.contains("btn-serve")) action = "serve";
        else if (btn.classList.contains("btn-back")) action = "back";
        else if (btn.classList.contains("btn-claim")) action = "complete";
        if (!action) return;

        const confirmMsg = {
            serve: "Move this student to Serving?",
            back: "Send this student back to Queueing?",
            complete: "Mark this student as Completed?"
        };

        let queueNum = null;
        if (action === "serve") {
            queueNum = prompt("Enter queue number for this request:");
            if (!queueNum) return alert("Queue number is required!");
        }

        if (!confirm(confirmMsg[action])) return;

        updateStatus(id, action, queueNum);
    });

    function updateStatus(id, action, queueNum = null) {
        fetch("update_serving.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ request_id: id, action: action, queueing_num: queueNum })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return alert("Failed: " + (data.message || ""));
            const card = document.getElementById("req-" + id);
            if (!card) return;

            // Remove any previous queue number or position
            const queueSpan = card.querySelector(".queue-number");
            if (queueSpan) queueSpan.remove();
            const posSpan = card.querySelector(".position");
            if (posSpan) posSpan.remove();

            if (action === "serve") {
                const span = document.createElement("span");
                span.classList.add("queue-number");
                span.innerHTML = `<strong>Queue #:</strong> ${queueNum}`;
                card.appendChild(span);
                setServingCard(card);
                document.getElementById("serving-column").appendChild(card);
            } else if (action === "back") {
                setQueueingCard(card);
                document.getElementById("queueing-column").appendChild(card);
            } else if (action === "complete") {
                const actions = card.querySelector(".actions");
                if (actions) actions.remove();
                if (!card.querySelector(".completed-label")) {
                    const label = document.createElement("span");
                    label.classList.add("completed-label");
                    label.textContent = " Claimed / Completed";
                    card.appendChild(label);
                }
                document.getElementById("completed-list").appendChild(card);
            }
            updateServingPositions();
        })
        .catch(err => console.error("Error updating status:", err));
    }

    function setServingCard(card) {
        let actions = card.querySelector(".actions");
        if (!actions) {
            actions = document.createElement("div");
            actions.classList.add("actions");
            card.appendChild(actions);
        }
        actions.innerHTML = `
            <button class="btn-back" data-id="${card.id.replace("req-", "")}">Back</button>
            <button class="btn-claim" data-id="${card.id.replace("req-", "")}">Claim</button>
        `;
    }

    function setQueueingCard(card) {
        let actions = card.querySelector(".actions");
        if (!actions) {
            actions = document.createElement("div");
            actions.classList.add("actions");
            card.appendChild(actions);
        }
        actions.innerHTML = `<button class="btn-serve" data-id="${card.id.replace("req-", "")}">Serve</button>`;
    }

    function updateServingPositions() {
        const servingCards = document.querySelectorAll("#serving-column .card");
        servingCards.forEach((card, index) => {
            let pos = card.querySelector(".position");
            if (!pos) {
                pos = document.createElement("span");
                pos.classList.add("position");
                card.appendChild(pos);
            }
            pos.textContent = `Position: ${index + 1}`;
        });
    }

    // Initial positions on page load
    updateServingPositions();
});
