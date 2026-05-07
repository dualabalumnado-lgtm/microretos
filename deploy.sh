#!/bin/bash
# =============================================================================
# deploy.sh — Script de despliegue a Hostinger para microretos
# =============================================================================
# Uso:
#   ./deploy.sh              → Despliega todo (frontend + backend + activación)
#   ./deploy.sh --front      → Solo frontend
#   ./deploy.sh --back       → Solo backend
#   ./deploy.sh --ssh        → Solo activación SSH (caché + permisos)
#   ./deploy.sh --migrate    → Activación SSH + migraciones
#   ./deploy.sh --env        → Sube solo el .env (¡con cuidado!)
#   ./deploy.sh --help       → Muestra esta ayuda
# =============================================================================

set -e

# --- Configuración del servidor ---
SSH_KEY=~/.ssh/id_rsa
SSH_PORT=65002
SSH_USER=u197312986
SSH_HOST=145.79.20.29
REMOTE_ROOT=/home/u197312986/domains/dualab.es/public_html
REMOTE_BACKEND=$REMOTE_ROOT/api-backend

# --- Rutas locales ---
PROJECT_DIR=~/Documents/microretos
FRONTEND_DIR=$PROJECT_DIR/frontend-microretos

# --- Colores ---
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

log()    { echo -e "${CYAN}[deploy]${NC} $1"; }
ok()     { echo -e "${GREEN}[✓]${NC} $1"; }
warn()   { echo -e "${YELLOW}[!]${NC} $1"; }
error()  { echo -e "${RED}[✗]${NC} $1"; exit 1; }

# =============================================================================
step_frontend() {
    log "Compilando frontend..."
    cd "$FRONTEND_DIR"
    npm run build || error "Falló npm run build"
    ok "Frontend compilado"

    log "Subiendo frontend al servidor..."
    scp -P $SSH_PORT -i $SSH_KEY -r dist/* \
        $SSH_USER@$SSH_HOST:$REMOTE_ROOT/ \
        || error "Falló la subida del frontend"
    ok "Frontend subido a $REMOTE_ROOT/"
}

step_backend() {
    log "Subiendo backend al servidor..."
    scp -P $SSH_PORT -i $SSH_KEY -r \
        "$PROJECT_DIR/app" \
        "$PROJECT_DIR/routes" \
        "$PROJECT_DIR/database" \
        "$PROJECT_DIR/config" \
        "$PROJECT_DIR/public" \
        $SSH_USER@$SSH_HOST:$REMOTE_BACKEND/ \
        || error "Falló la subida del backend"
    ok "Backend subido a $REMOTE_BACKEND/"
}

step_env() {
    warn "Vas a subir el .env local al servidor. Esto sobreescribirá el .env del servidor."
    read -p "¿Seguro que quieres continuar? (s/N): " confirm
    [[ "$confirm" =~ ^[sS]$ ]] || { log "Cancelado."; return; }

    scp -P $SSH_PORT -i $SSH_KEY \
        "$PROJECT_DIR/.env" \
        $SSH_USER@$SSH_HOST:$REMOTE_BACKEND/.env \
        || error "Falló la subida del .env"
    ok ".env subido"
}

step_ssh_activate() {
    local run_migrate=$1

    log "Conectando al servidor para activar cambios..."

    ssh -p $SSH_PORT -i $SSH_KEY $SSH_USER@$SSH_HOST bash <<REMOTE_SCRIPT
set -e
cd $REMOTE_BACKEND

echo "→ Permisos de escritura..."
chmod -R 775 storage bootstrap/cache

echo "→ Limpiando caché..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

$(if [ "$run_migrate" = "true" ]; then
echo 'echo "→ Ejecutando migraciones..."'
echo 'php artisan migrate --force'
fi)

echo "→ Recargando configuración..."
php artisan config:cache
php artisan route:cache

echo "¡Activación completada!"
REMOTE_SCRIPT

    ok "Servidor activado correctamente"
}

# =============================================================================
show_help() {
    grep '^#   ' "$0" | sed 's/^#   /  /'
}

# =============================================================================
# Lógica principal
case "${1:-}" in
    --front)
        step_frontend
        ;;
    --back)
        step_backend
        ;;
    --ssh)
        step_ssh_activate false
        ;;
    --migrate)
        warn "Se ejecutará php artisan migrate --force en el servidor."
        read -p "¿Confirmar migraciones? (s/N): " confirm
        [[ "$confirm" =~ ^[sS]$ ]] || { log "Cancelado."; exit 0; }
        step_ssh_activate true
        ;;
    --env)
        step_env
        ;;
    --help)
        show_help
        ;;
    "")
        log "=== Despliegue completo ==="
        step_frontend
        step_backend
        step_ssh_activate false
        echo ""
        ok "=== Despliegue completado ==="
        warn "Si has añadido migraciones nuevas, ejecuta: ./deploy.sh --migrate"
        ;;
    *)
        error "Opción desconocida: $1. Usa ./deploy.sh --help"
        ;;
esac
