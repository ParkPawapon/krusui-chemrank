# Chem Rank / เส้นทางสู่นักเคมี

เว็บสะสม “หยดสารเคมี” สำหรับนักเรียนและครูผู้ดูแลคะแนน พัฒนาเป็น PHP 8+, PDO, Tailwind CSS และ JavaScript แบบ native modules โดยไม่พึ่ง CDN ใน production

## โครงสร้าง

- `public/` web root, front controller, assets
- `app/` controllers, middleware, services, views
- `domain/` entities, repository contracts, rank registry
- `infrastructure/` PDO persistence, database, security
- `database/` schema สำหรับ SQLite และ MySQL
- `tests/` test runner สำหรับ rank/business rules

## ติดตั้ง local

```bash
cp .env.example .env
npm install
npm run build
php bin/migrate.php
php bin/create-teacher.php "ครูซุย" teacher@example.com "change-this-password"
php -S localhost:8080 -t public
```

เปิด `http://localhost:8080`

## Environment

ค่าเริ่มต้นใช้ SQLite ที่ `storage/database.sqlite` เหมาะกับ local/dev ถ้าใช้ MySQL ให้แก้ `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chem_rank
DB_USERNAME=...
DB_PASSWORD=...
```

ตั้ง `APP_DEBUG=false` ใน production และตั้ง `SESSION_SECURE_COOKIE=true` เมื่อใช้ HTTPS

PHP runtime สำหรับ production ต้องเปิด extension `pdo`, `mbstring`, `fileinfo` และ `zip` เพื่อรองรับระบบนำเข้ารายชื่อจากไฟล์ Excel `.xlsx`

## Build assets

```bash
npm run build
```

ไฟล์ CSS production จะอยู่ที่ `public/assets/css/app.css`

## Deploy ผ่าน FTP

1. รัน `npm run build` ก่อน deploy
2. อัปโหลดไฟล์ production ทั้งหมด ยกเว้น `node_modules/`, `.env`, `tests/`, `storage/logs/`, `storage/rate-limit/*.json`, source map และ credential
3. ถ้า hosting ตั้ง document root ได้ ให้ชี้ไปที่ `public/`
4. ถ้าชี้ document root ไม่ได้ ให้ใช้ `.htaccess` ที่ root ซึ่ง rewrite เข้า `public/` และ block โฟลเดอร์ sensitive
5. สร้าง `.env` บน server เอง ไม่ commit credential
6. รัน schema ผ่าน phpMyAdmin/MySQL client หรือ `php bin/migrate.php` ถ้า hosting เปิด CLI
7. สร้าง teacher account แรกด้วย `php bin/create-teacher.php`

## Security

- Prepared statements/PDO ทุก query
- CSRF token ทุก POST form
- `password_hash`/`password_verify`
- Session cookie ใช้ HttpOnly, SameSite และ Secure ตาม env
- Login rate limit แบบ file-backed
- Security headers: CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- ครูเท่านั้นที่เข้า route เพิ่ม/ลด/ลบนักเรียนได้
- นักเรียนเห็นได้เฉพาะ student record ที่ผูกกับ user ของตัวเอง
- Production ไม่แสดง stack trace และ log error ลง `storage/logs/php-error.log`

## Rank registry

Rank logic อยู่ที่ `domain/Rank/RankRegistry.php` จุดเดียว:

- 0-10: ละอองแรก
- 11-25: หยดต้นกำเนิด
- 26-45: สารผสมเริ่มต้น
- 46-70: สารละลายก่อตัว
- 71-100: สารเข้มข้น
- 101-135: ภาวะอิ่มตัว
- 136+: เหนือจุดอิ่มตัว

Asset rank อยู่ที่ `public/assets/ranks/*.webp` และควบคุม path ผ่าน `assetPath` ใน registry เท่านั้น

## Tests

```bash
php tests/run.php
```

ครอบคลุม:

- Boundary rank: `0, 10, 11, 25, 26, 45, 46, 70, 71, 100, 101, 135, 136, 180, 181`
- Add drops
- Subtract drops
- Drops ไม่ต่ำกว่า 0
- Transaction log ทุกการปรับหยด
- Activity log ทุกการเพิ่มนักเรียน ลบนักเรียน และปรับหยดสาร

## Manual QA checklist

- Login teacher แล้วเพิ่มนักเรียนพร้อมเลขประจำตัวนักเรียน 5 หลัก, ชั้น, ห้อง, ปีการศึกษา และรหัสผ่านได้
- Login student ด้วยเลขประจำตัวนักเรียนและรหัสผ่านได้
- Login student แล้วเห็นเฉพาะข้อมูลของตัวเอง
- Student ไม่สามารถเข้า `/teacher`
- Teacher เพิ่ม/ลดหยดแล้ว table update แบบ smooth
- เพิ่มหยดจน rank เปลี่ยนแล้ว level-up modal แสดง
- ลดหยดแล้วใช้ข้อความกลาง ๆ และ drops ไม่ติดลบ
- ลบนักเรียนมี confirmation
- Search/filter ชั้นเรียนทำงาน
- Teacher นำเข้ารายชื่อนักเรียนจากไฟล์ `.xlsx` ได้ โดยมีคอลัมน์ `เลขประจำตัวนักเรียน`, `ชื่อนักเรียน`, `ชั้น`, `ห้อง` และ `รหัสผ่าน` ถ้ามี
- Leaderboard รองรับคะแนนเท่ากัน
- Responsive: mobile 375px, tablet 768px, desktop 1440px

## Docker production-like stack

Stack Docker แยกเป็น `nginx`, `php-fpm`, และ `mysql:8.4` พร้อม OPcache, security headers, healthcheck และ persistent volumes

```bash
cp .env.docker.example .env.docker
# แก้ MYSQL_PASSWORD และ MYSQL_ROOT_PASSWORD ให้เป็นค่าจริง
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker exec app php bin/migrate.php
docker compose --env-file .env.docker exec app php bin/create-teacher.php "ครูซุย" teacher "change-this-password"
```

เปิดเว็บที่ `http://localhost:8080` หรือแก้ `APP_PORT` ใน `.env.docker`

คำสั่งตรวจระบบ:

```bash
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs -f nginx app mysql
```
