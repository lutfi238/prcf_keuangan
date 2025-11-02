# 🔒 Admin Protection System

## Overview

Sistem proteksi untuk mencegah admin menghapus/memodifikasi akun sendiri atau admin terakhir di sistem.

## 🚨 Proteksi yang Diimplementasikan

### **1. Cannot Edit/Delete Yourself** ❌

Admin **TIDAK BISA**:
- ✅ Edit akun sendiri (nama, email, role, phone)
- ✅ Hapus akun sendiri
- ✅ Reset password sendiri

**Alasan:** Mencegah admin mengunci diri sendiri dari sistem.

**Solusi:** Minta admin lain untuk mengubah akun Anda.

**Visual Indicator:**
```
Actions Column: [🛡️ You]
```

### **2. Last Admin Protection** 🛡️

Jika hanya ada **1 admin** di sistem, admin tersebut **TIDAK BISA**:
- ✅ Di-demote ke role lain (FM, DIR, SA, PM)
- ✅ Dihapus dari sistem

**Alasan:** Sistem **HARUS** memiliki minimal 1 admin untuk management.

**Solusi:** 
1. Buat admin baru dulu
2. Setelah ada 2+ admin, baru bisa demote/hapus

**Visual Indicator:**
```
Actions Column: [🛡️ Protected] (warna kuning)
```

### **3. Multi-Admin Safety** ✅

Jika ada **2+ admin**, boleh:
- ✅ Admin A bisa edit/hapus Admin B
- ✅ Admin B bisa edit/hapus Admin A
- ✅ Tapi tetap tidak bisa edit/hapus diri sendiri

**Log:** Semua perubahan admin tercatat di error log.

## 📋 Error Messages

### **Error 1: Edit Diri Sendiri**
```
❌ Tidak dapat mengubah akun Anda sendiri! 
   Minta admin lain untuk mengubah akun Anda.
```

### **Error 2: Hapus Diri Sendiri**
```
❌ Tidak dapat menghapus akun Anda sendiri!
```

### **Error 3: Demote Admin Terakhir**
```
❌ Tidak dapat mengubah role Admin terakhir! 
   Sistem harus memiliki minimal 1 Admin.
```

### **Error 4: Hapus Admin Terakhir**
```
❌ Tidak dapat menghapus Admin terakhir! 
   Sistem harus memiliki minimal 1 Admin.
```

## 🎯 User Interface

### **Tabel User Management**

| Name | Email | Role | Actions |
|------|-------|------|---------|
| Lutfi | lutfi@prcf.id | Admin | [🛡️ You] |
| Admin2 | admin2@prcf.id | Admin | [✏️ 🔑 🗑️] |
| FM User | fm@prcf.id | Finance Manager | [✏️ 🔑 🗑️] |

**Legend:**
- 🛡️ You = Akun Anda sendiri (tidak bisa diubah)
- 🛡️ Protected = Admin terakhir (tidak bisa diubah)
- ✏️ = Edit
- 🔑 = Reset Password
- 🗑️ = Delete

## 🔧 Implementation Details

### **Backend Protection (PHP)**

```php
// Function to count admins
function countAdmins($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'Admin'");
    return $result->fetch_assoc()['count'];
}

// Protection in UPDATE
if ($id_user == $current_user_id) {
    $error_message = "❌ Tidak dapat mengubah akun Anda sendiri!";
} elseif ($current_role === 'Admin' && $role !== 'Admin') {
    $admin_count = countAdmins($conn);
    if ($admin_count <= 1) {
        $error_message = "❌ Tidak dapat mengubah role Admin terakhir!";
    }
}

// Protection in DELETE
if ($id_user == $current_user_id) {
    $error_message = "❌ Tidak dapat menghapus akun Anda sendiri!";
} elseif ($user_role === 'Admin' && $admin_count <= 1) {
    $error_message = "❌ Tidak dapat menghapus Admin terakhir!";
}
```

### **Frontend Protection (UI)**

```php
<?php 
$is_self = ($user['id_user'] == $current_user_id);
$is_last_admin = ($user['role'] === 'Admin' && $admin_count <= 1);
?>

<?php if ($is_self): ?>
    <span class="bg-gray-100">🛡️ You</span>
<?php elseif ($is_last_admin): ?>
    <span class="bg-yellow-100">🛡️ Protected</span>
<?php else: ?>
    <!-- Show action buttons -->
<?php endif; ?>
```

## 📊 Activity Logging

Setiap perubahan admin dicatat di error log:

```php
error_log("ADMIN ACTION: User ID $current_user_id updated user ID $id_user (role changed: $current_role → $role)");
error_log("ADMIN ACTION: User ID $current_user_id deleted user ID $id_user (role: $user_role)");
```

**Log Location:** `/var/log/apache/error.log` (Linux) atau check Apache error log di cPanel

**View Logs:**
```bash
# Linux
tail -f /var/log/apache2/error.log | grep "ADMIN ACTION"

# cPanel
Error Logs → Filter by "ADMIN ACTION"
```

## ✅ Testing Scenarios

### **Test 1: Edit Diri Sendiri**
1. Login sebagai admin (misal: lutfi)
2. Pergi ke User Management
3. Coba edit akun lutfi
4. **Expected:** Tombol Edit tidak muncul, ada label "🛡️ You"

### **Test 2: Hapus Diri Sendiri**
1. Login sebagai admin
2. Pergi ke User Management
3. Coba hapus akun sendiri
4. **Expected:** Tombol Delete tidak muncul, ada label "🛡️ You"

### **Test 3: Demote Admin Terakhir**
1. Pastikan hanya ada 1 admin di sistem
2. Login sebagai admin lain (atau admin yang sama via modal)
3. Coba ubah role admin terakhir ke FM
4. **Expected:** Error message atau tombol tidak muncul "🛡️ Protected"

### **Test 4: Multiple Admin**
1. Buat 2 admin: Admin A dan Admin B
2. Login sebagai Admin A
3. Edit/Hapus Admin B → **Should work!** ✅
4. Try edit Admin A (self) → **Should fail!** ❌

## 🔐 Security Benefits

1. **Prevent Self-Lock** - Admin tidak bisa mengunci diri sendiri
2. **Prevent System Lock** - Sistem selalu punya minimal 1 admin
3. **Admin Accountability** - Semua perubahan tercatat
4. **Clear Feedback** - Error message yang jelas
5. **Visual Protection** - Badge yang mudah dipahami

## 🚀 Future Enhancements (Optional)

### **1. Super Admin Role**
- Role hierarchy: Super Admin > Admin
- Only Super Admin can manage other admins
- Prevent admin wars

### **2. Password Confirmation**
- Require current password before editing admin
- Extra security layer

### **3. Audit Trail**
- Full audit table: `admin_actions`
- Track: who, what, when, from_ip
- View audit log in admin panel

### **4. Two-Factor Authentication**
- Require 2FA for admin login
- SMS or Email OTP

### **5. Session Timeout**
- Auto-logout admin after 15 mins inactivity
- Security best practice

## 📞 Support

Jika ada masalah dengan admin management:
1. Check error logs untuk detail
2. Pastikan minimal ada 1 admin aktif
3. Hubungi super admin atau developer
4. Jangan pernah hapus semua admin!

---

**Last Updated:** November 2, 2025
**Version:** 3.0
**File:** `pages/admin/manage_users.php`

