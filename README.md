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
├── index.php                         # Application routing gateway controller
├── login.php                         # Glassmorphism login engine with input tracking
├── logout.php                        # Session lifecycle cleanup script
├── .env.example                      # Environment configuration template
├── .gitignore                        # Excludes editor logs, secrets, Syncthing
├── LICENSE                           # MIT Open Source permissions file
├── database.sql                      # Database schema export
│
├── config/
│   └── db.php                        # Production PDO database client instance
│
├── assets/
│   ├── css/
│   │   ├── style.css                 # Core CSS & glassmorphism design tokens
│   │   └── dashboard.css             # Dashboard specific styling
│   ├── img/
│   │   ├── logo.png                  # Centered portal brand header (300x100px)
│   │   ├── logo1.png                 # Alternative logo variant
│   │   ├── background.png            # Blurred glassmorphism backdrop
│   │   └── background1.png           # Alternative background
│   └── js/
│       └── main.js                   # Vanilla JS for async UI & AJAX interactivity
│
├── admin/
│   └── dashboard.php                 # Administrative system controls, user provisioning & portal configuration
│
├── staff/
│   └── dashboard.php                 # Academic records management, enrollment registry & operations
│
├── lecturer/
│   └── dashboard.php                 # Instructor grade management, course syllabus assignment & evaluation
│
└── student/
    ├── dashboard.php                 # Main student portal (Hero banner & Quick Actions)
    ├── profil/
    │   └── index.php                 # Student biodata & profile viewer
    ├── pusat-informasi/
    │   └── index.php                 # Campus information bulletin hub
    ├── perkuliahan/
    │   ├── jadwal.php                # Course schedules viewer
    │   ├── krs.php                   # Course registration system
    │   ├── khs.php                   # Semester academic results
    │   ├── presensi.php              # Attendance verification tracker
    │   ├── tugas.php                 # Assignment submission panel
    │   ├── bimbingan.php             # Academic counseling entry point
    │   ├── kartu-mahasiswa.php       # Digital student ID card generator
    │   └── transkrip.php             # Cumulative academic transcript viewer
    ├── kemahasiswaan/
    │   ├── beasiswa.php              # Scholarship application tracker
    │   ├── prestasi.php              # Student achievements log
    │   ├── kompetisi.php             # Academic & non-academic competitions registry
    │   ├── organisasi.php            # Campus student organizations portal
    │   ├── kkt.php                   # Integrated work lecture routing
    │   ├── praktik-lapangan/
    │   │   ├── pembimbing.php        # Internship field supervisor assignment
    │   │   └── seminar.php           # Field internship presentation evaluation
    │   ├── skripsi-tesis/
    │   │   ├── proposal.php          # Thesis topic & proposal submission
    │   │   ├── pembimbingan.php      # Advisor consultation logs tracker
    │   │   ├── hasil.php             # Research results defense panel
    │   │   └── ujian-akhir.php       # Final thesis viva examination
    │   └── wisuda/
    │       ├── informasi.php         # Graduation requirements information hub
    │       ├── daftar.php            # Graduation registry application
    │       └── validasi.php          # Clearance validation checklists
    ├── perpustakaan/
    │   └── index.php                 # Digital library integration entry point
    ├── fasilitas/
    │   ├── kalender.php              # Academic activities event calendar
    │   ├── email.php                 # Official institutional student mail access
    │   └── wifi.php                  # Campus Wi-Fi single sign-on access manager
    └── administrasi/
        ├── billing.php               # Tuition fees payment registry
        └── cuti-pindah/
            ├── cuti.php              # Academic leave request processing
            ├── pindah-prodi.php      # Internal major transfer gateway
            └── pindah-keluar.php     # External university transfer processing
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
