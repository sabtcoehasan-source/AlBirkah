#!/bin/sh
set -e

# Render (وفي محلي Docker) يمرّر PORT؛ الافتراضي 8080.
LISTEN_PORT="${PORT:-8080}"

printf 'Listen %s\n' "$LISTEN_PORT" >/etc/apache2/ports.conf

cat >/etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${LISTEN_PORT}>
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
</VirtualHost>
EOF

exec apache2-foreground
