[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/lxL_zZuw)
## 🚀 Project Setup Flow

```mermaid
# HR Management System

## Project Description
A web-based HR Management System built with Laravel 12.
The system manages employees, departments, and leave requests with an approval workflow.

## Tech Stack
- Backend: Laravel 12
- Database: MySQL
- Frontend: Blade (server-rendered views)
- Authentication: Laravel built-in authentication

## Features
- User authentication
- Employee management (CRUD)
- Department management (CRUD)
- Leave request submission
- Leave approval / rejection by Head of Department
- Leave history tracking
- Role-based access (Employee, Head, HR, Admin – partial)

## Database
- Users
- Employees
- Departments
- Leave Requests

Relationships are enforced using foreign keys and migrations.

flowchart TD
    A[Clone Repository] --> B[Install Backend Dependencies<br/>composer install]
    B --> C[Install Frontend Dependencies<br/>npm install]
    C --> D[Build Frontend Assets<br/>npm run build]
    D --> E[Create .env File<br/>cp .env.example .env]
    E --> F[Configure Database Credentials]
    F --> G[Generate App Key<br/>php artisan key:generate]
    G --> H[Run Migrations<br/>php artisan migrate]
    H --> I[Start Server<br/>php artisan serve]
    I --> J[Access App<br/>http://127.0.0.1:8000]

A[Project Documentation] --> B[Source Code Repository]
    B --> C[HR Management System]
    C --> D[https://github.com/Leyeft/HR-Management-System]
