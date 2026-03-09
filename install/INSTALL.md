# PXMBoard Installation Guide

## System Requirements

| Component | Minimum version |
|-----------|----------------|
| PHP | 8.4 |
| MySQL / MariaDB | MySQL 8.4 / MariaDB 10.11 |
| PostgreSQL (alternative) | 14 |
| PHP extensions | `pdo`, `pdo_mysql` / `pdo_pgsql`, `mbstring`, `json` |
| PHP extensions (optional) | `xsl` (XSLT template support), `gd` (profile images) |

---

## 1. Upload Files

Extract the PXMBoard archive to a directory on your server, e.g. `/var/www/pxmboard`.

The repository contains the following top-level layout:

```
pxmboard/
├── config/         ← runtime configuration (written by installer)
├── install/        ← SQL schema and changelog
├── public/         ← web root (point your server here)
├── skins/          ← Smarty templates and compiled cache
└── src/            ← application source code
```

---

## 2. Configure the Web Server

Set the **document root** to the `public/` subdirectory.
Files outside `public/` (config, source, skins, install) must not be accessible via HTTP.

**Apache** (`VirtualHost`):
```apache
DocumentRoot /var/www/pxmboard/public
```

**Nginx** (`server` block):
```nginx
root /var/www/pxmboard/public;
```

---

## 3. Set Write Permissions

The web server process must be able to write to:

```bash
chmod -R 775 skins/pxm/cache/
chmod -R 775 public/images/profile/
```

Also ensure the web server can write `config/pxmboard-config.php` and
`public/pxmboard-basedir.php` during installation:

```bash
chmod 775 config/
chmod 775 public/
```

---

## 4. Create the Database

```sql
CREATE DATABASE pxmboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pxmboard'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON pxmboard.* TO 'pxmboard'@'localhost';
FLUSH PRIVILEGES;
```

---

## 5. Run the Web Installer

Open your browser and navigate to:

```
https://yourdomain/install/install.php
```

The installer guides you through the following steps:

### Database Connection

| Field | Description |
|-------|-------------|
| Database type | MySQL (default) or PostgreSQL |
| Host | Database host, e.g. `localhost` |
| Username | Database user |
| Password | Database password |
| Database name | Name of the database created in step 4 |

### Search Engine

| Option | Description |
|--------|-------------|
| MySQL | Default. Uses MySQL `FULLTEXT` search. No additional setup. |
| ElasticSearch | Optional. Provide host URL, index name, and API key. |

### Template Engine

Select at least one template engine:

| Engine | Requires |
|--------|---------|
| Smarty | Default. Bundled via Composer. |
| XSLT | PHP `xsl` extension must be installed. |

### Admin Account

Enter a username and password for the first administrator account.
The password is stored as a bcrypt hash.

### Initialize Database Schema

Check **"Import SQL schema"** to let the installer create all tables automatically
(`install/sql/pxmboard-mysql.sql`). Skip this step only if you have already
imported the schema manually.

### Finish

After a successful configuration the installer writes:
- `config/pxmboard-config.php` — database and engine settings
- `public/pxmboard-basedir.php` — defines the `BASEDIR` constant

The installer then **deletes itself** (the `public/install/` directory).

---

## 6. First Login

Navigate to your board:

```
https://yourdomain/pxmboard.php
```

Log in with the admin account you created during installation and review the
administration settings — in particular the mail templates under **Administration → Templates**.

---

## Manual Configuration (without installer)

If you prefer to configure the application without the web installer:

1. Copy the configuration template:
   ```bash
   cp config/pxmboard-config.example.php config/pxmboard-config.php
   ```

2. Edit `config/pxmboard-config.php` and fill in your database credentials,
   template engine, and session name.

3. Create `public/pxmboard-basedir.php` with the following content,
   replacing the path with your actual installation directory:
   ```php
   <?php
   define('BASEDIR', '/var/www/pxmboard');
   ```

4. Import the database schema manually:
   ```bash
   mysql -u pxmboard -p pxmboard < install/sql/pxmboard-mysql.sql
   ```

5. Insert an admin user directly into the database:
   ```sql
   INSERT INTO pxm_user (u_username, u_password, u_passwordkey, u_status, u_admin, ...)
   VALUES ('admin', '<bcrypt-hash>', '<random-hex>', 1, 1, ...);
   ```

---

## Troubleshooting

### Blank page / 500 error
- Check the PHP error log.
- Verify that `config/pxmboard-config.php` exists and is readable by PHP.
- Confirm `public/pxmboard-basedir.php` defines `BASEDIR` correctly.

### Template not found
- Ensure `skins/pxm/cache/` is writable by the web server.

### Database connection failed
- Test the credentials with `mysql -u <user> -p <dbname>`.
- Confirm the MySQL/MariaDB service is running.

### Installer already ran / config exists
- If `config/pxmboard-config.php` already exists the installer redirects immediately.
  Remove it to re-run the installer.
