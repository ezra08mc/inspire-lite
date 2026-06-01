```inspire-lite/           # ─── PRIMARY SYNCED ROOT (Shared via Syncthing)
├── .stignore           # Syncthing skip file (Crucial for Linux ↔ Windows)
├── database.sql        # Main raw SQL database schema export
│
├── config/
│   └── db.php          # Dynamic PDO database connector configuration
│
├── public/             # Publicly accessible asset directory
│   ├── css/
│   │   └── style.css   # Single source of truth for minimalist design tokens
│   └── js/
│       └── app.js      # Vanilla JS for async UI interactivity (Notifications/AJAX)
│
├── templates/          # Global layout component partials
│   ├── header.php      # Dynamic layout header & sidebar menu based on user role
│   └── footer.php      # Global layout closure & script injections
│
├── index.php           # Portal Login UI (image_343af0.png equivalent)
├── beranda.php         # Main Application Routing Gate / User Dashboard
├── logout.php          # Session destruction utility script
├── daftar.php          # User registration gateway script
│
├── mahasiswa/          # GATED MEMBER SECTION: STUDENT FEATURES
│   ├── krs.php         # Study Plan System / Rencana Studi
│   ├── khs.php         # Academic Transcript / Hasil Studi
│   ├── absensi.php     # Class Attendance / Presence Tracking
│   ├── kurikulum.php   # Course Timeline / Roadmap
│   └── tugas.php       # Assignment Submissions
│
├── dosen/              # GATED MEMBER SECTION: LECTURER FEATURES
│   ├── dashboard.php   # Overview of taught classes & tasks
│   ├── input_nilai.php # CRUD: Input and update student grades
│   ├── absensi.php     # CRUD: Mark student presence records
│   └── wali_krs.php    # CRUD: Approve/Reject student study plans
│
└── admin/              # GATED MEMBER SECTION: ADMINISTRATOR FEATURES
    ├── dashboard.php   # Overall system metrics dashboard
    ├── mahasiswa.php   # CRUD: Manage student profiles and NIM data
    ├── dosen.php       # CRUD: Manage lecturer profiles and NIP data
    └── berita.php      # CRUD: Post, edit, or delete portal announcements```