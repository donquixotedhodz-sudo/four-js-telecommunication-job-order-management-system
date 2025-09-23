@echo off
echo Setting up Four JS Telecommunication Job Order Management System Docker environment...

:: Create necessary directories if they don't exist
if not exist php mkdir php

:: Prepare SQL file
echo Preparing SQL file...
copy "..\fourjs_db (9).sql" "init.sql"
if %ERRORLEVEL% neq 0 (
    echo Error: Could not copy SQL file.
    exit /b 1
)

:: Check if Docker is running
docker info > nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo Error: Docker is not running. Please start Docker and try again.
    exit /b 1
)

echo Starting Docker containers...
cd ..
docker-compose -f docker/docker-compose.yml up -d

echo Waiting for database to initialize (30 seconds)...
timeout /t 30 /nobreak > nul

echo Setting proper permissions...
docker exec -it fourjs-app chown -R www-data:www-data /var/www/html

echo Setup complete!
echo You can access the application at: http://localhost:8080
echo You can access phpMyAdmin at: http://localhost:8081
echo Database credentials:
echo   - Database: fourjs_db
echo   - Username: fourjs_user
echo   - Password: fourjs_password

pause