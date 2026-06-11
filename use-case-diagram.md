# Use Case Diagram

```mermaid
flowchart LR
    Student[Student]
    Admin[Admin / Lecturer]

    Login((Login))
    Dashboard((View Dashboard))
    ViewTasks((View Tasks))
    AddTask((Add Task))
    EditTask((Edit Task))
    DeleteTask((Delete Task))
    Logout((Logout))

    Student --> Login
    Student --> Dashboard
    Student --> ViewTasks
    Student --> AddTask
    Student --> EditTask
    Student --> DeleteTask
    Student --> Logout

    Admin --> Login
    Admin --> Dashboard
    Admin --> ViewTasks
    Admin --> Logout
```

## Short Description

- **Student** logs in, views the dashboard, manages tasks, and logs out.
- **Admin / Lecturer** is shown only as a generic report actor if your lecturer wants a multi-user diagram.
- For this project implementation, the application focus is the student task tracker.
