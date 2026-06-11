# Grade Management System

Web application for managing academic grades, built with **PHP** and **PostgreSQL**. It allows teachers to manage courses, enroll students, define each course's evaluation scheme, and record grades, automatically computing each student's final grade.

## Features

- Teacher authentication via code and password.
- Management of students, courses, and enrollments by year and term.
- Definition of each course's evaluation structure (assessments with their percentage weight).
- Grade recording and automatic calculation of the final grade as a weighted sum.
- Data integrity enforced with `CHECK` constraints and PostgreSQL *triggers* (grade validation 0.0–5.0, percentage sum ≤ 100%, enrollment required before grading, and cascade deletion when un-enrolling).

## Tech Stack

- **PHP** (PDO) — application logic
- **PostgreSQL** — relational database
- **HTML / CSS** — user interface

## Project Structure

```
proyectofinal/
├── config/          # Database connection settings
├── css/             # Styles
├── db/              # SQL schema, triggers, and seed data (init.sql)
├── includes/        # Authentication and header
├── *.php            # Pages: login, students, courses, grades, report...
└── iniciar.sh       # Startup script
```

## Getting Started

**Requirements:** PHP and PostgreSQL installed.

1. Check the connection settings in `config/database.php` (defaults to user `postgres` and database `registro_notas`).
2. Make the startup script executable and run it:

   ```bash
   chmod +x iniciar.sh
   ./iniciar.sh
   ```

   The script creates the database, applies the schema with its triggers and seed data, and starts the server.
3. Open your browser at **http://localhost:8080**

### Demo Credentials

| Code   | Password |
|--------|----------|
| DOC001 | 1234     |

## Data Model

The system is built on six tables: `docentes`, `estudiantes`, `cursos`, `inscripciones`, `notas`, and `calificaciones`. The Entity-Relationship diagram is in `Modelo_Entidad_Relacion.png` and the full project documentation in `Documentacion_Proyecto_Registro_Notas.pdf`.
