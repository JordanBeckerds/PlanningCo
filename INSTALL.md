# PlanningCo — Installation Guide

---

## 1. Local development

### Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.4+
- Git

```bash
git clone https://github.com/JordanBeckerds/PlanningCo.git
cd PlanningCo
cp .env.example .env
# Edit .env with your DB credentials
php -S localhost:8000
```

Open `http://localhost:8000/setup/` to run the wizard.

After setup:
- App: `http://localhost:8000/public/`
- Login: `http://localhost:8000/public/login.php`

---

## 2. Shared hosting (OVH, Hostinger, InfinityFree…)

1. Download repo as ZIP → upload and extract to `public_html/`
2. Create a MySQL database in your host control panel
3. Copy `.env.example` to `.env`, fill in credentials
4. Visit `https://yoursite.com/setup/`

**Common issues:**

| Problem | Fix |
|---------|-----|
| Blank page | Enable PHP 8+ in host panel; check error logs |
| DB connection fails | Try `127.0.0.1` instead of `localhost` |
| `.env` not found | Some FTP clients hide dotfiles — make sure it uploaded |

---

## 3. VPS (nginx + PHP-FPM)

```nginx
server {
    listen 80;
    server_name yoursite.com;
    root /var/www/planningco/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(env|installed|git) { deny all; }
}
```

HTTPS: `certbot --nginx -d yoursite.com`

---

## Environment variables

| Variable | Description | Default |
|----------|-------------|----------|
| `DB_HOST` | Database host | `localhost` |
| `DB_NAME` | Database name | `timetable_system` |
| `DB_USER` | Database user | `root` |
| `DB_PASS` | Database password | *(empty)* |
| `APP_URL` | Full URL of your installation | `http://localhost:8000` |

---

## Re-running setup

```bash
rm .installed
```

Then visit `/setup/`. **Warning: re-imports the schema — may overwrite data.**
