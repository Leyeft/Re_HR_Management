# HR Management System – ER Diagram

## 1. Overview
This document describes the Entity–Relationship (ER) Diagram for the HR Management System.  
It explains the core database entities, their attributes, and how they relate to each other.  
The design supports employee management, department structure, and leave request workflows.

---

## 2. ER Diagram (Mermaid)

```mermaid
erDiagram
    USER ||--o| EMPLOYEE : has
    DEPARTMENT ||--o{ EMPLOYEE : contains
    EMPLOYEE ||--o{ LEAVE_REQUEST : submits
    DEPARTMENT ||--o{ LEAVE_REQUEST : manages

    USER {
        int id PK
        string name
        string email
        string password
        string role
    }

    EMPLOYEE {
        int id PK
        int user_id FK
        int department_id FK
        string rank
        date date_of_birth
    }

    DEPARTMENT {
        int id PK
        string name
        string description
    }

    LEAVE_REQUEST {
        int id PK
        int employee_id FK
        int department_id FK
        date start_date
        date end_date
        string reason
        string status
        string rejection_reason
        datetime created_at
    }
