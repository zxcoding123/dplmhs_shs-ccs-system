# Student Management System

### Ateneo de Davao University

## Overview

The **Student Management System (SMS)** is a web-based platform designed to manage and organize student records at **Ateneo de Davao University**. The system allows administrators and authorized personnel to efficiently store, update, and retrieve student information in a centralized database.

The goal of this system is to simplify administrative processes, improve data accuracy, and reduce the need for manual record management.

---

## Features

### Student Information Management

* Add new student records
* Update existing student profiles
* View and manage student data
* Organize students by course, department, and year level

### Record Organization

* Centralized database for student records
* Easy searching and retrieval of student information
* Structured data storage

### Administrative Management

* Manage and maintain student data
* Edit or remove outdated records
* Maintain accurate academic information

### Security

* Authentication system for administrators
* Restricted access to sensitive student information
* Secure session management

---

## Technologies Used

**Frontend**

* HTML
* CSS
* Bootstrap
* JavaScript

**Backend**

* PHP

**Database**

* MySQL

**Development Environment**

* XAMPP (Apache, MySQL, PHP)

---

## System Structure

```
student-management-system/
│
├── assets/             # CSS, JS, images
├── includes/           # Database connection and shared components
├── admin               # Admin management and operations
├── staff               # staff management and operations
├── students/           # Student management and operations
├── database/           # SQL database files
└── index.php           # System entry point
```

---

## Installation

### 1. Clone the Repository

```
git clone https://github.com/yourusername/student-management-system.git
```

### 2. Move the Project

Place the project folder inside your **XAMPP `htdocs` directory**.

```
C:/xampp/htdocs/student-management-system
```

### 3. Setup the Database

1. Open **phpMyAdmin**
2. Create a new database:

```
###REDACTED###
```

3. Import the provided SQL file from the `database` folder.

### 4. Configure Database Connection

Edit the database connection file:

```
includes/conn.php
```

Update the credentials if necessary:

```php
$host = "localhost";
$dbname = "REDACTED";
$username = "root";
$password = "";
```

### 5. Run the System

Start **Apache** and **MySQL** in XAMPP and open:

```
http://localhost/student-management-system
```

---

## User Roles

### Administrator

* Manage student records
* Update student information
* Maintain system data
* Chat with other faculties and students

### Authorized Staff

* View and update student records
* Manage academic information
* Grade students
* Chat with other faculties and students

 ### Verified students

* Pass courseworks and assignments
* View grades
* Chat with faculties and other students

---

## Future Improvements

* Integration with university systems
* To be continued and recreated

---

## License

This project was developed for **academic and institutional purposes** for Ateneo de Davao University.
All rights goes to Ahmad Pandaog Aquino

---

## Author

Developed as part of a **Student Management System project** for Ateneo de Davao University.
All rights goes to Ahmad Pandaog Aquino
