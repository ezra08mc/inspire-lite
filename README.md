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

## Structure
```
task-tracker/
│
├── config/
│   └── database.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
│
├── admin/
│   ├── Dashboard
│   ├── Manage Users
│   └── Logout
│
├── student/
│   ├── dashboard.php
│   ├── tasks.php
│   ├── add_task.php
│   ├── edit_task.php
│   └── update_status.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── auth.php
│
├── login.php
├── logout.php
└── database.sql
