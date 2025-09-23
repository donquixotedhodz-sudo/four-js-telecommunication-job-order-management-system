#!/bin/bash

# Setup script for Four JS Telecommunication Job Order Management System Docker environment

echo "Setting up Four JS Telecommunication Job Order Management System Docker environment..."

# Create necessary directories if they don't exist
mkdir -p php

# Prepare SQL file
echo "Preparing SQL file..."
cp "../fourjs_db (9).sql" "init.sql"
if [ $? -ne 0 ]; then
    echo "Error: Could not copy SQL file."
    exit 1
fi

# Check if docker-compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "Error: docker-compose is not installed. Please install Docker and Docker Compose first."
    exit 1
fi

# Check if Docker is running
if ! docker info &> /dev/null; then
    echo "Error: Docker is not running. Please start Docker and try again."
    exit 1
fi

echo "Starting Docker containers..."
cd ..
docker-compose -f docker/docker-compose.yml up -d

echo "Waiting for database to initialize (30 seconds)..."
sleep 30

echo "Setting proper permissions..."
docker exec -it fourjs-app chown -R www-data:www-data /var/www/html

echo "Setup complete!"
echo "You can access the application at: http://localhost:8080"
echo "You can access phpMyAdmin at: http://localhost:8081"
echo "Database credentials:"
echo "  - Database: fourjs_db"
echo "  - Username: fourjs_user"
echo "  - Password: fourjs_password"