# iCloud Checker System - Laravel 11

ระบบตรวจสอบ IMEI/Serial สำหรับ iPhone, iPad, MacBook
โดยใช้ ifreeicloud.co.uk API

## Requirements
- PHP 8.2+
- Laravel 11
- MySQL 8+
- Composer

## Installation

```bash
# 1. สร้าง Laravel project ใหม่
composer create-project laravel/laravel icloud-checker

# 2. คัดลอกไฟล์ทั้งหมดจาก ZIP นี้ทับ

# 3. ตั้งค่า .env
cp .env.example .env
php artisan key:generate

# 4. ตั้งค่า Database ใน .env
DB_DATABASE=icloud_checker
DB_USERNAME=root
DB_PASSWORD=your_password

# 5. ใส่ API Key
IFREEICLOUD_KEY=your_ifreeicloud_api_key

# 6. Import database
mysql -u root -p icloud_checker < database/schema.sql

# 7. Install Breeze (Auth)
composer require laravel/breeze --dev
php artisan breeze:install blade

# 8. Register Middleware ใน bootstrap/app.php
# ดูไฟล์ bootstrap/middleware_registration.txt

# 9. Clear cache
php artisan config:clear
php artisan cache:clear

# 10. Run
php artisan serve
```

## Login Default
- **URL:** http://localhost:8000
- **Admin Email:** admin@icloudchecker.com
- **Password:** password

## File Structure
```
app/
  Http/
    Controllers/
      CheckController.php         ← ตรวจ IMEI หลัก
      DashboardController.php
      OrderController.php
      CreditController.php
      LanguageController.php
      Admin/
        AdminDashboardController.php
        AdminUserController.php    ← เติมเครดิต, จัดการ user
        AdminServiceController.php ← ตั้งราคา, เพิ่ม service
        AdminOrderController.php
        AdminSettingController.php ← ตั้งค่า API key
    Middleware/
      AdminMiddleware.php
      ActiveUserMiddleware.php
      SetLocale.php
  Models/
    User.php
    Order.php
    Service.php
    CreditTransaction.php
    ApiLog.php
  Services/
    IFreeICloudService.php         ← Core API wrapper
```

## Services (Default)
| Service | Provider ID | ต้นทุน | ราคาขาย |
|---------|-------------|--------|---------|
| iPhone/iPad All-in-One Pro     | 242 | ฿15 | ฿35 |
| iPhone/iPad All-in-One Ultimate| 244 | ฿25 | ฿59 |
| MacBook All-in-One             | 245 | ฿20 | ฿49 |
| iPad All-in-One                | 246 | ฿15 | ฿35 |

**หมายเหตุ:** ปรับ Service ID และราคาได้ในหน้า Admin → Services

## Features
- ✅ Login / Register (Laravel Breeze)
- ✅ ระบบเครดิต (เติม/ตัด/คืน อัตโนมัติ)
- ✅ ตรวจ IMEI/Serial ผ่าน ifreeicloud API
- ✅ แสดงผล FMI, Activation Lock, Blacklist, SIM Lock, MDM
- ✅ ประวัติการตรวจ + filter
- ✅ Admin Dashboard (revenue, profit, top users)
- ✅ Admin: เติมเครดิตให้ user
- ✅ Admin: จัดการ service + ตั้งราคา
- ✅ Admin: ตั้งค่า API key
- ✅ สองภาษา (ไทย/อังกฤษ)
- ✅ API Log ทุก request