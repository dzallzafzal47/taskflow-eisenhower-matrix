# TaskFlow — Eisenhower Task Manager

TaskFlow is a lightweight task management web app built with **native PHP and SQLite**. It combines everyday task management with the **Eisenhower Matrix**, helping users organize work based on urgency and importance while keeping a complete activity history.

![TaskFlow — Eisenhower Task Manager](./screenshot.png)

## Features

- Create, edit, and delete tasks
- Organize tasks using the Eisenhower Matrix
- Task status: **To Do**, **In Progress**, and **Done**
- Priority levels: **Low**, **Medium**, and **High**
- Optional due dates
- Search and filter tasks
- Dedicated responsive Matrix view
- Activity history for task changes
- SQLite database with automatic initialization
- Responsive interface for desktop and mobile
- No frontend framework required

## Eisenhower Matrix

Tasks are grouped into four quadrants based on urgency and importance:

| Quadrant | Urgency | Importance | Action |
| --- | --- | --- | --- |
| **Do** | Urgent | Important | Do it immediately |
| **Decide / Schedule** | Not urgent | Important | Schedule a time to do it |
| **Delegate** | Urgent | Not important | Assign it to someone else |
| **Eliminate** | Not urgent | Not important | Remove or reduce it |

The Matrix page uses a 2×2 layout on desktop and adapts into a single-column layout on smaller screens.

> **Note:** Moving a task to **Eliminate** does not permanently delete it. Permanent deletion is handled separately.

## Activity History

TaskFlow keeps an audit trail of important actions, including:

- Task creation
- Task updates
- Status changes
- Eisenhower quadrant changes
- Task deletion

History records remain available even after a task is permanently deleted.

## Tech Stack

- PHP 8+
- SQLite
- PDO SQLite
- HTML5
- CSS3
- Vanilla browser APIs

## Project Structure

```text
taskflow-eisenhower-matrix/
├── assets/
│   └── style.css
├── data/
│   └── schema.sql
├── includes/
│   ├── db.php
│   └── functions.php
├── .gitignore
├── .htaccess
├── README.md
├── screenshot.png
├── history.php
├── index.php
├── matrix.php
├── task-action.php
└── task-form.php
```

The SQLite database file is created automatically at `data/tasks.sqlite` and is excluded from Git through `.gitignore`.

## Getting Started

### Requirements

- PHP 8 or newer
- PDO SQLite extension enabled

### Run with PHP Built-in Server

Clone the repository and enter the project directory:

```bash
git clone https://github.com/your-username/taskflow-eisenhower-matrix.git
cd taskflow-eisenhower-matrix
```

Start the local development server:

```bash
php -S localhost:8000
```

Then open:

```text
http://localhost:8000
```

### Run with XAMPP or Laragon

Place the project inside the local web root:

- **XAMPP:** `htdocs`
- **Laragon:** `www`

Make sure the `pdo_sqlite` extension is enabled, start Apache, and open the project through localhost.

## Database

TaskFlow uses SQLite, so no separate database server is required.

### `tasks`

Stores task information such as:

- Title
- Description
- Status
- Priority
- Eisenhower quadrant
- Due date
- Created and updated timestamps

### `task_history`

Stores the activity log for each task. The task reference is nullable so historical records can remain available after a task is deleted.

## How It Works

When the application starts for the first time, it checks whether the SQLite database exists. If it does not, TaskFlow automatically creates the database using `data/schema.sql`.

```text
Open TaskFlow
     ↓
Check SQLite database
     ↓
Database missing?
     ↓
Initialize from schema.sql
     ↓
TaskFlow is ready
```

## Roadmap

Future improvements may include:

- User authentication
- Multiple users and workspaces
- Drag-and-drop between Matrix quadrants
- Recurring tasks
- Tags and categories
- Dark mode
- CSV history export

## License

This project is currently distributed without an open-source license.
