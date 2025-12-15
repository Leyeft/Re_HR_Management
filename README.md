[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/lxL_zZuw)
## 🚀 Project Setup Flow

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

A[Project Documentation] --> B[Source Code Repository]
    B --> C[HR Management System]
    C --> D[https://github.com/Leyeft/HR-Management-System]
