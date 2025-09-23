#!/bin/bash
set -e

# Copy the Docker-specific database configuration if it exists
if [ -f "/var/www/html/docker/config/database.php" ]; then
  cp /var/www/html/docker/config/database.php /var/www/html/config/database.php
  echo "Using Docker-specific database configuration."
fi

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
until mysqladmin ping -h"$DB_HOST" --silent; do
  echo "Waiting for database connection..."
  sleep 2
done

# Set proper permissions
chown -R www-data:www-data /var/www/html

# Start Apache in foreground
exec apache2-foreground