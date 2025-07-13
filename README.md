
# 📚 PHP & MySQL Learning Management System (LMS)

![PHP](https://img.shields.io/badge/Backend-PHP-blue)
![MySQL](https://img.shields.io/badge/Database-MySQL-yellow)
![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)

A robust and user-friendly **Learning Management System (LMS)** built with **PHP** and **MySQL** to streamline online learning. This platform allows **teachers** to upload topics with video lectures and PDF notes, while **students** can access content and engage via a **Q&A system**. An **admin dashboard** allows full platform control and moderation.

---

## 📑 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Setup & Installation](#setup--installation)
- [Usage Guide](#usage-guide)
- [Default Credentials](#default-credentials)
- [Project Structure](#project-structure)
- [Security Notes](#security-notes)
- [Contributing](#contributing)
- [License](#license)

---

## ✅ Features

### 👥 User Roles
- **Student**: Access learning content, post questions.
- **Teacher**: Upload and manage topics (video + PDF), reply to questions.
- **Admin**: Manage users, topics, and moderate Q&A.

### 📚 Topic Management
- Text, embedded video (YouTube/local MP4), and PDF notes.
- Easy categorization and dashboard display.

### 💬 Q&A System
- Students post questions under each topic.
- Teachers & admins can respond and manage all interactions.

### 📁 File Uploads
- Video & PDF files are uploaded to the server.
- File paths are stored and managed via the database.

### 🧠 Smart Dashboards
- **Student Dashboard**: All topics, questions, and downloads.
- **Teacher Dashboard**: Uploaded topics and question replies.
- **Admin Dashboard**: Full overview of platform activity.

### 🔐 Security
- Passwords hashed via `password_hash()`.
- SQL Injection protection using prepared statements.
- Role-based access control.
- Input sanitization on forms.

---

## 💻 Tech Stack

| Layer        | Technology         |
|--------------|--------------------|
| Backend      | PHP (Native)       |
| Database     | MySQL              |
| Frontend     | HTML5, CSS3        |
| Server       | Apache (via XAMPP) |

---

## 🧰 Prerequisites

- [XAMPP](https://www.apachefriends.org/index.html) (includes Apache, MySQL, PHP)
- Web browser (Chrome, Firefox, etc.)
- Git (optional but recommended)

---

## 🚀 Setup & Installation

### 1. Clone the Repository

Navigate to your `htdocs` folder (usually in `C:\xampp\htdocs\`) and run:

```bash
git clone https://github.com/muhammad-un/php-mysql-learning-platform.git
cd php-mysql-learning-platform
```

### 2. Database Setup

- Start **MySQL** from XAMPP.
- Go to `http://localhost/phpmyadmin`
- Create a new database named:
  ```
  learning_platform
  ```
- Open `database.sql` from the project root, copy contents, and run it in phpMyAdmin → SQL tab.

### 3. Configure Database Connection

Edit `includes/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // If password is set, enter here
define('DB_NAME', 'learning_platform');
```

### 4. PHP Settings for File Uploads (Optional)

Edit `php.ini` file (via XAMPP Control Panel → Apache → Config):

```ini
upload_max_filesize = 200M
post_max_size = 200M
memory_limit = 256M
max_execution_time = 360
max_input_time = 360
```

Restart Apache after saving changes.

### 5. Start Apache & MySQL

Ensure both are running in XAMPP.

### 6. Run the Application

Go to:
```
http://localhost/php-mysql-learning-platform/
```

---

## 🧪 Usage Guide

### 👤 Register & Login

- New users can register as **Student** or **Teacher**.
- Admin can login directly using default credentials.

### 🧑‍🎓 Student Dashboard

- View uploaded topics
- Watch videos & download PDFs
- Post questions for teachers

### 👨‍🏫 Teacher Dashboard

- Upload topics (video + PDF)
- View student questions and reply

### 🛠️ Admin Dashboard

- View/manage all users
- Oversee topics and Q&A moderation
- Change user roles, delete content

---

## 🔐 Default Credentials

Use this to login as admin initially:

```
Email: admin@example.com
Password: adminpass
```

👉 **Important:** Change password immediately after first login.

---

## 📂 Project Structure

```
php-mysql-learning-platform/
├── admin/                 → Admin dashboard & controls
├── includes/              → DB connection, auth, helpers
├── student/               → Student dashboard & Q&A
├── teacher/               → Teacher topic uploads & Q&A
├── uploads/
│   ├── pdf/               → PDF files
│   └── videos/            → Video lectures (MP4)
├── index.php              → Entry point
├── login.php              → Login page
├── register.php           → Registration form
├── logout.php             → Logout script
├── style.css              → Global styling
└── database.sql           → SQL schema and default data
```

---

## 🛡️ Security Notes

This project includes:
- `password_hash()` for secure password storage.
- Prepared statements to prevent SQL injection.
- Basic session handling and role-based access control.
- Input validation & sanitization.

### 🔐 For Production Use:
- Add CSRF tokens
- Enable HTTPS
- Add audit logs
- Use environment variables for DB credentials
- Set proper file permissions

---

## 🤝 Contributing

Contributions are welcome! To contribute:

```bash
# Fork & clone the repository
git checkout -b feature/your-feature
# Make your changes
git commit -m "feat: Add X feature"
git push origin feature/your-feature
# Create a Pull Request
```

---

## 📄 License

This project is licensed under the **MIT License**.  
You are free to use, modify, and distribute this project for both personal and commercial purposes.
