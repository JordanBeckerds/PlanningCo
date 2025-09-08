# PLanningCo

PLanningCo is a web-based planning and scheduling application designed to manage employee timetables efficiently. It provides a clean interface to view and edit schedules, with data stored in a MySQL database.

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Features](#features)
3. [Technologies](#technologies)
4. [Installation](#installation)
5. [Database Setup](#database-setup)
6. [Usage](#usage)
7. [Folder Structure](#folder-structure)
8. [Contributing](#contributing)
9. [License](#license)

---

## Project Overview

PLanningCo allows administrators and team members to manage employee working hours:

* View and edit schedules for each day of the week.
* Organize shifts in a clear timetable layout.
* Search for employees by name.

All data is stored in a MySQL database using the included schema.

---

## Features

* Responsive timetable view (rows = employees, columns = days of the week).
* Search/filter employees by name.
* Data persistence with MySQL.
* Database schema included (`schema.sql`) for quick setup.

---

## Technologies

* **Backend**: PHP
* **Database**: MySQL
* **Frontend**: HTML, CSS, JavaScript

---

## Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/JordanBeckerds/PLanningCo.git
   cd PLanningCo
   ```

2. Set up the database:

   * Create a new MySQL database.
   * Import the schema:

     ```bash
     mysql -u your_username -p your_database < schema.sql
     ```

3. Configure the database connection (update credentials in `config.php`).

4. Start the PHP server:

   ```bash
   php -S localhost:8000
   ```

5. Open `http://localhost:8000` in your browser.

---

## Database Setup

The `schema.sql` file contains all necessary tables, including:

* `users`: stores system users and their credentials.
* `employees`: stores employee details.
* `schedules`: stores working hours for each employee per day.

> Ensure your MySQL user has permission to create tables and insert data.

---

## Usage

1. Open PLanningCo in your browser.
2. Log in with your user account (if applicable).
3. Navigate the timetable to view or edit schedules.
4. Use the search bar to filter employees by name.

---

## Folder Structure

```
PLanningCo/
├─ schema.sql         # Database structure
├─ index.php          # Main application
├─ config.php         # Database configuration
├─ assets/
│   ├─ css/           # Stylesheets
│   ├─ js/            # JavaScript scripts
├─ uploads/           # Optional folder for files
└─ README.md
```

---

## Contributing

1. Fork the repository.
2. Create a new branch:

   ```bash
   git checkout -b feature/your-feature
   ```
3. Commit your changes:

   ```bash
   git commit -am "Add feature"
   ```
4. Push to your branch and create a pull request.

---

## License

This project is open-source under the [GPL-3.0 License](https://opensource.org/licenses/GPL-3.0).


