<div align="center">
  
  <h1>INSPIRE Lite</h1>
  <h3><b>A Minimalist Campus Management Information System</b></h3>  
  <p>A native, modern academic web portal engineered for high performance and clean data routing.</p>
  
  [![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat)]()
</div>

## 🚀 Overview

**INSPIRE Lite** is a native web framework designed to deliver a better, vanilla alternative to existing university portals. Functioning as a centralized digital campus platform inspired by UNSRAT's academic infrastructure, it streamlines student authentication, profile directories, and semester course metrics with zero framework overhead.

---

## 📂 Project Directory Structure

```text
inspire-lite/
├── index.php                          # Core routing gateway for application requests
├── login.php                          # Authentication engine and session tracking
├── logout.php                         # User session termination and cleanup
├── database.sql                       # Relational database schema export
├── .env                               # Environment secrets and database credentials
├── config/
│   └── db.php                         # Database connection and PDO instance
├── assets/
│   ├── css/
│   │   ├── style.css                  # Core application structural layout
│   │   └── dashboard.css              # Sub-module grid and navigation panel layouts
│   ├── img/
│   │   ├── logo.png                   # Main institutional branding image
│   │   └── background.png             # Application interface background resource
│   └── js/
│       └── main.js                    # Client-side logic and async AJAX requests
├── student/
│   ├── dashboard.php                  # Personal overview, statistics, and quick actions
│   ├── profil/
│   │   ├── index.php                  # View personal biodata (excluding NIM)
│   │   └── edit.php                   # Contact information modification form
│   ├── pusat-informasi/
│   │   ├── pengumuman.php             # Official academic and faculty announcements
│   │   ├── berita.php                 # Campus news articles feed
│   │   └── acara.php                  # Campus events registry and schedule
│   ├── perkuliahan/
│   │   ├── jadwal.php                 # Dynamic daily class schedule viewer
│   │   ├── krs.php                    # Interactive course registration platform
│   │   ├── khs.php                    # Semester grading reports and GPA tracking
│   │   ├── presensi.php               # Attendance logs and percentage analyzer
│   │   ├── tugas.php                  # Assignment deadlines and file uploads
│   │   ├── bimbingan.php              # Academic counseling logs with advisors
│   │   ├── kartu-mahasiswa.php        # Digital student ID generator with QR code
│   │   └── transkrip.php              # Cumulative transcript data compilation
│   ├── kemahasiswaan/
│   │   ├── beasiswa.php               # Financial aid options and application tracker
│   │   ├── prestasi.php               # Competition and award submission log
│   │   ├── kompetisi.php              # Campus-supported event registration
│   │   ├── organisasi.php             # Directory of student clubs and unions
│   │   ├── kkt.php                    # Community service (KKT/KKN) deployment portal
│   │   ├── praktik-lapangan/
│   │   │   ├── pembimbing.php         # Field supervisor matching and details
│   │   │   └── seminar.php            # Internship presentation scheduling
│   │   ├── skripsi-tesis/
│   │   │   ├── proposal.php           # Title defense and proposal submission
│   │   │   ├── pembimbingan.php       # Logbook for progress tracking with advisors
│   │   │   ├── hasil.php              # Research results defense registry
│   │   │   └── ujian-akhir.php        # Final viva examination dashboard
│   │   └── wisuda/
│   │       ├── informasi.php          # Graduation deadlines and guidelines
│   │       ├── daftar.php             # Ijazah data validation and gown registry
│   │       └── validasi.php           # Financial and administrative clearance check
│   ├── perpustakaan/
│   │   └── index.php                  # Book catalog search engine and journal access
│   ├── fasilitas/
│   │   ├── kalender.php               # Visual institutional academic calendar
│   │   ├── email.php                  # Single Sign-On link to official webmail
│   │   └── wifi.php                   # Campus internet access credential manager
│   └── administrasi/
│       ├── billing.php                # Tuition fee (UKT) tracking and virtual accounts
│       └── cuti-pindah/
│           ├── cuti.php               # Academic leave request workflow
│           ├── pindah-prodi.php       # Internal department transfer processing
│           └── pindah-keluar.php      # University withdrawal processing
├── lecturer/
│   ├── dashboard.php                  # Teaching assignments and grading schedules
│   ├── courses/
│   │   ├── index.php                  # List of active assigned subjects
│   │   └── materials.php              # Syllabus upload and file sharing gateway
│   ├── attendance/
│   │   └── input.php                  # Manual entry for student daily attendance
│   ├── grading/
│   │   ├── index.php                  # Grade book overview for assigned classes
│   │   └── submit.php                 # Weight configurations and score locking mechanism
│   ├── advising/
│   │   ├── index.php                  # List of assigned academic advisees
│   │   └── krs-approval.php           # Digital signature and approval for student KRS
│   └── supervision/
│       ├── thesis.php                 # Monitoring dashboard for guided students
│       └── defense.php                # Scoring interface for thesis examinations
├── staff/
│   ├── dashboard.php                  # Workflow queue and real-time operational alerts
│   ├── students/
│   │   ├── registry.php               # Central database for active student tracking
│   │   └── status-control.php         # Administrative drop-out, active, or leave toggles
│   ├── academics/
│   │   ├── scheduling.php             # Core timeline planner for master timetables
│   │   └── room-allocator.php         # Classroom physical capacity coordinator
│   ├── finance/
│   │   └── verify.php                 # Manual check for wire transfers and UKT billing
│   ├── records/
│   │   └── transcripts.php            # Legal transcript production and verification
│   └── verification/
│       └── documents.php              # File validation panel for graduation and leave
└── admin/
    ├── dashboard.php                  # Real-time resource metrics and active sessions
    ├── users/
    │   ├── manage.php                 # User listing and modification panel
    │   └── provision.php              # Account creation engine for staff and teachers
    ├── security/
    │   └── logs.php                   # System-wide audit logs and brute force filters
    ├── settings/
    │   └── calendar.php               # Global semester timeline toggles and dates
    ├── infrastructure/
    │   └── backup.php                 # Database hot-backup control panel
    └── assets/
        └── branding.php               # Global site asset modification panel
```

## System Requirements

- PHP 8.0+
- MySQL / MariaDB
- Web Server (Apache / Nginx)

## Installation

### 1. Clone Repository
```bash
git clone https://github.com/USERNAME/inspire-lite.git
cd inspire-lite
```

### 2. Setup Database
```bash
mysql -u root -p
CREATE DATABASE inspire_lite;
USE inspire_lite;
SOURCE database.sql;
```

### 3. Configure Environment
```bash
copy .env.example .env
```

Edit `.env` with your database credentials:
```env
DB_HOST=localhost
DB_NAME=inspire_lite
DB_USER=root
DB_PASS=
DB_PORT=3306
```

### 4. Run Application
```bash
# Using PHP built-in server
php -S localhost:8000

# Or use Apache/Nginx
```

Access the application at `http://localhost:8000`

## Login Workflow

- **Admin** → Admin dashboard (User management, system configuration)
- **Lecturer** → Lecturer dashboard (Grade input, attendance, course advising)
- **Staff** → Staff dashboard (Academic records, enrollment management)
- **Student** → Student dashboard (Course registration, grades, attendance, campus info, etc.)

## Security

- ⚠️ **Never commit `.env`** – Only `.env.example` should be pushed to GitHub
- Database credentials stored in local `.env` file
- Sensitive files excluded via `.gitignore`

## Development Status

- [x] Folder structure setup
- [x] Login/logout system
- [x] Database schema
- [ ] Complete dashboard per role
- [ ] Course registration module (KRS/KHS)
- [ ] Attendance tracking module
- [ ] Grade input module
- [ ] Student affairs features
- [ ] Student affairs features
