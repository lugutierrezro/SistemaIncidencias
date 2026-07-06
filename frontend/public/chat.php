<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\chat.php
// Soporte Live — Adaptador de entrada UI (Arquitectura Hexagonal)

require_once __DIR__ . '/api/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvChatRepository;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvIncidenciaRepository;
use App\Application\UseCases\Chat\GetAllConversacionesUseCase;
use App\Application\UseCases\Incidencia\GetAllIncidenciasUseCase;

$conversaciones = [];
$incidencias    = [];
$backendOnline  = false;

try {
    $chatRepo = new SqlsrvChatRepository();
    $incidenciaRepo = new SqlsrvIncidenciaRepository();

    // Cargar conversaciones activas
    $chatUseCase = new GetAllConversacionesUseCase($chatRepo);
    $convsEntities = $chatUseCase->execute();
    foreach ($convsEntities as $c) {
        $conversaciones[] = $c->toArray();
    }

    // Cargar incidencias para el modal de nueva conversación
    $incUseCase = new GetAllIncidenciasUseCase($incidenciaRepo);
    $incEntities = $incUseCase->execute();
    foreach ($incEntities as $i) {
        if ($i->estado !== 'resuelta') {
            $incidencias[] = $i->toArray();
        }
    }

    $backendOnline = true;
} catch (Throwable $e) {
    $backendOnline = false;
}

$page_title = "Soporte Live";
$extra_css  = '
  <style>
    .online-indicator { display:inline-block; width:9px; height:9px; background:#10b981; border-radius:50%; border:2px solid white; }
    .msg-timestamp { font-size:0.68rem; color:var(--text-light); margin-top:3px; }
    .msg-sent .msg-timestamp { text-align:right; }
    .typing-indicator { display:flex; gap:5px; padding:0.6rem 1rem; align-items:center; }
    .typing-dot { width:8px; height:8px; background:#94a3b8; border-radius:50%; animation:typingBounce 1.2s infinite; }
    .typing-dot:nth-child(2){ animation-delay:0.2s; }
    .typing-dot:nth-child(3){ animation-delay:0.4s; }
    @keyframes typingBounce { 0%,80%,100%{transform:translateY(0)} 40%{transform:translateY(-6px)} }

    .conv-item { display:flex; align-items:center; gap:10px; padding:0.85rem 1.1rem; cursor:pointer; transition:background 0.15s; border-bottom:1px solid #f1f5f9; }
    .conv-item:hover { background:var(--sky-50); }
    .conv-item.active { background:var(--sky-100); border-right:3px solid var(--primary); }
    .conv-avatar { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.9rem; font-weight:700; color:#fff; flex-shrink:0; }
    .conv-name { font-size:0.87rem; font-weight:600; color:var(--text-dark); }
    .conv-preview { font-size:0.76rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:150px; }
    .conv-time { font-size:0.68rem; color:var(--text-light); }
    .estado-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
    .estado-activa  { background:#10b981; }
    .estado-espera  { background:#f59e0b; }
    .estado-cerrada { background:#94a3b8; }

    .empty-chat { display:flex; flex-direction:column; align-items:center; justify-content:center; flex:1; gap:1rem; color:var(--text-muted); }
    .empty-chat i { font-size:3rem; opacity:0.2; }

    .api-status { font-size:0.75rem; padding:2px 8px; border-radius:99px; display:inline-flex; align-items:center; gap:4px; }
    .api-ok      { background:#d1fae5; color:#065f46; }
    .api-error   { background:#fee2e2; color:#991b1b; }
    .api-loading { background:#e0f2fe; color:#0369a1; }
  </style>
';

include 'components/head.php';
include 'components/sidebar.php';
?>

<div class="page-wrapper">
  <?php
  $search_placeholder     = "Buscar conversaciones...";
  $search_oninput         = "filtrarConversaciones(this.value)";
  $extra_topbar_actions   = '<span id="apiStatus" class="api-status ' . ($backendOnline ? 'api-ok' : 'api-error') . '"><i class="fa-solid ' . ($backendOnline ? 'fa-circle-check' : 'fa-circle-xmark') . '"></i> ' . ($backendOnline ? 'BD Conectada' : 'Sin conexión a BD') . '</span>';
  include 'components/topbar.php';
  ?>

  <main class="page-content" style="padding-bottom:0; height:calc(100vh - 70px); display:flex; flex-direction:column; overflow:hidden;">
    <!-- Header -->
    <div class="page-header mb-3 flex-shrink-0" style="padding-top:1.5rem; padding-left:0.25rem;">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <div class="breadcrumb-custom">
            <a href="index.php">Inicio</a>
            <span><i class="fa-solid fa-chevron-right" style="font-size:0.6rem"></i></span>
            <span>Soporte Live</span>
          </div>
          <h1>Soporte en Tiempo Real</h1>
          <p>Chat conectado directamente a SQL Server via PHP</p>
        </div>
        <button class="btn-sky" data-bs-toggle="modal" data-bs-target="#modalNuevaCon">
          <i class="fa-solid fa-plus me-1"></i>Nueva Conversación
        </button>
      </div>
    </div>

    <!-- CHAT LAYOUT -->
    <div class="chat-layout flex-grow-1" style="min-height:0;" data-aos="fade-up">

      <!-- Lista de conversaciones -->
      <div class="chat-sidebar" style="width:310px; display:flex; flex-direction:column;">
        <div style="padding:0.85rem; border-bottom:1px solid var(--border);">
          <input type="text" class="form-control-custom" placeholder="Buscar conversaciones..."
                 oninput="filtrarConversaciones(this.value)" style="font-size:0.83rem;">
        </div>
        <div style="padding:0.5rem 1rem 0.3rem; font-size:0.7rem; font-weight:700; letter-spacing:0.08em; color:var(--text-muted); text-transform:uppercase;">
          Conversaciones Activas · <span id="convCount">—</span>
        </div>
        <div id="convList" style="flex:1; overflow-y:auto;"></div>
      </div>

      <!-- Panel de chat activo -->
      <div id="chatMain" class="chat-main" style="display:flex; flex-direction:column;">
        <!-- Estado vacío -->
        <div class="empty-chat" id="emptyState">
          <i class="fa-solid fa-comments"></i>
          <p style="font-size:0.9rem;">Selecciona una conversación o crea una nueva</p>
        </div>

        <!-- Área activa de chat -->
        <div id="chatActive" style="display:none; flex-direction:column; height:100%;">
          <!-- Header del chat -->
          <div class="chat-main-header">
            <div style="position:relative; display:inline-block;">
              <div class="user-avatar" id="chatAvatarActive" style="width:44px;height:44px;background:linear-gradient(135deg,#38bdf8,#0ea5e9);">—</div>
            </div>
            <div style="flex:1; min-width:0;">
              <div style="font-weight:700; font-size:0.95rem; display:flex; align-items:center; gap:8px;">
                <span id="chatNameActive">—</span>
                <span class="online-indicator" id="onlineIndicator"></span>
              </div>
              <div style="font-size:0.78rem; color:var(--text-muted);" id="chatSubtitleActive">—</div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
              <button class="btn-sky-outline py-1 px-3" onclick="resolverConversacion()" id="btnResolver">
                <i class="fa-solid fa-circle-check me-1"></i>Resolver
              </button>
              <button class="topbar-btn" onclick="recargarMensajes()" title="Actualizar">
                <i class="fa-solid fa-arrows-rotate" id="reloadIcon"></i>
              </button>
            </div>
          </div>

          <!-- Detalles de clasificación de incidencia -->
          <div id="incidenciaBanner" style="display:none; padding:0.6rem 1.5rem; background:#f0f9ff; border-bottom:1px solid var(--border); font-size:0.82rem; color:var(--text-muted); align-items:center; gap:1.25rem; flex-wrap:wrap;">
            <div class="d-flex align-items-center gap-1">
              <i class="fa-solid fa-tags" style="color:var(--primary);"></i>
              <span>Clasificación:</span>
              <strong id="bannerClasificacion" style="color:#0369a1; margin-left:3px;">—</strong>
            </div>
            <div class="d-flex align-items-center gap-1">
              <i class="fa-solid fa-triangle-exclamation" style="color:var(--primary);"></i>
              <span>Prioridad:</span>
              <strong id="bannerPrioridad" style="color:#0369a1; margin-left:3px;">—</strong>
            </div>
            <div class="d-flex align-items-center gap-1">
              <i class="fa-solid fa-school" style="color:var(--primary);"></i>
              <span>Aula:</span>
              <strong id="bannerAula" style="color:#0369a1; margin-left:3px;">—</strong>
            </div>
          </div>

          <!-- Mensajes -->
          <div id="chatMessages" style="flex:1; overflow-y:auto; padding:1.25rem; display:flex; flex-direction:column; gap:0.85rem; background:var(--sky-50);"></div>

          <!-- Footer input -->
          <div class="chat-footer" style="background:white; border-top:1px solid var(--border); padding:0.9rem 1.25rem; display:flex; align-items:center; gap:0.75rem;">
            <select id="tipoRemitente" class="form-control-custom form-select-custom py-1" style="width:130px; font-size:0.8rem;">
              <option value="soporte">🎧 Soporte</option>
              <option value="usuario">👤 Usuario</option>
            </select>
            <input type="text" class="chat-input-field flex-grow-1" id="chatInput"
                   placeholder="Escribe tu mensaje... (Enter para enviar)"
                   onkeypress="if(event.key==='Enter')enviarMensaje()">
            <button class="chat-send-btn" onclick="enviarMensaje()" title="Enviar">
              <i class="fa-solid fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- MODAL: Nueva Conversación -->
<div class="modal fade modal-custom" id="modalNuevaCon" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-comment-medical me-2"></i>Nueva Conversación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label-custom">Nombre del usuario / solicitante *</label>
            <input type="text" class="form-control-custom" id="newConvUsuario" placeholder="Ej: Juan Pérez">
          </div>
          <div class="col-12">
            <label class="form-label-custom">Asunto / Título *</label>
            <input type="text" class="form-control-custom" id="newConvTitulo" placeholder="Ej: Proyector sin señal en Aula 102">
          </div>
          <div class="col-12">
            <label class="form-label-custom">Primer mensaje</label>
            <textarea class="form-control-custom" id="newConvMsg" rows="3" placeholder="Describe el problema inicial..."></textarea>
          </div>
          <div class="col-12">
            <label class="form-label-custom">Vincular a Incidencia (Opcional)</label>
            <select class="form-control-custom form-select-custom" id="newConvIncidencia">
              <option value="">-- Sin Vincular --</option>
              <?php foreach ($incidencias as $inc): ?>
                <option value="<?php echo htmlspecialchars($inc['id']); ?>">
                  <?php echo htmlspecialchars($inc['titulo']) . ' (' . htmlspecialchars($inc['aula_nombre'] ?? 'Sin Aula') . ')'; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer gap-2">
        <button class="btn-sky-outline" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn-sky" onclick="crearConversacion()"><i class="fa-solid fa-paper-plane me-1"></i>Crear e Iniciar</button>
      </div>
    </div>
  </div>
</div>

<?php ob_start(); ?>
<script>
const API_CHAT       = 'api/chat.php';
const POLL_INTERVAL  = 3000;
const AVATAR_COLORS  = ['#0ea5e9','#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#0369a1'];

const backendOnline  = <?php echo $backendOnline ? 'true' : 'false'; ?>;
let conversaciones   = <?php echo json_encode($conversaciones); ?>;
let incidencias      = <?php echo json_encode($incidencias); ?>;
let convActivaId     = null;
let ultimoMsgId      = null;

function initials(name) {
  return (name||'?').split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase();
}
function avatarColor(name) {
  let hash = 0;
  for (const c of (name||'')) hash = (hash*31 + c.charCodeAt(0)) & 0xFFFFFFFF;
  return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
}
function formatTime(isoStr) {
  if (!isoStr) return '';
  const d = new Date(isoStr);
  return isNaN(d.getTime()) ? isoStr : d.toLocaleTimeString('es-ES', { hour:'2-digit', minute:'2-digit' });
}
function setApiStatus(state, msg) {
  const el = document.getElementById('apiStatus');
  if (!el) return;
  el.className = `api-status api-${state}`;
  const icons = { ok:'fa-circle-check', error:'fa-circle-xmark', loading:'fa-spinner fa-spin' };
  el.innerHTML = `<i class="fa-solid ${icons[state]}"></i> ${msg}`;
}

async function apiGet(url) {
  const r = await fetch(API_CHAT + url);
  if (!r.ok) throw new Error(`HTTP ${r.status}`);
  return r.json();
}
async function apiPost(url, body) {
  const r = await fetch(API_CHAT + url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  });
  if (!r.ok) { const err = await r.json().catch(()=>({})); throw new Error(err.error || `HTTP ${r.status}`); }
  return r.json();
}
async function apiPut(url) {
  const r = await fetch(API_CHAT + url, { method: 'PUT' });
  if (!r.ok) throw new Error(`HTTP ${r.status}`);
  return r.json();
}

function renderConversaciones(data) {
  document.getElementById('convCount').textContent = data.length;
  const list = document.getElementById('convList');
  if (!data.length) {
    list.innerHTML = `<div style="text-align:center;padding:2rem;color:var(--text-muted);"><i class="fa-solid fa-comment-slash" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:0.5rem;"></i>Sin conversaciones activas</div>`;
    return;
  }
  list.innerHTML = data.map(c => {
    const color   = avatarColor(c.usuario_nombre);
    const inits   = initials(c.usuario_nombre);
    const isActv  = c.id === convActivaId;
    const stateCls= { activa:'estado-activa', espera:'estado-espera', cerrada:'estado-cerrada' }[c.estado] || 'estado-activa';
    return `
    <div class="conv-item ${isActv ? 'active' : ''}" onclick="seleccionarConversacion('${c.id}','${escapeAttr(c.usuario_nombre)}','${escapeAttr(c.titulo)}','${color}')">
      <div class="conv-avatar" style="background:${color};">${inits}</div>
      <div style="flex:1;min-width:0;">
        <div class="conv-name">${escapeHtml(c.usuario_nombre)}</div>
        <div class="conv-preview">${escapeHtml(c.titulo)}</div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0;">
        <div class="conv-time">${formatTime(c.updated_at)}</div>
        <div class="estado-dot ${stateCls}" title="${c.estado}"></div>
      </div>
    </div>`;
  }).join('');
}

function filtrarConversaciones(q) {
  renderConversaciones(conversaciones.filter(c =>
    (c.usuario_nombre||'').toLowerCase().includes(q.toLowerCase()) ||
    (c.titulo||'').toLowerCase().includes(q.toLowerCase())
  ));
}

async function cargarConversaciones() {
  try {
    setApiStatus('loading', 'Cargando...');
    conversaciones = await apiGet('?action=conversaciones');
    setApiStatus('ok', 'BD Conectada');
    renderConversaciones(conversaciones);
  } catch (e) {
    setApiStatus('error', 'Error de conexión');
    renderConversaciones(conversaciones);
  }
}

async function seleccionarConversacion(id, nombre, titulo, color) {
  convActivaId = id;
  ultimoMsgId  = null;
  document.getElementById('emptyState').style.display = 'none';
  const chatActive = document.getElementById('chatActive');
  chatActive.style.display = 'flex';
  document.getElementById('chatAvatarActive').textContent = initials(nombre);
  document.getElementById('chatAvatarActive').style.background = `linear-gradient(135deg,${color},${color}bb)`;
  document.getElementById('chatNameActive').textContent = nombre;
  document.getElementById('chatSubtitleActive').textContent = titulo;
  document.getElementById('chatMessages').innerHTML = `<div style="text-align:center;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Cargando mensajes...</div>`;

  const conv   = conversaciones.find(c => c.id === id);
  const banner = document.getElementById('incidenciaBanner');
  if (conv && conv.incidencia_id) {
    banner.style.display = 'flex';
    const cat = conv.categoria_nombre || 'Sin categoría';
    const sub = conv.subcategoria_nombre || 'Sin subcategoría';
    document.getElementById('bannerClasificacion').textContent = `${cat} > ${sub}`;
    
    const prio = conv.prioridad || 'media';
    const prioLabel = { alta: '🔴 Alta', media: '🟡 Media', baja: '🟢 Baja' }[prio.toLowerCase()] || prio;
    document.getElementById('bannerPrioridad').textContent = prioLabel;
    
    const aula = conv.aula_nombre || 'Ninguna';
    document.getElementById('bannerAula').textContent = aula;
  } else {
    banner.style.display = 'none';
  }

  renderConversaciones(conversaciones);
  await cargarTodosLosMensajes();
}

async function cargarTodosLosMensajes() {
  if (!convActivaId) return;
  try {
    const mensajes = await apiGet(`?action=mensajes&conv_id=${encodeURIComponent(convActivaId)}`);
    const box = document.getElementById('chatMessages');
    box.innerHTML = '';
    mensajes.forEach(m => appendMensaje(m));
    if (mensajes.length) ultimoMsgId = mensajes[mensajes.length-1].id;
    else ultimoMsgId = null;
    box.scrollTop = box.scrollHeight;
  } catch (e) {
    document.getElementById('chatMessages').innerHTML = `<div style="text-align:center;color:var(--text-danger);">Error al cargar mensajes.</div>`;
  }
}

async function pollMensajesNuevos() {
  if (!convActivaId) return;
  try {
    let url = `?action=mensajes_nuevos&conv_id=${encodeURIComponent(convActivaId)}`;
    if (ultimoMsgId) url += `&desde=${encodeURIComponent(ultimoMsgId)}`;
    const nuevos = await apiGet(url);
    if (nuevos.length) {
      nuevos.forEach(m => appendMensaje(m));
      ultimoMsgId = nuevos[nuevos.length-1].id;
      const box = document.getElementById('chatMessages');
      box.scrollTop = box.scrollHeight;
    }
  } catch (e) { }
}

async function recargarMensajes() {
  const icon = document.getElementById('reloadIcon');
  icon.classList.add('fa-spin');
  await cargarTodosLosMensajes();
  setTimeout(() => icon.classList.remove('fa-spin'), 600);
}

function appendMensaje(m) {
  const box = document.getElementById('chatMessages');
  const esEnviado = (m.tipo_remitente||'').toLowerCase() === 'soporte';
  const displayName = esEnviado ? (m.remitente||'Soporte TI') : (m.remitente||'Usuario');
  const color = avatarColor(displayName);
  const inits = initials(displayName);
  const div = document.createElement('div');
  div.className = `msg-group ${esEnviado ? 'msg-sent' : 'msg-received'}`;
  div.style.animation = 'fadeUp 0.3s ease forwards';
  div.innerHTML = esEnviado
    ? `<div style="flex:1;display:flex;flex-direction:column;align-items:flex-end;">
         <div class="msg-bubble">${escapeHtml(m.contenido)}</div>
         <div class="msg-timestamp">${escapeHtml(displayName)} · ${formatTime(m.inserted_at)}</div>
       </div>
       <div class="msg-avatar" style="background:${color};width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;color:#fff;flex-shrink:0;">${inits}</div>`
    : `<div class="msg-avatar" style="background:${color};width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;color:#fff;flex-shrink:0;">${inits}</div>
       <div style="display:flex;flex-direction:column;">
         <div class="msg-bubble">${escapeHtml(m.contenido)}</div>
         <div class="msg-timestamp">${escapeHtml(displayName)} · ${formatTime(m.inserted_at)}</div>
       </div>`;
  box.appendChild(div);
}

async function enviarMensaje() {
  if (!convActivaId) return;
  const input  = document.getElementById('chatInput');
  const tipo   = document.getElementById('tipoRemitente').value;
  const nombre = tipo === 'soporte' ? 'Admin Operaciones' : 'Usuario';
  const contenido = input.value.trim();
  if (!contenido) return;
  input.value = '';
  try {
    const msg = await apiPost(`?action=nuevo_mensaje&conv_id=${encodeURIComponent(convActivaId)}`, {
      contenido, remitente: nombre, tipo_remitente: tipo
    });
    appendMensaje(msg);
    ultimoMsgId = msg.id;
    document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
    conversaciones = await apiGet('?action=conversaciones');
    renderConversaciones(conversaciones);
  } catch (e) {
    alert('No se pudo enviar el mensaje: ' + e.message);
    input.value = contenido;
  }
}

async function crearConversacion() {
  const usuario = document.getElementById('newConvUsuario').value.trim();
  const titulo  = document.getElementById('newConvTitulo').value.trim();
  const primerMsg = document.getElementById('newConvMsg').value.trim();
  const incidenciaId = document.getElementById('newConvIncidencia').value;
  if (!usuario || !titulo) { alert('El nombre y el asunto son obligatorios.'); return; }
  try {
    const conv = await apiPost('?action=nueva_conversacion', {
      titulo, usuario_nombre: usuario, incidencia_id: incidenciaId || null
    });
    if (primerMsg) {
      await apiPost(`?action=nuevo_mensaje&conv_id=${encodeURIComponent(conv.id)}`, {
        contenido: primerMsg, remitente: usuario, tipo_remitente: 'usuario'
      });
    }
    window.location.reload();
  } catch (e) {
    alert('Error al crear la conversación: ' + e.message);
  }
}

async function resolverConversacion() {
  if (!convActivaId) return;
  if (!confirm('¿Marcar esta conversación como resuelta/cerrada?')) return;
  try {
    await apiPut(`?action=cerrar_conversacion&id=${encodeURIComponent(convActivaId)}`);
    window.location.reload();
  } catch (e) {
    alert('Error al cerrar la conversación: ' + e.message);
  }
}

function procesarParametrosUrl() {
  const params = new URLSearchParams(window.location.search);
  const incId  = params.get('incidencia_id');
  if (!incId) return;
  const existente = conversaciones.find(c => c.incidencia_id === incId);
  if (existente) {
    seleccionarConversacion(existente.id, existente.usuario_nombre, existente.titulo, avatarColor(existente.usuario_nombre));
  } else {
    const inc = incidencias.find(i => i.id === incId);
    if (inc) {
      const modal = new bootstrap.Modal(document.getElementById('modalNuevaCon'));
      modal.show();
      document.getElementById('newConvUsuario').value = inc.reportado_por || 'Usuario';
      document.getElementById('newConvTitulo').value  = inc.titulo;
      const sel = document.getElementById('newConvIncidencia');
      if (sel) sel.value = inc.id;
      setTimeout(() => document.getElementById('newConvMsg').focus(), 500);
    }
  }
}

function escapeHtml(text) {
  return String(text ?? '').replace(/[&<>"']/g, m =>
    ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])
  );
}
function escapeAttr(text) {
  return String(text ?? '').replace(/'/g, "\\'").replace(/\n/g,' ');
}

renderConversaciones(conversaciones);
procesarParametrosUrl();
setInterval(pollMensajesNuevos, POLL_INTERVAL);
</script>
<?php
$extra_js = ob_get_clean();
include 'components/footer.php';
?>
