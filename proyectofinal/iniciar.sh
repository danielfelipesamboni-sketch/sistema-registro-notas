#!/bin/bash
cd "$(dirname "$0")"

echo "=== Registro de Notas ==="

# Crear la base de datos si no existe
DB_EXISTS=$(PGPASSWORD=postgres psql -U postgres -tAc "SELECT 1 FROM pg_database WHERE datname='registro_notas'" 2>/dev/null)
if [ "$DB_EXISTS" != "1" ]; then
    echo "Creando base de datos..."
    PGPASSWORD=postgres psql -U postgres -c "CREATE DATABASE registro_notas;"
fi

# Aplicar esquema y datos iniciales (idempotente)
echo "Aplicando esquema y datos iniciales..."
PGPASSWORD=postgres psql -U postgres -d registro_notas -f db/init.sql
echo "Listo."

echo "Servidor corriendo en http://localhost:8080"
echo "Presiona Ctrl+C para detener."
xdg-open http://localhost:8080 &
php -S localhost:8080
