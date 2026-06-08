# PlanningCo

Open-source employee schedule management platform. Give any team a clean, web-based timetable — shifts, leave requests, departments, and a role-based dashboard — with zero extra tooling.

---

## Features

### Admin
- **Schedule management** — weekly timetable grid, assign shifts to employees
- **Shift library** — define shifts (name, start/end time, night-shift flag)
- **Department management** — group employees by department, color-coded
- **User management** — create / edit / delete employee and admin accounts
- **Leave request review** — approve or deny requests, Congés Payés (CP) vs. unpaid

### Employee
- **Personal schedule** — view upcoming shifts
- **Leave requests** — submit and track requests (pending / approved / denied)

### Platform
- Role-based access: `admin` and `employee`
- Login with brute-force protection (5 failed attempts → 30-minute lockout)
- Guided install wizard at `/setup/` — no CLI required
- `.env`-based configuration

---

## Tech stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8+ |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Frontend | Tailwind CSS (CDN), Lucide Icons, vanilla JS |
| Hosting | Any PHP shared host |

Zero npm, zero build step.

---

## Quick start

```bash
git clone https://github.com/JordanBeckerds/PlanningCo.git
cd PlanningCo
cp .env.example .env   # fill in your DB credentials
php -S localhost:8000
```

Then visit `http://localhost:8000/setup/` and follow the wizard.

See [INSTALL.md](INSTALL.md) for shared hosting and VPS instructions.

---

## Structure

```
PlanningCo/
├── public/           # All pages (schedule, dashboard, leave, users…)
├── actions/          # Form POST handlers
├── includes/
│   ├── db.php        # PDO connection (env-based, auto-redirects to setup)
│   ├── auth.php      # require_login() / require_admin() / session helpers
│   ├── functions.php # h(), format_date(), flash(), redirect()…
│   ├── head.php      # HTML document opener + <head>
│   ├── header.php    # <body> + navigation header
│   └── footer.php    # closes </body></html>
├── setup/
│   └── index.php     # Guided install wizard
├── sql/
│   └── schema.sql    # Full database schema
├── .env.example
└── README.md
```

---

## License

GPL-3.0 — see [LICENSE](LICENSE).

---

## Author

Jordan Beckerds · [github.com/JordanBeckerds](https://github.com/JordanBeckerds)
