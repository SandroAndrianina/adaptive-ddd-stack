<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>adaptive-ddd-stack</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
    h1 { margin: 0 0 .3rem; }
    .sub { color: #666; margin-bottom: 1.5rem; }
    fieldset { border: 1px solid #ddd; padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; }
    legend { font-weight: 600; padding: 0 .5rem; }
    label { display: inline-block; margin-right: 1rem; margin-bottom: .4rem; }
    input, select, button { padding: .4rem .6rem; font: inherit; }
    button { cursor: pointer; }
    table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
    th, td { border: 1px solid #ddd; padding: .4rem .6rem; text-align: left; font-size: .9rem; }
    th { background: #f4f4f4; }
    .row-actions button { background: none; border: none; color: #b00; cursor: pointer; }
    #msg { margin-top: 1rem; }
    .ok  { color: #0a7a29; }
    .err { color: #b00020; }
    .tabs { margin-bottom: 1rem; }
    .tabs button { margin-right: .5rem; }
    .tabs button.active { background: #333; color: #fff; }
  </style>
</head>
<body>
  <h1>adaptive-ddd-stack</h1>
  <p class="sub">Any entity declared in <code>entities.json</code> works here — no per-entity code.</p>

  <div class="tabs" id="tabs"></div>

  <fieldset>
    <legend id="form-legend">New item</legend>
    <form id="create-form"></form>
    <div id="msg"></div>
  </fieldset>

  <button id="refresh">Refresh</button>
  <table>
    <thead id="thead"></thead>
    <tbody id="tbody"></tbody>
  </table>

<script>
let currentEntity = null;
let currentSchema = null;

const $ = (s) => document.querySelector(s);
const msg = $('#msg');

function setMsg(text, cls) { msg.textContent = text; msg.className = cls || ''; }

async function fetchJSON(url, opts = {}) {
  const res = await fetch(url, opts);
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || res.statusText);
  return data;
}

async function loadEntities() {
  const names = await fetchJSON('/api/_entities');
  const tabs = $('#tabs');
  tabs.innerHTML = '';
  names.forEach((n, i) => {
    const b = document.createElement('button');
    b.textContent = n;
    b.onclick = () => selectEntity(n);
    tabs.appendChild(b);
    if (i === 0) currentEntity = n;
  });
  if (currentEntity) selectEntity(currentEntity);
}

async function selectEntity(name) {
  currentEntity = name;
  document.querySelectorAll('.tabs button').forEach(b =>
    b.classList.toggle('active', b.textContent === name));
  currentSchema = await fetchJSON('/api/_schema/' + name);
  renderForm();
  await loadRows();
}

function renderForm() {
  const form = $('#create-form');
  $('#form-legend').textContent = 'New ' + currentEntity;
  form.innerHTML = '';
  currentSchema.fields.forEach(f => {
    if (f.primary || f.autoCreate) return;   // never ask for id or createdAt
    const label = document.createElement('label');
    label.textContent = f.name + ': ';
    let input;
    if (f.type === 'boolean') {
      input = document.createElement('input');
      input.type = 'checkbox';
    } else if (f.type === 'int') {
      input = document.createElement('input');
      input.type = 'number';
    } else {
      input = document.createElement('input');
      input.type = 'text';
    }
    input.name = f.name;
    label.appendChild(input);
    form.appendChild(label);
  });
  const submit = document.createElement('button');
  submit.type = 'submit';
  submit.textContent = 'Create';
  form.appendChild(submit);

  form.onsubmit = async (ev) => {
    ev.preventDefault();
    setMsg('Sending…');
    const fd = new FormData(form);
    const payload = {};
    currentSchema.fields.forEach(f => {
      if (f.primary || f.autoCreate) return;
      if (f.type === 'boolean') payload[f.name] = fd.get(f.name) === 'on';
      else if (f.type === 'int') payload[f.name] = parseInt(fd.get(f.name) || '0', 10);
      else payload[f.name] = fd.get(f.name) || '';
    });
    try {
      const saved = await fetchJSON('/api/' + currentEntity, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      setMsg('Saved #' + (saved.id ?? ''), 'ok');
      form.reset();
      await loadRows();
    } catch (e) { setMsg('Error: ' + e.message, 'err'); }
  };
}

async function loadRows() {
  const tbody = $('#tbody');
  const thead = $('#thead');
  tbody.innerHTML = '<tr><td>Loading…</td></tr>';
  try {
    const rows = await fetchJSON('/api/' + currentEntity);
    const cols = currentSchema.fields.map(f => f.name);

    thead.innerHTML = '<tr>' + cols.map(c => `<th>${c}</th>`).join('') + '<th></th></tr>';
    tbody.innerHTML = '';
    if (!rows.length) { tbody.innerHTML = `<tr><td colspan="${cols.length+1}">(empty)</td></tr>`; return; }

    for (const r of rows) {
      const tr = document.createElement('tr');
      tr.innerHTML = cols.map(c => `<td>${format(r[c])}</td>`).join('') +
        `<td class="row-actions"><button data-id="${r.id}">delete</button></td>`;
      tbody.appendChild(tr);
    }
    tbody.querySelectorAll('button[data-id]').forEach(b => {
      b.onclick = async () => {
        if (!confirm('Delete #' + b.dataset.id + '?')) return;
        try {
          await fetchJSON('/api/' + currentEntity + '/' + b.dataset.id, { method: 'DELETE' });
          await loadRows();
        } catch (e) { setMsg('Delete error: ' + e.message, 'err'); }
      };
    });
  } catch (e) {
    tbody.innerHTML = '';
    setMsg('Load error: ' + e.message, 'err');
  }
}

function format(v) {
  if (v === null || v === undefined) return '';
  if (typeof v === 'boolean') return v ? 'yes' : 'no';
  return String(v);
}

$('#refresh').onclick = loadRows;
loadEntities();
</script>
</body>
</html>