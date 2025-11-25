# MySQL to PostgreSQL Conversion Guide

## 1. Database Connection

### MySQL
```php
$pdo = new PDO(
    "mysql:host=localhost;dbname=waterbill_db;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
```

### PostgreSQL
```php
$pdo = new PDO(
    "pgsql:host=your-host.neon.tech;port=5432;dbname=your_db;sslmode=require",
    "your_user",
    "your_password",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);
```

## 2. Schema Changes

### Table Creation
```sql
-- MySQL
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status ENUM('active','suspended') DEFAULT 'active'
);

-- PostgreSQL
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'suspended'))
);
```

## 3. Query Differences

### LIMIT/OFFSET
```sql
-- Both
SELECT * FROM users LIMIT 10 OFFSET 20;
```

### String Concatenation
```sql
-- MySQL
CONCAT(first_name, ' ', last_name)

-- PostgreSQL
first_name || ' ' || last_name
```

## 4. Common Conversions

| MySQL | PostgreSQL |
|-------|------------|
| `AUTO_INCREMENT` | `SERIAL` |
| `` `column` `` | `"column"` |
| `NOW()` | `CURRENT_TIMESTAMP` |
| `TRUE`/`FALSE` | `true`/`false` (lowercase) |

## 5. Data Migration

1. Export MySQL data:
   ```bash
   mysqldump -u root -p waterbill_db > dump.sql
   ```

2. Convert schema and data using `pgloader`:
   ```bash
   pgloader mysql://user:pass@localhost/waterbill_db postgresql://user:pass@host:5432/waterbill_pg
   ```

3. Or manually:
   - Create tables in PostgreSQL
   - Convert data types
   - Import data

## 6. Code Changes

1. Update all database connections
2. Replace MySQL-specific functions
3. Test thoroughly

## 7. Common Issues
- Case sensitivity in identifiers
- Different date/time handling
- Boolean handling
- Transaction behavior
