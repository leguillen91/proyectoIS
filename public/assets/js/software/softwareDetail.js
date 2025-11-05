const tokenDet = localStorage.getItem("accessToken");
if (!tokenDet) window.location.href = "./../login.html";

const allowedCareersDet = ["Ingeniería en Sistemas", "Licenciatura en Informática"];
const query = new URLSearchParams(location.search);
const projectId = parseInt(query.get("id")||"0",10);

document.addEventListener("DOMContentLoaded", init);

async function init(){
  if (!projectId) { location.href="./software.html"; return; }

  try{
    const me = await fetch("/api/auth/me.php", { headers:{Authorization:`Bearer ${tokenDet}`} }).then(r=>r.json());
    const role = me.user.role; const career = me.user.career || "";
    const canEnter = ["student","teacher","coordinator","deptHead","admin"].includes(role) && allowedCareersDet.includes(career);
    if (!canEnter){ document.getElementById("blocked").classList.remove("d-none"); return; }

    // Cargar detalle
    const detail = await fetch(`/api/software/detail.php?id=${projectId}`).then(r=>r.json());
    renderDetail(detail);

    // Acciones de moderación solo para coordinator/deptHead/admin
    if (["coordinator","deptHead","admin"].includes(role)){
      const box = document.getElementById("moderationActions");
      box.classList.remove("d-none");
      box.addEventListener("click", onModerateClick);
    }

  }catch(e){
    console.error(e); window.location.href="./../index.php";
  }

  document.getElementById("btnLogout").addEventListener("click", async ()=>{
    try{ await fetch("/api/auth/logout.php", {headers:{Authorization:`Bearer ${tokenDet}`}}) }catch{}
    localStorage.removeItem("accessToken"); window.location.href="./../index.php";
  });
}

function renderDetail(p){
  document.getElementById("detailContainer").classList.remove("d-none");
  document.getElementById("title").textContent = p.title || "Proyecto";
  const lic = document.getElementById("licenseLink");
  lic.textContent = p.licenseName || "-";
  lic.href = p.licenseUrl || "#";
  document.getElementById("status").textContent = p.status || "-";
  document.getElementById("readme").innerHTML = renderReadme(p.readmeText || "—");

  // Autores (si quieres, amplía el detail.php para retornar lista)
  document.getElementById("authors").textContent = p.authors?.join(", ") || "Autor principal";

  // Archivos (si quieres, extiende detail.php para incluirlos)
    const filesList = document.getElementById("filesList");
      filesList.innerHTML = "";

      (p.files || []).forEach(f => {
        const li = document.createElement("li");
        li.className = "list-group-item d-flex justify-content-between align-items-center";

        const fileSize = (f.fileSize / 1024).toFixed(1) + " KB";
        const safeName = f.fileName.replace(/[<>]/g, "");

        li.innerHTML = `
          <span><i class="bi bi-file-earmark"></i> ${safeName}</span>
          <a class="btn btn-sm btn-outline-primary" href="/api/software/download.php?path=${encodeURIComponent(f.filePath)}" target="_blank">
            <i class="bi bi-download"></i> Descargar
          </a>
        `;
        filesList.appendChild(li);
      });

}

function renderReadme(txt){
  // Render mínimo de Markdown (títulos, bold, listas, links)
  let html = txt
    .replace(/^###### (.*$)/gim, '<h6>$1</h6>')
    .replace(/^##### (.*$)/gim, '<h5>$1</h5>')
    .replace(/^#### (.*$)/gim, '<h4>$1</h4>')
    .replace(/^### (.*$)/gim, '<h3>$1</h3>')
    .replace(/^## (.*$)/gim, '<h2>$1</h2>')
    .replace(/^# (.*$)/gim, '<h1>$1</h1>')
    .replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/gim, '<em>$1</em>')
    .replace(/^- (.*$)/gim, '<li>$1</li>')
    .replace(/\[(.*?)\]\((.*?)\)/gim, '<a href="$2" target="_blank">$1</a>');
  html = html.replace(/(<li>.*<\/li>)/gims, '<ul>$1</ul>');
  return html;
}

function escapeHtml(s){ return s?.replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;") || ""; }

async function onModerateClick(e){
  const btn = e.target.closest("button[data-s]"); if(!btn) return;
  const newStatus = btn.getAttribute("data-s");
  const note = prompt(`Nota para el cambio a "${newStatus}":`, "");
  try{
    const res = await fetch("/api/software/changeStatus.php", {
      method:"POST",
      headers:{ "Content-Type":"application/json", Authorization:`Bearer ${tokenDet}` },
      body: JSON.stringify({ projectId, newStatus, note })
    }).then(r=>r.json());
    if (!res.ok) throw new Error(res.error || "No se pudo cambiar el estado");
    alert(" Estado actualizado");
    location.reload();
  }catch(err){
    alert(" " + err.message);
  }
}
