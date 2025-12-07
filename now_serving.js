document.addEventListener("DOMContentLoaded", () => {
  const container = document.querySelector(".container");
  const department = container.dataset.department || 0;
  const servingColumn = document.getElementById("serving-column");
  const queueingColumn = document.getElementById("queueing-column");
  const completedColumn = document.getElementById("completed-list");
  const completedPicker = document.getElementById("completed-date-picker");

  /* ================= FLASH MESSAGE ================= */
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

  /* ================= FETCH COMPLETED BY DATE ================= */
  // Set today's date as default
  if (completedPicker && !completedPicker.value) {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    completedPicker.value = `${year}-${month}-${day}`;
  }

  completedPicker.addEventListener("change", () => {
    refreshCompleted();
  });

  function refreshCompleted() {
    const department = container.dataset.department || 0;
    fetch("fetch_completed.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        completed_date: completedPicker.value || null,
        department: department,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success)
          renderList(completedColumn, data.requests, "completed");
      })
      .catch((err) => console.error("Error fetching completed:", err));
  }

  /* ================= BUTTON HANDLER ================= */
  container.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-serve, .btn-back, .btn-claim");
    if (!btn) return;

    const id = btn.dataset.id;
    const action = btn.classList.contains("btn-serve")
      ? "serve"
      : btn.classList.contains("btn-back")
        ? "back"
        : btn.classList.contains("btn-claim")
          ? "complete"
          : null;

    if (!action) return;

    updateStatus(id, action);
  });

  /* ================= UPDATE STATUS ================= */
  function updateStatus(id, action) {
    fetch("update_serving.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ request_id: id, action }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) {
          showFlashMessage(
            "Error: " + (data.message || "Unknown error"),
            "error"
          );
          return;
        }

        showFlashMessage(data.message, "success");
        refreshAll();
      })
      .catch((err) =>
        showFlashMessage("Server error while updating status.", "error")
      );
  }

  /* ================= REFRESH ALL COLUMNS ================= */
  window.refreshAll = function () {
    const department = container.dataset.department || 0;

    Promise.all([
      fetch(`fetch_queueing.php?department=${department}`).then((r) =>
        r.json()
      ),
      fetch(`fetch_serving.php?department=${department}`).then((r) => r.json()),
      fetch("fetch_completed.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          completed_date: completedPicker.value || null,
          department: department,
        }),
      }).then((r) => r.json()),
    ])
      .then(([queueingData, servingData, completedData]) => {
        if (queueingData.success)
          renderList(queueingColumn, queueingData.requests, "queueing");
        if (servingData.success)
          renderList(servingColumn, servingData.requests, "serving");
        if (completedData.success)
          renderList(completedColumn, completedData.requests, "completed");
      })
      .catch((err) => console.error("Error refreshing lists:", err));
  };

  /* ================= RENDER LIST ================= */
  function renderList(containerEl, list, type) {
    containerEl.innerHTML = "";
    if (!list || list.length === 0) {
      containerEl.innerHTML = `<p class="empty">No ${type} requests.</p>`;
      return;
    }

    list.forEach((req) => {
      const div = document.createElement("div");
      div.className = "card";
      div.id = `req-${req.id}`;

      div.innerHTML = `
        <span><strong>ID:</strong> ${req.id}</span>
        <span><strong>Name:</strong> ${req.first_name} ${req.last_name}</span>
        <span><strong>Documents:</strong> ${req.documents}</span>
        <span><strong>Notes:</strong> ${req.notes}</span>
        <span><strong>Status:</strong> ${req.status}</span>
        ${req.queueing_num
          ? `<span class="queue-number"><strong>Queue #:</strong> ${req.queueing_num}</span>`
          : ""
        }
        ${req.serving_position
          ? `<span class="position"><strong>Position:</strong> ${req.serving_position}</span>`
          : ""
        }
        <div class="actions">
          ${type === "queueing"
          ? `<button class="btn btn-serve" data-id="${req.id}">Serve</button>`
          : type === "serving"
            ? `<button class="btn btn-back" data-id="${req.id}">Back</button>
                 <button class="btn btn-claim" data-id="${req.id}">Complete</button>`
            : ""
        }
        </div>
      `;

      containerEl.appendChild(div);
    });
  }

  /* ================= INITIAL LOAD ================= */
  // Wait a moment to ensure DOM is ready, then do first refresh
  setTimeout(() => {
    refreshAll();
  }, 100);

  /* ================= AUTO REFRESH EVERY 5 SECONDS ================= */
  setInterval(() => {
    refreshAll();
  }, 5000); // refresh data every 5 seconds
});
