const tokenUp = localStorage.getItem("accessToken");
if (!tokenUp) window.location.href = "./../login.html";

const allowedCareersUp = ["Ingeniería en Sistemas", "Licenciatura en Informática"];

document.addEventListener("DOMContentLoaded", async () => {
  // Validación de acceso: solo student o admin y carrera permitida
  try {
    const me = await fetch("/api/auth/me.php", { headers:{Authorization:`Bearer ${tokenUp}`} }).then(r=>r.json());
    const role = me.user.role;
    const career = me.user.career || "";
    const canCreate = (role === "student" || role === "admin") && allowedCareersUp.includes(career);

    if (!canCreate){
      document.getElementById("blocked").classList.remove("d-none");
    } else {
      document.getElementById("formContainer").classList.remove("d-none");
      loadLicenses();
      bindSubmit();
    }
  } catch(e){
    window.location.href = "./../index.php";
  }

  document.getElementById("btnLogout").addEventListener("click", async () => {
    try{ await fetch("/api/auth/logout.php",{headers:{Authorization:`Bearer ${tokenUp}`}}) }catch{}
    localStorage.removeItem("accessToken");
    window.location.href = "./../index.php";
  });
});

async function loadLicenses(){
  try{
    const res = await fetch("/api/software/licenses/list.php");
    const data = await res.json();
    const sel = document.getElementById("licenseId");
    sel.innerHTML = `<option value="">Seleccionar...</option>`;
    (data || []).forEach(l=>{
      const opt = document.createElement("option");
      opt.value = l.id;
      opt.textContent = `${l.name} (${l.licenseKey})`;
      sel.appendChild(opt);
    });
  }catch{ document.getElementById("licenseId").innerHTML = `<option value="">Error cargando</option>`; }
}

function bindSubmit(){
  document.getElementById("formCreate").addEventListener("submit", async (e)=>{
    e.preventDefault();
    try{
      // 1) Crear proyecto
      const title = document.getElementById("title").value.trim();
      const description = document.getElementById("description").value.trim();
      const licenseId = document.getElementById("licenseId").value;
      const tags = document.getElementById("tags").value.split(",").map(s=>s.trim()).filter(Boolean);
      const readmeText = document.getElementById("readmeText").value;

      if (!title || !licenseId) return alert("Título y licencia son obligatorios.");

      const create = await fetch("/api/software/create.php", {
        method:"POST",
        headers:{ "Content-Type":"application/json", Authorization:`Bearer ${tokenUp}` },
        body: JSON.stringify({ title, description, licenseId, tags, readmeText })
      }).then(r=>r.json());

      if (!create.ok) throw new Error(create.error || "No se pudo crear el proyecto");

      // 2) Subir archivos (si hay)
      const files = document.getElementById("files").files;
      if (files.length){
        const fd = new FormData();
        fd.append("projectId", create.projectId);
        for (const f of files) fd.append("files[]", f);
        const up = await fetch("/api/software/uploadFiles.php", {
          method:"POST",
          headers:{ Authorization:`Bearer ${tokenUp}` },
          body: fd
        }).then(r=>r.json());
        if (!up.ok) throw new Error("Error subiendo archivos");
      }

      alert(" Proyecto registrado. Enviado para revisión.");
      window.location.href = `./softwareDetail.html?id=${create.projectId}`;
    }catch(err){
      alert("" + err.message);
    }
  });
}
