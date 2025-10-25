# 💰 PRCF INDONESIA - Financial Management System

> Sistem Manajemen Keuangan Terintegrasi untuk PRCF Indonesia

## 📚 Table of Contents
1. [Struktur Folder Terorganisir](#-struktur-folder-terorganisir)
2. [Fitur Utama](#-fitur-utama)
3. [Quick Start](#-quick-start)
4. [Dokumentasi Lengkap](#-dokumentasi-lengkap)
5. [Local Setup](includes/README_LOCAL_SETUP.md) *(how to configure SMTP & secrets)*

## 📁 Struktur Folder Terorganisir

Proyek ini telah direstrukturisasi untuk kemudahan maintenance dan skalabilitas:

```
prcf_keuangan/
├── index.php                           # Entry point utama
│
├── 📂 auth/                            # Sistem autentikasi
│   ├── login.php                       # Halaman login
│   ├── register.php                    # Halaman registrasi
│   ├── verify_otp.php                  # Verifikasi OTP Email
│   ├── logout.php                      # Logout handler
│   └── unauthorized.php                # Halaman akses ditolak
│
├── 📂 pages/                           # Halaman aplikasi utama
│   ├── dashboards/                     # Dashboard per role
│   │   ├── dashboard_pm.php            # Project Manager
│   │   ├── dashboard_fm.php            # Finance Manager
│   │   ├── dashboard_sa.php            # Staff Accountant
│   │   └── dashboard_dir.php           # Direktur
│   │
│   ├── proposals/                      # Manajemen proposal
│   │   ├── create_proposal.php         # Buat proposal baru
│   │   ├── review_proposal.php         # Review untuk PM
│   │   ├── review_proposal_fm.php      # Review untuk FM
│   │   ├── review_proposal_dir.php     # Review untuk Direktur
│   │   └── approve_proposal.php        # Approval (2-stage)
│   │
│   ├── reports/                        # Laporan keuangan
│   │   ├── create_financial_report.php # Buat laporan
│   │   ├── validate_report.php         # Validasi (SA)
│   │   ├── approve_report.php          # Approval (FM)
│   │   ├── approve_report_dir.php      # Approval (Direktur)
│   │   └── view_report.php             # Lihat laporan
│   │
│   ├── books/                          # Buku keuangan
│   │   ├── buku_bank.php               # Buku bank
│   │   └── buku_piutang.php            # Buku piutang
│   │
│   ├── projects/                       # Manajemen proyek
│   │   └── manage_projects.php         # Kelola proyek
│   │
│   └── profile/                        # Profil user
│       └── profile.php                 # Edit profil
│
├── 📂 api/                             # API endpoints
│   ├── api_notifications.php           # Notifikasi realtime
│   └── get_proposals.php               # Get proposal data (AJAX)
│
├── 📂 includes/                        # Konfigurasi & shared files
│   ├── config.php                      # Database & app config
│   ├── config.example.php              # Template konfigurasi
│   ├── config_simple.php               # Simple config
│   ├── config_manualOTP.php            # Manual OTP mode
│   ├── maintenance_config.php          # Maintenance mode settings
│   └── maintenance_config.example.php  # Template maintenance config
│
├── 📂 public/                          # Halaman publik
│   ├── maintenance.php                 # Halaman maintenance
│   └── under_construction.php          # Under construction page
│
├── 📂 assets/                          # Static assets
│   ├── js/                             # JavaScript files
│   │   └── realtime_notifications.js   # Notifikasi realtime
│   ├── Maintenance web.json            # Lottie animation
│   └── Under Construction 1.json       # Lottie animation
│
├── 📂 uploads/                         # User uploaded files
│   ├── tor/                            # Terms of Reference documents
│   └── budgets/                        # Budget files
│
├── 📂 docs/                            # Dokumentasi lengkap
│   ├── guides/                         # Panduan setup & usage
│   ├── implementation/                 # Dokumentasi implementasi
│   ├── summaries/                      # Ringkasan fitur
│   └── images/                         # Screenshot & gambar
│
├── 📂 sql/                             # Database files
│   ├── migrations/                     # Migration files
│   └── dumps/                          # Database backups
│
├── 📂 scripts/                         # Utility scripts
│   └── batch/                          # Windows batch scripts
│       ├── restart_apache.bat          # Restart Apache
│       ├── setup_brevo.bat             # Setup email OTP
│       └── start_ngrok.bat             # Start ngrok tunnel
│
└── 📂 tests/                           # Test & debugging files
    ├── test_email.php                  # Test email functionality
    ├── test_whatsapp_otp.php           # Test WhatsApp OTP
    └── test_notifications_api.php      # Test notifications
```

## ✨ Fitur Utama

### 🔐 **Multi-Channel Authentication**
- **Project Manager (PM)**: Buat proposal & laporan
- **Finance Manager (FM)**: Approve proposal (stage 1) & laporan
- **Staff Accountant (SA)**: Validasi laporan keuangan
- **Direktur**: Final approval proposal (stage 2)
> Login menggunakan Email OTP (Gmail App Password). WhatsApp OTP dinonaktifkan.

### 📝 **2-Stage Proposal Approval**
1. **Stage 1**: FM Review → Approve/Reject
2. **Stage 2**: Direktur Final Approval

### 💸 **Financial Reporting**
- Buat laporan keuangan dengan upload bukti
- Validasi oleh Staff Accountant
- Approval bertahap (FM → Direktur)

### 🔔 **Real-time Notifications & OTP Email**
- Notifikasi otomatis untuk setiap aksi
- Read/Unread tracking
- Real-time updates tanpa reload
- Email OTP dikirim menggunakan templated HTML (Gmail SMTP)

### 📱 **OTP Verification**
- **WhatsApp OTP**: Via Fonnte API
- **Email OTP**: Via Brevo SMTP
- **Developer Mode**: Bypass OTP untuk testing

### 🛠️ **Maintenance Mode**
- Toggle on/off maintenance mode
- IP Whitelist untuk admin bypass
- Animated maintenance page

## 🚀 Quick Start

### 1️⃣ **Setup Database**
```bash
# Import database
mysql -u root < sql/dumps/prcf_keuangan_clean.sql

# Atau via phpMyAdmin
# Import: sql/dumps/prcf_keuangan_clean.sql
```

### 2️⃣ **Konfigurasi**
```bash
# Copy config template
copy includes\config.example.php includes\config.php

# Local secrets (SMTP, tokens)
copy includes\config.local.php.example includes\config.local.php

# Update config.local.php with Gmail app password & other overrides
```

### 3️⃣ **Setup OTP (Optional)**
**Email OTP:**
```bash
# Jalankan setup wizard
scripts\batch\setup_brevo.bat

# Atau manual edit config.php
EMAIL_ENABLED = true
BREVO_SMTP_* = 'your_credentials'
```

### 4️⃣ **Developer Mode (Testing)**
```php
// includes/config.php
define('DEVELOPER_MODE', true);
define('SKIP_OTP_FOR_ALL', true);
```

### 5️⃣ **Akses Aplikasi**
```
URL: http://localhost/prcf_keuangan/
```

**Default Users:**
| Role | Email | Password |
|------|-------|----------|
| PM | pm@prcf.id | password |
| FM | fm@prcf.id | password |
| SA | sa@prcf.id | password |
| Direktur | dir@prcf.id | password |

## 📚 Dokumentasi Lengkap

### Setup Guides
- [Setup Guide](docs/guides/SETUP_GUIDE.md) - Panduan lengkap instalasi
- [Email OTP Setup](docs/guides/EMAIL_SETUP_GUIDE.md) - Setup email OTP (Gmail App Password)
- [WhatsApp Setup](docs/SETUP_FONNTE.md) - Setup WhatsApp OTP
- [Ngrok Setup](docs/guides/NGROK_SETUP_GUIDE.md) - Expose local untuk testing

### Feature Guides
- [Maintenance Mode](docs/guides/MAINTENANCE_MODE_GUIDE.md) - Cara enable/disable maintenance
- [Developer Mode](docs/guides/DEVELOPER_MODE_GUIDE.md) - Testing tanpa OTP
- [Project Management](docs/guides/PROJECT_MANAGEMENT_GUIDE.md) - Kelola proyek
- [Role Access Control](docs/guides/ROLE_ACCESS_CONTROL_GUIDE.md) - Permission per role

### Implementation Docs
- [2-Stage Approval](docs/implementation/2_STAGE_APPROVAL_IMPLEMENTATION.md)
- [Notification System](docs/implementation/NOTIFICATION_SYSTEM_FIX.md)
- [Proposal Flow](docs/implementation/PROPOSAL_2STAGE_APPROVAL_FIX.md)

## 🔧 Troubleshooting

### **OTP Email Tidak Terkirim?**
```bash
# Test Email OTP
php tests/test_email.php

# Periksa SMTP log di php_error.log untuk detail
```

### **Error Database Connection?**
- Cek XAMPP MySQL sudah running
- Verify credentials di `includes/config.php`
- Pastikan database `prcf_keuangan` sudah di-import

### **Session Issues dengan Back Button?**
- Sudah ada fix otomatis dengan cache headers
- Clear browser cache jika masih terjadi

### **Maintenance Mode Stuck?**
```php
// Edit includes/maintenance_config.php
define('MAINTENANCE_MODE', false);
```

## 🛡️ Security Features

- ✅ Password hashing dengan `password_hash()`
- ✅ Prepared statements untuk SQL injection prevention
- ✅ Session management dengan role-based access
- ✅ CSRF protection pada forms
- ✅ File upload validation
- ✅ XSS prevention dengan output escaping

## 🌐 Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Tailwind CSS, Alpine.js (optional)
- **Icons**: Font Awesome
- **Animations**: Lottie
- **OTP**: Fonnte API (WhatsApp), Brevo SMTP (Email)

## 📦 Dependencies

### External Services
- [Gmail SMTP](https://support.google.com/accounts/answer/185833) - Email OTP (App Password)
- [Ngrok](https://ngrok.com) - Local tunneling (dev only)

### CDN Resources
- Tailwind CSS
- Font Awesome
- Lottie Web

## 🤝 Contributing

Kontribusi selalu welcome! Silakan:
1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 License

[MIT License](LICENSE) - Feel free to use for your projects!

## 👥 Authors

**PRCF Indonesia Development Team**

## 🙏 Acknowledgments

- Terima kasih kepada semua kontributor
- Inspirasi dari best practices modern web development
- Special thanks to the open-source community

---

**Made with ❤️ for PRCF Indonesia**

*Last Updated: October 2024*

---

## 📧 Support

Butuh bantuan? Hubungi:
- 📧 Email: support@prcf.id
- 📱 WhatsApp: [Contact Admin]
- 📖 Docs: [docs/guides/](docs/guides/)

---

## 🔄 Changelog

### v2.0.0 - October 2024
- ✅ **Major Restructuring**: Organized folder structure
- ✅ **2-Stage Approval**: Implemented dual approval flow
- ✅ **Real-time Notifications**: Added notification system
- ✅ **Maintenance Mode**: Added maintenance page with animations
- ✅ **Developer Mode**: Testing mode without OTP
- ✅ **WhatsApp OTP**: Integrated Fonnte API
- ✅ **Email OTP**: Integrated Brevo SMTP
- ✅ **Session Fixes**: Fixed back button issues
- ✅ **Security Enhancements**: Improved security measures

### v1.0.0 - Initial Release
- Basic authentication system
- Proposal & report management
- Single-stage approval
- Basic notifications

---

💡 **Tip**: Lihat [QUICK_REFERENCE.md](docs/guides/QUICK_REFERENCE.md) untuk panduan cepat semua fitur!

