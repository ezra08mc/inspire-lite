# Student Task Reminder and Assignment Tracker

A simple PHP and MySQL web application for students to manage assignments, deadlines, and task status in one place.

This project keeps the existing login system and refactors the student portal into a task reminder and assignment tracker.

## Features

- Existing login system retained
- Student dashboard with task summary
- Add new tasks
- Edit existing tasks
- Delete tasks
- View all tasks in a table
- Dashboard statistics:
  - Total tasks
  - Completed tasks
  - Pending tasks
  - Nearest deadline
- MySQL database integration
- Simple and functional layout for academic use

## Tech Stack

- PHP
- MySQL
- HTML
- CSS
- JavaScript

## Database

The application uses a `tasks` table with the following fields:

- `id`
- `title`
- `course`
- `deadline`
- `status`
- `created_at`

A ready-to-use SQL schema is included in:

- `tasks_schema.sql`
- `database.sql`

## Installation

### 1. Clone or download the project

Place the project inside your local web server folder, for example:

```bash
C:\xampp\htdocs\inspire-lite
