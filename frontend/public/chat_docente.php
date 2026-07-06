<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\chat_docente.php
// Chat de Soporte para el Docente (Público, sin credenciales)

require_once __DIR__ . '/api/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvIncidenciaRepository;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvChatRepository;

$incidenciaId = $_GET['incidencia_id'] ?? null;
$incidencia = null;
$errorMsg = '';
$mensajes = [];

if ($incidenciaId) {
    try {
        $incidenciaRepo = new SqlsrvIncidenciaRepository();
        $chatRepo = new SqlsrvChatRepository();
        
        $incidencia = $incidenciaRepo->findById($incidenciaId);
        if ($incidencia) {
            $mensajesEntities = $chatRepo->findMensajesByConversacion($incidenciaId);
            foreach ($mensajesEntities as $m) {
                $mensajes[] = $m->toArray();
            }
            $incidencia = $incidencia->toArray();
        }
    } catch (Throwable $e) {
        $errorMsg = 'Error al conectar con la base de datos.';
    }
}

if (!$incidencia) {
    header("Location: reportar.php");
    exit;
}

$idShort = strtoupper(substr($incidencia['id'], 0, 8));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat de Soporte — Incidencia #<?php echo $idShort; ?></title>
  
  <!-- FontAwesome + Google Fonts -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/bootstrap.min.css" rel="stylesheet">

  <style>
    :root {
      --primary: #0ea5e9;
      --bg: #f8fafc;
      --text-dark: #0f172a;
      --text-muted: #64748b;
      --text-light: #94a3b8;
      --border: #e2e8f0;
      --bubble-received: #fff;
      --bubble-sent: #0ea5e9;
    }
    
    body {
      font-family: 'Outfit', sans-serif;
      background: #f1f5f9;
      color: var(--text-dark);
      height: 100vh;
      display: flex;
      flex-direction: column;
      margin: 0;
      overflow: hidden;
    }

    .chat-header {
      background: white;
      border-bottom: 1px solid var(--border);
      padding: 0.85rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.02);
      flex-shrink: 0;
    }

    .chat-logo {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      background: linear-gradient(135deg, #38bdf8, #0ea5e9);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      box-shadow: 0 4px 12px rgba(14, 165, 233, 0.25);
    }

    .chat-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: #f8fafc;
      overflow: hidden;
      max-width: 600px;
      width: 100%;
      margin: 0 auto;
      box-shadow: 0 0 30px rgba(0,0,0,0.03);
    }

    .messages-area {
      flex: 1;
      overflow-y: auto;
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      gap: 0.85rem;
      background: #f0f4f8;
    }

    .msg-group {
      display: flex;
      gap: 10px;
      max-width: 85%;
    }

    .msg-sent {
      align-self: flex-end;
      flex-direction: row-reverse;
    }

    .msg-received {
      align-self: flex-start;
    }

    .msg-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.72rem;
      font-weight: 700;
      color: white;
      flex-shrink: 0;
    }

    .msg-bubble {
      padding: 0.7rem 1rem;
      border-radius: 16px;
      font-size: 0.9rem;
      line-height: 1.4;
      box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }

    .msg-sent .msg-bubble {
      background: var(--bubble-sent);
      color: white;
      border-bottom-right-radius: 4px;
    }

    .msg-received .msg-bubble {
      background: var(--bubble-received);
      color: var(--text-dark);
      border-bottom-left-radius: 4px;
      border: 1px solid var(--border);
    }

    .msg-timestamp {
      font-size: 0.65rem;
      color: var(--text-light);
      margin-top: 3px;
    }

    .msg-sent .msg-timestamp {
      text-align: right;
    }

    .chat-footer {
      background: white;
      border-top: 1px solid var(--border);
      padding: 0.85rem 1.1rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      flex-shrink: 0;
    }

    .chat-input-field {
      flex: 1;
      border: 1.5px solid var(--border);
      border-radius: 20px;
      padding: 0.55rem 1.1rem;
      font-size: 0.9rem;
      transition: all 0.2s;
    }

    .chat-input-field:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
    }

    .chat-send-btn {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
      box-shadow: 0 3px 8px rgba(14, 165, 233, 0.3);
    }

    .chat-send-btn:hover {
      background: #0284c7;
      transform: scale(1.05);
    }

    .classroom-tag {
      background: #e0f2fe;
      color: #0369a1;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 99px;
      text-transform: uppercase;
    }
  </style>
</head>
<body>

  <div class="chat-container">
    <!-- Header -->
    <div class="chat-header">
      <div class="chat-logo">
        <i class="fa-solid fa-comments"></i>
      </div>
      <div style="flex:1; min-width:0;">
        <div style="font-weight:700; font-size:0.92rem; display:flex; align-items:center; gap:6px;">
          <span>Soporte Técnico</span>
          <span class="classroom-tag"><?php echo htmlspecialchars($incidencia['aula_nombre']); ?></span>
        </div>
        <div style="font-size:0.75rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
          Asunto: <?php echo htmlspecialchars($incidencia['titulo']); ?>
        </div>
      </div>
      <div>
        <span style="font-size:0.78rem; font-weight:700; color:var(--text-light);">#<?php echo $idShort; ?></span>
      </div>
    </div>

    <!-- Messages Area -->
    <div class="messages-area" id="chatMessages">
      <!-- Se carga dinámicamente -->
    </div>

    <!-- Input Footer -->
    <div class="chat-footer">
      <input type="text" class="chat-input-field" id="chatInput" placeholder="Escribe tu mensaje aquí..." onkeypress="if(event.key==='Enter')enviarMensaje()">
      <button class="chat-send-btn" onclick="enviarMensaje()">
        <i class="fa-solid fa-paper-plane" style="font-size:0.9rem;"></i>
      </button>
    </div>
  </div>

  <script>
    const convId = <?php echo json_encode($incidencia['id']); ?>;
    const docenteNombre = <?php echo json_encode($incidencia['reportado_por'] ?? 'Docente'); ?>;
    let ultimoMsgId = null;

    function formatTime(isoStr) {
      if (!isoStr) return '';
      const d = new Date(isoStr);
      return isNaN(d.getTime()) ? isoStr : d.toLocaleTimeString('es-ES', { hour:'2-digit', minute:'2-digit' });
    }

    function appendMessage(m) {
      const box = document.getElementById('chatMessages');
      const esEnviado = (m.tipo_remitente || '').toLowerCase() === 'usuario';
      const displayName = esEnviado ? docenteNombre : 'Soporte TI';
      
      const div = document.createElement('div');
      div.className = `msg-group ${esEnviado ? 'msg-sent' : 'msg-received'}`;
      
      const initials = esEnviado ? 'D' : 'S';
      const color = esEnviado ? '#0ea5e9' : '#6366f1';
      
      div.innerHTML = esEnviado
        ? `<div style="flex:1; display:flex; flex-direction:column; align-items:flex-end;">
             <div class="msg-bubble">${escapeHtml(m.contenido)}</div>
             <div class="msg-timestamp">${formatTime(m.inserted_at)}</div>
           </div>
           <div class="msg-avatar" style="background:${color};">${initials}</div>`
        : `<div class="msg-avatar" style="background:${color};">${initials}</div>
           <div style="display:flex; flex-direction:column;">
             <div class="msg-bubble">${escapeHtml(m.contenido)}</div>
             <div class="msg-timestamp">${formatTime(m.inserted_at)}</div>
           </div>`;
           
      box.appendChild(div);
    }

    async function cargarMensajes() {
      try {
        const r = await fetch(`api/chat.php?action=mensajes&conv_id=${encodeURIComponent(convId)}`);
        if (!r.ok) return;
        const mensajes = await r.json();
        const box = document.getElementById('chatMessages');
        box.innerHTML = '';
        
        // Si no hay mensajes, mostrar tarjeta de bienvenida amigable
        if (mensajes.length === 0) {
          box.innerHTML = `
            <div style="background:rgba(255,255,255,0.9); border-radius:18px; border:1.5px dashed #0ea5e9; padding:1.75rem; text-align:center; margin:1rem auto; max-width:400px; box-shadow:0 10px 25px rgba(0,0,0,0.03);">
              <div style="width:50px; height:50px; border-radius:50%; background:#e0f2fe; color:#0ea5e9; display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin:0 auto 0.85rem;"><i class="fa-solid fa-headset"></i></div>
              <h6 class="fw-bold mb-1" style="color:var(--text-dark);">¡Conexión de Soporte Lista!</h6>
              <p class="text-muted mb-0" style="font-size:0.8rem; line-height:1.45;">Escribe directamente aquí el problema que tienes en este salón y el equipo técnico de soporte te responderá de inmediato.</p>
            </div>
          `;
        } else {
          mensajes.forEach(m => appendMessage(m));
        }
        
        if (mensajes.length) ultimoMsgId = mensajes[mensajes.length-1].id;
        box.scrollTop = box.scrollHeight;
      } catch (e) {}
    }

    async function pollMensajesNuevos() {
      try {
        let url = `api/chat.php?action=mensajes_nuevos&conv_id=${encodeURIComponent(convId)}`;
        if (ultimoMsgId) url += `&desde=${encodeURIComponent(ultimoMsgId)}`;
        const r = await fetch(url);
        if (!r.ok) return;
        const nuevos = await r.json();
        if (nuevos.length) {
          nuevos.forEach(m => appendMessage(m));
          ultimoMsgId = nuevos[nuevos.length-1].id;
          const box = document.getElementById('chatMessages');
          box.scrollTop = box.scrollHeight;
        }
      } catch (e) {}
    }

    async function enviarMensaje() {
      const input = document.getElementById('chatInput');
      const contenido = input.value.trim();
      if (!contenido) return;
      input.value = '';
      try {
        const res = await fetch(`api/chat.php?action=nuevo_mensaje&conv_id=${encodeURIComponent(convId)}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            contenido,
            remitente: docenteNombre,
            tipo_remitente: 'usuario'
          })
        });
        if (!res.ok) throw new Error('Error al enviar');
        const msg = await res.json();
        appendMessage(msg);
        ultimoMsgId = msg.id;
        document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
      } catch (e) {
        input.value = contenido;
      }
    }

    function escapeHtml(text) {
      return String(text ?? '').replace(/[&<>"']/g, m =>
        ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])
      );
    }

    // Iniciar polling
    cargarMensajes();
    setInterval(pollMensajesNuevos, 3000);
  </script>
</body>
</html>
