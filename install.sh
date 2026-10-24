#!/usr/bin/env bash
# Installs this payroll app on top of a fresh Laravel 11 project and sets up MySQL.
#
# Usage:   ./install.sh [target-dir]        (default target: ../payroll-app)
# Needs:   PHP 8.2+, Composer, a running MySQL/MariaDB server.
# Env:     DB_NAME (payroll)  DB_USER (root)  DB_PASS ()  DB_HOST (127.0.0.1)  DB_PORT (3306)
set -euo pipefail

SRC="$(cd "$(dirname "$0")" && pwd)"
TARGET="${1:-$SRC/../payroll-app}"
DB_NAME="${DB_NAME:-payroll}"; DB_USER="${DB_USER:-root}"; DB_PASS="${DB_PASS:-}"
DB_HOST="${DB_HOST:-127.0.0.1}"; DB_PORT="${DB_PORT:-3306}"

if [ ! -f "$TARGET/artisan" ]; then
  echo "==> Creating a fresh Laravel 11 project in $TARGET"
  composer create-project laravel/laravel "$TARGET" "^11.0" --no-interaction
fi

echo "==> Copying payroll app files"
# Overlay everything except this script and docs onto the Laravel project
(cd "$SRC" && tar --exclude=install.sh --exclude=README.md -cf - .) | (cd "$TARGET" && tar -xf -)
rm -f "$TARGET/resources/views/welcome.blade.php" "$TARGET/tests/Feature/ExampleTest.php" "$TARGET/tests/Unit/ExampleTest.php"

cd "$TARGET"

echo "==> Configuring MySQL in .env"
[ -f .env ] || cp .env.example .env
set_env() { # key value
  if grep -qE "^#? ?$1=" .env; then sed -i -E "s|^#? ?$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi
}
set_env DB_CONNECTION mysql
set_env DB_HOST "$DB_HOST"
set_env DB_PORT "$DB_PORT"
set_env DB_DATABASE "$DB_NAME"
set_env DB_USERNAME "$DB_USER"
set_env DB_PASSWORD "$DB_PASS"
set_env APP_NAME '"Payroll"'
set_env PAYROLL_COMPANY_NAME '"Your Company Ltd."'
set_env PAYROLL_CURRENCY_SYMBOL '"$"'
php artisan key:generate --force >/dev/null

echo "==> Creating database '$DB_NAME' (if it does not exist)"
if command -v mysql >/dev/null 2>&1; then
  MYSQL_PWD="$DB_PASS" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" \
    -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    || echo "!! Could not create the database automatically. Create it yourself, then re-run."
else
  echo "!! mysql client not found. Create the database '$DB_NAME' yourself, then continue."
fi

echo "==> Running migrations and demo seed data"
php artisan config:clear >/dev/null
php artisan migrate:fresh --seed --force

echo
echo "Done. Start the app with:  cd $TARGET && php artisan serve"
echo "Admin login:     admin@payroll.test / password"
echo "Employee login:  olivia@payroll.test / password"
echo "Remove the demo seed data and change these passwords before real use."
