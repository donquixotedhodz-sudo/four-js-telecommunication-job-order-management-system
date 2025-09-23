# Four JS Telecommunication Job Order Management System - Docker Setup

This directory contains Docker configuration files to run the Four JS Telecommunication Job Order Management System in a containerized environment.

## Prerequisites

- [Docker](https://www.docker.com/get-started) installed on your system
- [Docker Compose](https://docs.docker.com/compose/install/) installed on your system

## Services

The Docker setup includes the following services:

1. **Web Application (PHP/Apache)** - Runs the PHP application with Apache web server
2. **MySQL Database** - Stores application data
3. **phpMyAdmin** - Web interface for database management

## Configuration

- Web application is accessible at: http://localhost:8080
- phpMyAdmin is accessible at: http://localhost:8081
- MySQL database is accessible on port 3306

## Database Credentials

- **Database Name**: fourjs_db
- **Username**: fourjs_user
- **Password**: fourjs_password
- **Root Password**: root_password

## Getting Started

1. Navigate to the project root directory:
   ```
   cd /path/to/four-js-telecommunication-job-order-management-system
   ```

2. Start the Docker containers:
   ```
   docker-compose -f docker/docker-compose.yml up -d
   ```

3. Access the application at http://localhost:8080

4. To access phpMyAdmin, go to http://localhost:8081
   - Server: db
   - Username: fourjs_user or root
   - Password: fourjs_password or root_password

## Stopping the Application

To stop the Docker containers:
```
docker-compose -f docker/docker-compose.yml down
```

To stop and remove all data (including database volume):
```
docker-compose -f docker/docker-compose.yml down -v
```

## Troubleshooting

- If you encounter permission issues, you may need to adjust file permissions in the container:
  ```
  docker exec -it fourjs-app chown -R www-data:www-data /var/www/html
  ```

- To view logs:
  ```
  docker-compose -f docker/docker-compose.yml logs -f
  ```

- To access the container shell:
  ```
  docker exec -it fourjs-app bash
  ```