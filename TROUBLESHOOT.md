## 🛠 Common Development Problems & Fixes

```mermaid
flowchart TD

    A[Login Error: HTTP 500] --> B[Error: Password does not use Bcrypt]
    B --> C{How was user created?}

    C -->|Inserted manually| D[Password stored as plain text]
    C -->|Seeder without Hash::make| D

    D --> E[Laravel refuses authentication]

    E --> F[Fix Option 1: Register via UI]
    F --> G[Password auto-hashed]
    G --> H[Login works]

    E --> I[Fix Option 2: Manual Fix]
    I --> J[Generate bcrypt hash via Tinker]
    J --> K[Update users table]
    K --> H

    E --> X[❌ Do NOT disable bcrypt]

    H --> L[Next Step: Build Departments CRUD]


    M[Employee Creation Error] --> N[Model Not Found]
    N --> O[Missing namespace in Model]
    O --> P[Laravel expects App\\Models\\Employee]
    P --> Q[Add namespace App\\Models]
    Q --> R[Run composer dump-autoload]
    R --> S[Employee created successfully]

### Key Lessons Learned
- Laravel never stores plain passwords
- Always use `Hash::make()` for passwords
- Every model must declare `namespace App\Models;`
- Authentication failures are often security protections, not bugs
