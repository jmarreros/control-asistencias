#!/bin/bash

# Script para empaquetar el proyecto para subir al servidor
# Excluye: node_modules, .env, public/hot, .git, archivos temporales

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_NAME="$(basename "$PROJECT_DIR")"
OUTPUT="$PROJECT_DIR/../${PROJECT_NAME}-deploy-$(date +%Y%m%d-%H%M).zip"

echo "Empaquetando $PROJECT_NAME..."

cd "$PROJECT_DIR"

zip -r "$OUTPUT" . \
    --exclude "*/.*" \
    --exclude ".*" \
    --exclude "node_modules/*" \
    --exclude "env.production.txt" \
    --exclude "public/hot" \
    --exclude "storage/logs/*.log" \
    --exclude "storage/framework/cache/data/*" \
    --exclude "storage/framework/sessions/*" \
    --exclude "storage/framework/views/*" \
    --exclude "empaquetar.sh" \
    --exclude "database/*.sqlite" \
    --exclude "database/*.sqlite-shm" \
    --exclude "database/*.sqlite-wal"

# Agregar carpetas vacías requeridas por Laravel en producción
zip "$OUTPUT" \
    storage/framework/sessions/ \
    storage/framework/views/ \
    storage/framework/cache/ \
    storage/framework/cache/data/ \
    storage/logs/

echo ""
echo "Archivo generado: $(basename "$OUTPUT")"
echo "Tamaño: $(du -sh "$OUTPUT" | cut -f1)"
