# Crewly — ER Diagram

The full, current Entity–Relationship Diagram is in the README:
**[README → Database (ERD)](README.md#database-erd)**.

It covers all 10 application tables:
- users, departments, employees, leave_requests
- skills and the employee_skill pivot
- holidays, settings, activity_logs and notifications

It also shows the self-referencing `departments.parent_id`, the soft deletes, and what happens on delete.

The diagram previously in this file showed only the first four tables of the original Blade version.
