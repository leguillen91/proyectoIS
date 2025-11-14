// public/assets/students.js

const API_STUDENTS = "/public/api/students";
const API_AUTH = "/public/api/auth/";
const token = localStorage.getItem("accessToken");

// Redirige si no hay sesión
if (!token) {
  window.location.href = "./login.html";
}

// ===== Utilidades =====
const fetchJSON = async (url, options = {}) => {
  const res = await fetch(url, {
    ...options,
    headers: {
      Authorization: `Bearer ${token}`,
      "Content-Type": options.body ? "application/json" : undefined,
      ...(options.headers || {}),
    },
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || "Error en la solicitud");
  return data;
};

const qs = (sel) => document.querySelector(sel);

// ===== Carga inicial =====
document.addEventListener("DOMContentLoaded", () => {
  // Cargar tabla de estudiantes si está en la vista
  if (qs("#studentsTable")) loadStudents();

  // Guardar (crear/actualizar) estudiante
  const btnSave = qs("#btnSaveStudent");
  if (btnSave) {
    btnSave.addEventListener("click", onSaveStudent);
  }

  // Logout
  const btnLogout = qs("#btnLogout");
  if (btnLogout) {
    btnLogout.addEventListener("click", async () => {
      try {
        await fetchJSON(`${API_AUTH}/logout.php`);
      } catch (_) {}
      localStorage.removeItem("accessToken");
      window.location.href = "./login.html";
    });
  }
});

// ===== Listado =====
async function loadStudents() {
  try {
    const data = await fetchJSON(`${API_STUDENTS}/list.php`);
    const tbody = qs("#studentsTable tbody");
    tbody.innerHTML = "";

    (data.students || []).forEach((s) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${s.studentId}</td>
        <td>${escapeHtml(s.fullName)}</td>
        <td>${escapeHtml(s.email)}</td>
        <td>${escapeHtml(s.career || "")}</td>
        <td>${escapeHtml(s.academicCenter || "")}</td>
        <td>${escapeHtml(s.currentPeriod || "")}</td>
        <td class="d-flex gap-2">
          <button class="btn btn-sm btn-info" onclick="editStudent(${s.studentId})">✏️</button>
          <button class="btn btn-sm btn-danger" onclick="deleteStudent(${s.studentId})">🗑️</button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  } catch (err) {
    alert("error " + err.message);
  }
}

// ===== Crear / Actualizar =====
async function onSaveStudent() {
  const form = qs("#studentForm");
  const studentId = qs("#studentId").value.trim();

  const payload = {
    enrollmentCode: qs("#enrollmentCode").value.trim(),
    career: qs("#career").value.trim(),
    academicCenter: qs("#academicCenter").value.trim(),
    admissionYear: parseInt(qs("#admissionYear").value, 10),
    currentPeriod: qs("#currentPeriod").value.trim(),
    status: qs("#status").value,
    phoneNumber: qs("#phoneNumber").value.trim() || null,
    address: qs("#address").value.trim() || null,
  };

  // Validación mínima
  const required = ["career", "academicCenter", "admissionYear"];
  for (const f of required) {
    if (!payload[f]) {
      alert(` Falta el campo: ${f}`);
      return;
    }
  }

  try {
    if (studentId) {
      // Update
      const res = await fetchJSON(`${API_STUDENTS}/update.php?id=${encodeURIComponent(studentId)}`, {
        method: "PUT",
        body: JSON.stringify(payload),
      });
      if (!res.ok && !res.message) throw new Error("No se pudo actualizar");
      alert(" Estudiante actualizado");
    } else {
      // Create requiere además userId
      const userId = prompt("Ingrese el userId del estudiante (users.id con rol 'student'):");
      if (!userId) return;

      const createPayload = { userId: parseInt(userId, 10), ...payload };
      const res = await fetchJSON(`${API_STUDENTS}/create.php`, {
        method: "POST",
        body: JSON.stringify(createPayload),
      });
      if (!res.ok && !res.studentId) throw new Error("No se pudo crear");
      alert(`✅ Estudiante creado (ID: ${res.studentId})`);
    }

    // Cerrar modal y refrescar
    form.reset();
    const modalEl = qs("#modalStudent");
    if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
    loadStudents();
  } catch (err) {
    alert("error " + err.message);
  }
}

// ===== Editar =====
async function editStudent(id) {
  try {
    const data = await fetchJSON(`${API_STUDENTS}/get.php?id=${encodeURIComponent(id)}`);
    const s = data.student;

    qs("#studentId").value = s.studentId;
    qs("#enrollmentCode").value = s.enrollmentCode || "";
    qs("#career").value = s.career || "";
    qs("#academicCenter").value = s.academicCenter || "";
    qs("#admissionYear").value = s.admissionYear || new Date().getFullYear();
    qs("#currentPeriod").value = s.currentPeriod || "";
    qs("#status").value = s.status || "Activo";
    qs("#phoneNumber").value = s.phoneNumber || "";
    qs("#address").value = s.address || "";

    new bootstrap.Modal(qs("#modalStudent")).show();
  } catch (err) {
    alert("error " + err.message);
  }
}

// ===== Eliminar =====
async function deleteStudent(id) {
  if (!confirm("¿Eliminar este estudiante?")) return;
  try {
    const res = await fetchJSON(`${API_STUDENTS}/delete.php?id=${encodeURIComponent(id)}`, {
      method: "DELETE",
    });
    if (!res.ok && !res.message) throw new Error("No se pudo eliminar");
    alert("🗑️ Estudiante eliminado");
    loadStudents();
  } catch (err) {
    alert("error " + err.message);
  }
}

// ===== Escape XSS mínimo en celdas =====
function escapeHtml(str) {
  return String(str ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

// Exponer funciones para botones inline
window.editStudent = editStudent;
window.deleteStudent = deleteStudent;
