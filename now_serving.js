document.addEventListener("DOMContentLoaded", () => {
  const container = document.querySelector(".container");
  const department = container.dataset.department || 0;

  const queueingColumn = document.getElementById("queueing-column");
  const servingColumn = document.getElementById("serving-column");
  const completedColumn = document.getElementById("completed-list");
  const completedPicker = document.getElementById("completed-date-picker");

  // ================= FLASH MESSAGE =================
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

  // ================= FETCH COMPLETED =================
  completedPicker.addEventListener("change", refreshCompleted);

  function refreshCompleted() {
    fetch("fetch_completed.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        completed_date: completedPicker.value || null,
        department: department,
      }),
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) renderList(completedColumn, data.requests, "completed");
      })
      .catch(err => console.error("Error fetching completed:", err));
  }

  // ================= BUTTON HANDLER =================
  container.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-back, .btn-claim");
    if (!btn) return;

    const id = btn.dataset.id;
    const action = btn.classList.contains("btn-back")
      ? "back"
      : btn.classList.contains("btn-claim")
      ? "complete"
      : null;

    if (!action) return;
    updateStatus(id, action);
  });

  // ================= UPDATE STATUS =================
  function updateStatus(id, action) {
    fetch("update_serving.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ request_id: id, action }),
    })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          showFlashMessage("Error: " + (data.message || "Unknown error"), "error");
          return;
        }
        showFlashMessage(data.message, "success");
        refreshServing();
        refreshCompleted();
      })
      .catch(() => showFlashMessage("Server error while updating status.", "error"));
  }

  // ================= REFRESH SERVING =================
  function refreshServing() {
    fetch(`fetch_serving.php?department=${department}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          renderServingList(data.requests);
          autoServeFirst(data.requests);
        }
      })
      .catch(err => console.error("Error fetching serving:", err));
  }

  // ================= AUTO SERVE FIRST =================
  function autoServeFirst(servingList) {
    if (!servingList || servingList.length === 0) {
      fetch(`fetch_queueing.php?department=${department}`)
        .then(res => res.json())
        .then(data => {
          if (data.success && data.requests.length > 0) {
            const firstReq = data.requests.reduce((prev, curr) => {
              return (prev.queueing_num || Infinity) < (curr.queueing_num || Infinity)
                ? prev
                : curr;
            });
            updateStatus(firstReq.id, "serve");
          }
        })
        .catch(err => console.error("Error fetching queueing for auto-serve:", err));
    }
  }

  // ================= RENDER LISTS =================
  function renderList(containerEl, list, type) {
    containerEl.innerHTML = "";
    if (!list || list.length === 0) {
      containerEl.innerHTML = `<p class="empty">No ${type} requests.</p>`;
      return;
    }

    list.forEach(req => {
      const div = document.createElement("div");
      div.className = "card";
      div.id = `req-${req.id}`;

      div.innerHTML = `
        <span><strong>ID:</strong> ${req.id}</span>
        <span><strong>Name:</strong> ${req.first_name} ${req.last_name}</span>
        <span><strong>Documents:</strong> ${req.documents}</span>
        <span><strong>Notes:</strong> ${req.notes}</span>
        <span><strong>Status:</strong> ${req.status || "Serving"}</span>
        <span><strong>Queue #:</strong> ${req.queueing_num ?? "-"}</span>
        <div class="actions">
          ${type === "serving" 
            ? `<button class="btn btn-back" data-id="${req.id}">Back</button>
               <button class="btn btn-claim" data-id="${req.id}">Complete</button>`
            : ""
          }
        </div>
      `;

      containerEl.appendChild(div);
    });
  }

  // ================= RENDER SERVING =================
  function renderServingList(list) {
    servingColumn.innerHTML = "<h2>Serving</h2>";

    if (!list || list.length === 0) {
      servingColumn.innerHTML += '<p class="empty">No serving requests.</p>';
      return;
    }

    list.forEach(req => {
      const div = document.createElement("div");
      div.className = "card";
      div.id = `req-${req.id}`;

      div.innerHTML = `
        <span><strong>ID:</strong> ${req.id}</span>
        <span><strong>Name:</strong> ${req.first_name} ${req.last_name}</span>
        <span><strong>Documents:</strong> ${req.documents}</span>
        <span><strong>Notes:</strong> ${req.notes}</span>
        <span><strong>Status:</strong> ${req.status || "Serving"}</span>
        <span><strong>Queue #:</strong> ${req.queueing_num ?? "-"}</span>
        <div class="actions">
          <button class="btn btn-back" data-id="${req.id}">Back</button>
          <button class="btn btn-claim" data-id="${req.id}">Complete</button>
        </div>
      `;

      servingColumn.appendChild(div);
    });
  }

  // ================= INITIAL LOAD =================
  refreshServing();
  refreshCompleted();

  // Refresh every 1.5s
  setInterval(() => {
    refreshServing();
    refreshCompleted();
  }, 1500);
});
