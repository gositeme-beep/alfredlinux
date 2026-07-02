const REFRESH_MS = 60000;

function fmt(ts) {
  if (!ts) return "-";
  const d = new Date(ts * 1000);
  return d.toLocaleString();
}

function setList(id, items) {
  const el = document.getElementById(id);
  el.innerHTML = "";
  (items || []).forEach((item) => {
    const li = document.createElement("li");
    li.textContent = item;
    el.appendChild(li);
  });
}

async function loadStatus() {
  const pill = document.getElementById("refreshPill");
  pill.textContent = "Refreshing";
  try {
    const [statusRes, aboutRes] = await Promise.all([
      fetch(`data/public-status.json?t=${Date.now()}`),
      fetch(`data/about-alfredlinux.json?t=${Date.now()}`),
    ]);
    const status = await statusRes.json();
    const about = await aboutRes.json();

    document.getElementById("releaseName").textContent = status.release_name;
    document.getElementById("releaseTagline").textContent = status.tagline;
    document.getElementById("progressPct").textContent = `${status.progress_pct}%`;
    document.getElementById("phase").textContent = status.phase_label;
    document.getElementById("eta").textContent = status.eta_window;
    document.getElementById("publicNote").textContent = status.public_note;
    document.getElementById("updated").textContent = fmt(status.last_updated_epoch);
    document.getElementById("progressBar").style.width = `${Math.max(0, Math.min(100, status.progress_pct))}%`;

    setList("gates", status.quality_gates);
    setList("about", about.facts);

    pill.textContent = "Live";
  } catch (err) {
    pill.textContent = "Retrying";
    console.error(err);
  }
}

loadStatus();
setInterval(loadStatus, REFRESH_MS);
