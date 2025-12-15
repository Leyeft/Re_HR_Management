[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/lxL_zZuw)
## 🚀 Project Setup Flow


# HR Management System

## Project Overview
The HR Management System is a web-based application designed to manage employees, departments,
and leave requests within an organization. It provides an approval workflow for leave requests
and basic role-based access control.

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

## AI Assistance

This project was developed with the assistance of **ChatGPT (OpenAI)** as a development support tool.

ChatGPT was used to:
- Explain Laravel concepts and best practices
- Debug errors and interpret confusing error messages
- Assist in database design and workflow planning
- Help structure documentation and Mermaid diagrams

Like any real-world development collaboration, there were moments of miscommunication, iterative clarification, and “wait… that’s not what I meant” discussions.  
These moments contributed positively to problem-solving, learning, and refining the final system.

All final decisions, implementations, and integrations were reviewed, understood, and executed by the project author.

AI tools were used strictly as a **learning aid and development sidekick**, in accordance with course guidelines.
## Quote of The Project

"Coding is not just about getting things right the first time—it's about laughing at the detours, learning from the bugs, and enjoying the ride." - ChaptGPT


## Link
- Visit link below for the public view
- https://github.com/Leyeft/HR-Management-System


Relationships are enforced using foreign keys and migrations.
```mermaid
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

'''

