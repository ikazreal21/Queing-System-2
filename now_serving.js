document.addEventListener("DOMContentLoaded", function () {
  var container = document.querySelector(".container");
  if (!container) return;

  var department = container.dataset.department || 0;
  var servingColumn = document.getElementById("serving-column");
  var queueingColumn = document.getElementById("queueing-column");
  var completedColumn = document.getElementById("completed-list");
  var completedPicker = document.getElementById("completed-date-picker");

  /* ================= FLASH MESSAGE ================= */
  function showFlashMessage(message, type) {
    type = type || "success"; // default type
    var flash = document.createElement("div");
    flash.className = "flash-message " + type; // ✅ no backticks
    flash.textContent = message;
    document.body.appendChild(flash);

    setTimeout(function () { flash.classList.add("show"); }, 10);
    setTimeout(function () {
      flash.classList.remove("show");
      setTimeout(function () { flash.remove(); }, 300);
    }, 3000);
  }

  /* ================= FETCH COMPLETED ================= */
  if (completedPicker) {
    completedPicker.addEventListener("change", function () {
      refreshCompleted();
    });
  }

  function refreshCompleted() {
    fetch("fetch_completed.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        completed_date: completedPicker.value || null,
        department: department
      })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success) renderList(completedColumn, data.requests, "completed");
      })
      .catch(function (err) { console.error("Error fetching completed:", err); });
  }

  /* ================= BUTTON HANDLER ================= */
  container.addEventListener("click", function (e) {
    var btn = e.target.closest(".btn-serve, .btn-back, .btn-claim");
    if (!btn) return;

    var id = btn.dataset.id;
    var action = null;
    if (btn.classList.contains("btn-serve")) action = "serve";
    else if (btn.classList.contains("btn-back")) action = "back";
    else if (btn.classList.contains("btn-claim")) action = "complete";

    if (!action) return;

    updateStatus(id, action);
  });

  /* ================= UPDATE STATUS ================= */
  function updateStatus(id, action) {
    fetch("update_serving.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ request_id: id, action: action })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.success) {
          showFlashMessage("Error: " + (data.message || "Unknown error"), "error");
          return;
        }
        showFlashMessage(data.message, "success");
        refreshAll();
      })
      .catch(function () {
        showFlashMessage("Server error while updating status.", "error");
      });
  }

  /* ================= REFRESH ALL ================= */
  window.refreshAll = function () {
    fetchAll();
  };

  function fetchAll() {
    Promise.all([
      fetch("fetch_queueing.php?department=" + department).then(function (r) { return r.json(); }),
      fetch("fetch_serving.php?department=" + department).then(function (r) { return r.json(); }),
      fetch("fetch_completed.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          completed_date: completedPicker ? completedPicker.value : null,
          department: department
        })
      }).then(function (r) { return r.json(); })
    ])
      .then(function (results) {
        var queueingData = results[0];
        var servingData = results[1];
        var completedData = results[2];

        if (queueingData.success) renderList(queueingColumn, queueingData.requests, "queueing");
        if (servingData.success) renderList(servingColumn, servingData.requests, "serving");
        if (completedData.success) renderList(completedColumn, completedData.requests, "completed");
      })
      .catch(function (err) { console.error("Error refreshing lists:", err); });
  }

  /* ================= RENDER LIST ================= */
  function renderList(containerEl, list, type) {
    if (!containerEl) return;
    containerEl.innerHTML = "";
    if (!list || list.length === 0) {
      containerEl.innerHTML = "<p class='empty'>No " + type + " requests.</p>";
      return;
    }

    list.forEach(function (req) {
      var div = document.createElement("div");
      div.className = "card";
      div.id = "req-" + req.id;

      var actionsHtml = "";
      if (type === "queueing") {
        actionsHtml = "<button class='btn btn-serve' data-id='" + req.id + "'>Serve</button>";
      } else if (type === "serving") {
        actionsHtml =
          "<button class='btn btn-back' data-id='" + req.id + "'>Back</button>" +
          "<button class='btn btn-claim' data-id='" + req.id + "'>Complete</button>";
      }

      div.innerHTML =
        "<span><strong>ID:</strong> " + req.id + "</span>" +
        "<span><strong>Name:</strong> " + req.first_name + " " + req.last_name + "</span>" +
        "<span><strong>Documents:</strong> " + (req.documents || "") + "</span>" +
        "<span><strong>Notes:</strong> " + (req.notes || "") + "</span>" +
        "<span><strong>Status:</strong> " + req.status + "</span>" +
        (req.queueing_num ? "<span class='queue-number'><strong>Queue #:</strong> " + req.queueing_num + "</span>" : "") +
        (req.serving_position ? "<span class='position'><strong>Position:</strong> " + req.serving_position + "</span>" : "") +
        "<div class='actions'>" + actionsHtml + "</div>";

      containerEl.appendChild(div);
    });
  }

  // Initial load
  fetchAll();
});
