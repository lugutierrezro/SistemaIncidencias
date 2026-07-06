# Iniciar frontend PHP con entorno configurado (sin backend Elixir)

$phpBin  = "C:\xampp\php"

Write-Host "Iniciando Frontend PHP en el puerto 8000..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-Command", "`$env:PATH = '$phpBin;' + `$env:PATH; cd frontend/public; php -S localhost:8000"

Write-Host "¡Servicio iniciado en segundo plano!" -ForegroundColor Yellow
Write-Host "-> Frontend Web: http://localhost:8000"
