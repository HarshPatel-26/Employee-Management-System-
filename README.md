# 📊 Company Portal Database Setup Guide

## 🚀 Quick Setup using phpMyAdmin

### Step 1: Open phpMyAdmin
1. Start your **XAMPP Control Panel**
2. Make sure **Apache** and **MySQL** services are running ✅
3. Click **Admin** button next to MySQL, or go to: `http://localhost/phpmyadmin/`

### Step 2: Import Database
1. In phpMyAdmin, click **"Import"** tab at the top
2. Click **"Choose File"** button
3. Select `database/database_setup.sql` from the project folder.
4. Click **"Import"** button at the bottom
5. Wait for the success message ✅

### Step 3: Verify Setup
After import, you should see:
- **Database:** `company_portal` created
- **12 Tables** with sample data
- **Views, Procedures, and Triggers** installed

---

## 📋 Database Structure

### 🔑 Main Tables Created:
1. **users** - Employee/Admin/Boss accounts
2. **teams** - Company departments/teams  
3. **attendance** - Daily attendance records
4. **leaves** - Leave requests and approvals
5. **tasks** - Daily tasks management
6. **projects** - Company projects
7. **project_steps** - Project milestones
8. **ideas** - Employee suggestions
9. **transfers** - Department transfer requests
10. **notifications** - System notifications
11. **employee_performance** - Performance reviews
12. **payroll** - Salary and payment records

### 👥 Sample Users Created:
| Role | Name | Email | Password |
|------|------|-------|----------|
| Admin | System Admin | admin@example.com | Admin@123 |
| Boss | Company Boss | boss@example.com | Boss@123 |
| Employee | John Doe | employee@company.com | Employee123 |
| Employee | Jane Smith | jane.smith@company.com | Employee123 |
| Employee | Mike Johnson | mike.johnson@company.com | Employee123 |
| Employee | Sarah Wilson | sarah.wilson@company.com | Employee123 |
| Employee | David Brown | david.brown@company.com | Employee123 |

### 🏢 Teams Created:
- Development
- Design  
- Marketing
- Sales
- HR
- Finance
- Support
- Operations

---

## 🔧 Advanced Features Included

### 📊 Database Views:
- `employee_summary` - Complete employee overview
- `project_progress` - Real-time project completion status

### 🔄 Stored Procedures:
- `GetMonthlyAttendance(user_id, month)` - Monthly attendance report
- `GetEmployeeMetrics(employee_id)` - Performance metrics

### ⚡ Triggers:
- Auto-update project progress when steps completed
- Auto-create notifications for leave approvals
- Timestamp updates for record changes

### 📈 Performance Optimizations:
- Database indexes for faster queries
- Foreign key constraints for data integrity
- Proper UTF-8 encoding for international support

---

## 🗂️ Sample Data Included

### ✅ Pre-loaded Data:
- **5 Employee accounts** with different roles
- **8 Company teams** with managers
- **6 Active projects** with progress tracking
- **Sample attendance** records for current period
- **Leave requests** in various states
- **Daily tasks** for each employee
- **Employee ideas** and suggestions
- **System notifications** for testing
- **Project steps** with completion status

---

## 🔐 Security Features

### 🛡️ Built-in Security:
- **Password hashing** using PHP's secure bcrypt
- **Foreign key constraints** prevent orphaned records
- **Role-based access** control (Admin/Boss/Employee)
- **SQL injection** protection via prepared statements
- **Data validation** at database level

---

## 🚨 Troubleshooting

### Common Issues:

**❌ "Database already exists" error:**
- Solution: Delete existing `company_portal` database first, then import

**❌ "Import timeout" error:**
- Solution: Increase `max_execution_time` in php.ini or import in smaller chunks

**❌ "Permission denied" error:**
- Solution: Make sure MySQL service is running and you have admin privileges

**❌ "Trigger creation failed":**
- Solution: Your MySQL version might not support all features. The application will still work without triggers.

---

## ✅ Verification Steps

After successful import, verify by:

1. **Check Tables**: Go to `company_portal` database, verify all 12 tables exist
2. **Check Data**: Click on `users` table, you should see 7 user records
3. **Test Login**: Try logging into the application with sample credentials
4. **Check Relationships**: Verify foreign keys are working properly

---

## 🔄 Updating Configuration

The application is pre-configured for this database. If you need to change settings:

1. Edit `app/config.php` or set the corresponding variables in `.env`:
```php
'db_host' => '127.0.0.1',    // Your MySQL host
'db_name' => 'company_portal', // Database name
'db_user' => 'root',         // MySQL username  
'db_pass' => '',             // MySQL password
```

---

## 📞 Need Help?

If you encounter any issues:
1. Check XAMPP Apache/MySQL are running
2. Verify file path is correct
3. Check phpMyAdmin error messages
4. Ensure sufficient disk space
5. Try importing smaller sections if needed

---

**🎉 Your database is now ready to use with the Company Portal application!**

## Project structure and local web setup

The web server document root must be the project's `public/` directory. With
XAMPP, configure the Apache virtual host's `DocumentRoot` to point to
`<project-folder>/public`; do not serve the repository root. The supplied
`public/.htaccess` disables directory listings.

```text
app/                 Application configuration, libraries, and view partials
database/            SQL setup files and database configuration
public/              Web document root, PHP endpoints, CSS, JavaScript, and SVGs
scripts/             Local database and maintenance utilities
storage/             Local working files
.env                 Local environment settings (not committed)
```

Database setup files are under `database/`. Application PHP configuration is
in `app/config.php`; local environment values can be kept in `.env`.
