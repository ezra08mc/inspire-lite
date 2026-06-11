# Use Case Diagram

```mermaid
flowchart LR
    Student[Student]
    Admin[Admin / Account Manager]

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
- **Admin / Account Manager** creates, edits, resets, and removes user accounts.
- For this project implementation, the application focus is the student task tracker plus account management.
