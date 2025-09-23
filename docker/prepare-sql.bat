@echo off
echo Preparing SQL file for Docker...

:: Check if the SQL file exists
if not exist "..\fourjs_db (9).sql" (
    echo Error: SQL file not found.
    exit /b 1
)

:: Copy the SQL file to a Docker-friendly name
copy "..\fourjs_db (9).sql" "..\docker\init.sql"

echo SQL file prepared successfully.
echo The SQL file has been copied to docker/init.sql

pause