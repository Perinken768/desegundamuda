#!/usr/bin/env bash

set -Eeuo pipefail

# ============================================================
# CONFIGURACIÓN
# ============================================================

PROJECT_DIR="/var/www/desegundamuda"
BRANCH="main"
REMOTE="origin"
TAG_PREFIX="dsm_V"
VERSION_FILE="VERSION"

# Colores para la terminal.
GREEN="\033[0;32m"
YELLOW="\033[1;33m"
RED="\033[0;31m"
BLUE="\033[0;34m"
RESET="\033[0m"

info() {
    printf "${BLUE}%s${RESET}\n" "$1"
}

success() {
    printf "${GREEN}%s${RESET}\n" "$1"
}

warning() {
    printf "${YELLOW}%s${RESET}\n" "$1"
}

error() {
    printf "${RED}%s${RESET}\n" "$1" >&2
}

abort() {
    error "$1"
    exit 1
}

# ============================================================
# ENTRADA
# ============================================================

COMMIT_DESCRIPTION="${*:-actualización del proyecto DSM}"

cd "$PROJECT_DIR"

info "=============================================="
info " VERSIONADO AUTOMÁTICO DE DESEGUNDAMUDA"
info "=============================================="
echo

# ============================================================
# COMPROBACIONES PREVIAS
# ============================================================

git rev-parse --is-inside-work-tree >/dev/null 2>&1 \
    || abort "La ruta $PROJECT_DIR no contiene un repositorio Git."

CURRENT_BRANCH="$(git branch --show-current)"

if [[ "$CURRENT_BRANCH" != "$BRANCH" ]]; then
    abort "Debes ejecutar el script desde la rama '$BRANCH'. Rama actual: '$CURRENT_BRANCH'."
fi

if [[ -d ".git/rebase-merge" || -d ".git/rebase-apply" ]]; then
    abort "Hay un rebase en curso. Finalízalo o cancélalo antes de versionar."
fi

if [[ -f ".git/MERGE_HEAD" ]]; then
    abort "Hay una fusión en curso. Finalízala o cancélala antes de versionar."
fi

# ============================================================
# IGNORAR DOCUMENTACIÓN TEMPORAL
# ============================================================

touch .gitignore

if ! grep -qxF "documentacion-plugins/" .gitignore; then
    {
        echo
        echo "# Documentación generada automáticamente"
        echo "documentacion-plugins/"
    } >> .gitignore

    success "Añadido documentacion-plugins/ al .gitignore."
fi

# ============================================================
# ACTUALIZAR INFORMACIÓN REMOTA
# ============================================================

info "Consultando rama y etiquetas remotas..."

git fetch "$REMOTE" "$BRANCH" --tags --prune

# Comprobar si el remoto contiene commits que todavía no están
# presentes localmente.
read -r LOCAL_ONLY REMOTE_ONLY < <(
    git rev-list \
        --left-right \
        --count \
        "HEAD...${REMOTE}/${BRANCH}"
)

if (( REMOTE_ONLY > 0 )); then
    warning "La rama remota contiene $REMOTE_ONLY commit(s) nuevo(s)."
    info "Actualizando la rama local mediante rebase..."

    git pull \
        --rebase \
        --autostash \
        "$REMOTE" \
        "$BRANCH"

    success "Rama local actualizada."
fi

# ============================================================
# COMPROBAR CAMBIOS
# ============================================================

if [[ -z "$(git status --porcelain)" ]]; then
    warning "No existen cambios para guardar."
    echo

    LAST_EXISTING_TAG="$(
        git tag \
            --list "${TAG_PREFIX}[0-9]*.[0-9]*.[0-9]*" \
        | sort -V \
        | tail -n 1
    )"

    if [[ -n "$LAST_EXISTING_TAG" ]]; then
        info "Última versión existente: $LAST_EXISTING_TAG"
    else
        info "Todavía no existe ninguna versión DSM."
    fi

    exit 0
fi

# ============================================================
# LOCALIZAR LA ÚLTIMA VERSIÓN
# ============================================================

LAST_TAG="$(
    git tag \
        --list "${TAG_PREFIX}[0-9]*.[0-9]*.[0-9]*" \
    | sort -V \
    | tail -n 1
)"

if [[ -z "$LAST_TAG" ]]; then
    MAJOR=0
    MINOR=0
    PATCH=0

    info "No existe ninguna etiqueta DSM anterior."
else
    VERSION_WITHOUT_PREFIX="${LAST_TAG#"$TAG_PREFIX"}"

    if [[ "$VERSION_WITHOUT_PREFIX" =~ ^([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]; then
        MAJOR="${BASH_REMATCH[1]}"
        MINOR="${BASH_REMATCH[2]}"
        PATCH="${BASH_REMATCH[3]}"
    else
        abort "La última etiqueta encontrada no tiene un formato válido: $LAST_TAG"
    fi

    info "Última versión detectada: $LAST_TAG"
fi

# Incremento automático de la versión PATCH.
PATCH=$((PATCH + 1))

NEXT_VERSION="${MAJOR}.${MINOR}.${PATCH}"
NEXT_TAG="${TAG_PREFIX}${NEXT_VERSION}"

# Comprobar que la nueva etiqueta no exista por alguna anomalía.
if git rev-parse "$NEXT_TAG" >/dev/null 2>&1; then
    abort "La etiqueta $NEXT_TAG ya existe."
fi

success "Nueva versión: $NEXT_TAG"

# ============================================================
# GUARDAR EL ARCHIVO VERSION
# ============================================================

printf '%s\n' "$NEXT_TAG" > "$VERSION_FILE"

# ============================================================
# MOSTRAR CAMBIOS
# ============================================================

echo
info "Cambios que se guardarán:"
echo

git status --short

echo

# ============================================================
# CREAR COMMIT
# ============================================================

git add -A

if git diff --cached --quiet; then
    abort "No existen cambios preparados para crear el commit."
fi

COMMIT_MESSAGE="release: ${NEXT_TAG} - ${COMMIT_DESCRIPTION}"

info "Creando commit:"
echo "  $COMMIT_MESSAGE"
echo

git commit -m "$COMMIT_MESSAGE"

# ============================================================
# CREAR TAG ANOTADO
# ============================================================

git tag \
    -a "$NEXT_TAG" \
    -m "Versión ${NEXT_TAG}: ${COMMIT_DESCRIPTION}"

# ============================================================
# SUBIR A GITHUB
# ============================================================

info "Subiendo rama $BRANCH a $REMOTE..."

git push "$REMOTE" "$BRANCH"

info "Subiendo etiqueta $NEXT_TAG..."

git push "$REMOTE" "$NEXT_TAG"

# ============================================================
# RESULTADO
# ============================================================

echo
success "=============================================="
success " VERSIÓN PUBLICADA CORRECTAMENTE"
success "=============================================="
success "Versión: $NEXT_TAG"
success "Rama:   $BRANCH"
success "Commit: $(git rev-parse --short HEAD)"
echo
