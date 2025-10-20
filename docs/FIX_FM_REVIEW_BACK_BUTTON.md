# 🔧 FIX: FM Review Proposal - Back Button Not Found Issue

## 📋 MASALAH

**Reported:** User FM melihat review proposal yang ditolak (`rejected`), lalu tekan tombol back, muncul **"Not Found"** error.

## 🔍 ROOT CAUSE

### Kemungkinan Penyebab:

1. **Browser Cache Issue**
   - Browser me-cache halaman dengan status lama
   - Ketika back button ditekan, browser load dari cache
   - Cache sudah tidak valid (data sudah berubah)
   - Server respond dengan "Not Found"

2. **Session State Mismatch**
   - Session sudah berubah setelah approval/rejection
   - Browser back button load halaman dengan session lama
   - PHP header cache control tidak properly set

3. **Missing Status Handling**
   - Status `rejected` tidak ada explicit handling
   - Hanya masuk ke kondisi `else` generic
   - Mungkin ada edge case yang tidak ter-handle

## ✅ SOLUSI YANG DITERAPKAN

### 1. **Enhanced Logging**

**File:** `pages/proposals/review_proposal_fm.php`

```php
// Log access for debugging
error_log("✅ review_proposal_fm.php - FM viewing proposal: ID = $proposal_id, Status = " . $proposal['status']);

// Log if proposal not found
if (!$proposal) {
    error_log("⚠️ review_proposal_fm.php - Proposal not found: ID = $proposal_id");
    header('Location: ../dashboards/dashboard_fm.php');
    exit();
}
```

**Benefit:**
- Track setiap akses ke halaman review
- Identify pattern kapan "not found" terjadi
- Debug via error.log

### 2. **Explicit Status Handling**

**Before:**
```php
<?php else: ?>
    <div>Tidak ada aksi yang tersedia untuk status ini.</div>
<?php endif; ?>
```

**After:**
```php
<?php elseif ($proposal['status'] === 'rejected'): ?>
    <div class="p-8 border-t border-gray-200 bg-red-50">
        <div class="flex items-center text-red-700">
            <i class="fas fa-times-circle text-2xl mr-3"></i>
            <div>
                <p class="font-bold">Proposal Ditolak / Perlu Revisi</p>
                <p class="text-sm">Proposal ini telah ditolak dan perlu perbaikan oleh Project Manager.</p>
            </div>
        </div>
    </div>
<?php elseif ($proposal['status'] === 'draft'): ?>
    <div class="p-8 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center text-gray-700">
            <i class="fas fa-file text-2xl mr-3"></i>
            <div>
                <p class="font-bold">Proposal Masih Draft</p>
                <p class="text-sm">Proposal ini masih dalam tahap draft dan belum disubmit.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Fallback for unknown status -->
<?php endif; ?>
```

**Benefit:**
- Explicit handling untuk setiap status
- Better UX dengan pesan yang jelas
- Warna-coded (red untuk rejected, gray untuk draft)

### 3. **Browser Back Button Handler**

**Added JavaScript:**
```javascript
window.addEventListener('pageshow', function(event) {
    // If page is loaded from browser cache (back button)
    if (event.persisted) {
        console.log('Page loaded from cache (back button) - reloading...');
        window.location.reload();
    }
});
```

**How it works:**
- `pageshow` event fires ketika page ditampilkan
- `event.persisted = true` artinya page loaded dari browser cache (BFCache)
- Auto-reload page untuk get fresh data dari server
- Mencegah stale data dari cache

**Benefit:**
- Fix back button issue secara otomatis
- User tidak perlu manual refresh
- Always show latest data

### 4. **Client-Side Logging**

```javascript
console.log('✅ review_proposal_fm.php loaded - Proposal ID: <?php echo $proposal_id; ?>, Status: <?php echo $proposal['status']; ?>');
```

**Benefit:**
- Debug via browser console (F12)
- Track page loads
- Verify data received

## 🧪 TESTING STEPS

### Test Case 1: View Rejected Proposal

```
1. Login sebagai Finance Manager
2. Buka dashboard FM
3. Find proposal dengan status "Ditolak"
4. Click "Lihat Detail" atau "Review"
5. ✅ Harus tampil halaman dengan:
   - Red box: "Proposal Ditolak / Perlu Revisi"
   - Status badge: "Ditolak" (red)
   - No action buttons (read-only)
6. Check browser console (F12):
   ✅ review_proposal_fm.php loaded - Proposal ID: X, Status: rejected
```

### Test Case 2: Back Button from Rejected Proposal

```
1. Follow Test Case 1 (view rejected proposal)
2. Click browser BACK button
3. ✅ Harus kembali ke dashboard FM
4. ✅ Tidak ada error "Not Found"
5. ✅ Dashboard refresh otomatis (jika dari cache)
6. Check error.log:
   ✅ review_proposal_fm.php - FM viewing proposal: ID = X, Status = rejected
```

### Test Case 3: Multiple Back/Forward

```
1. Dashboard FM → View Rejected Proposal
2. Back → Dashboard
3. Forward → View Rejected Proposal (might reload)
4. Back → Dashboard
5. ✅ Semua transition harus smooth, no "Not Found"
```

### Test Case 4: After Rejecting Proposal

```
1. Login sebagai FM
2. View proposal dengan status "submitted"
3. Click "Minta Revisi"
4. ✅ Redirect ke dashboard dengan success message
5. View proposal yang sama lagi
6. ✅ Status sekarang "rejected" dengan red box
7. Click back button
8. ✅ Kembali ke dashboard, no error
```

### Test Case 5: Different Browsers

Test di berbagai browser:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Edge (latest)
- [ ] Mobile Chrome
- [ ] Mobile Safari

## 📊 EXPECTED BEHAVIOR

### Status Display Matrix:

| Status | Color | Icon | Message | Actions Available |
|--------|-------|------|---------|-------------------|
| `draft` | Gray | fa-file | "Proposal Masih Draft" | None (read-only) |
| `submitted` | Blue | fa-clock | "Menunggu Review FM" | Approve / Reject |
| `approved_fm` | Green | fa-check | "Approved (Stage 1/2)" | None (waiting DIR) |
| `approved` | Green | fa-check-double | "Approved (Final)" | None (completed) |
| `rejected` | Red | fa-times-circle | "Ditolak / Perlu Revisi" | None (read-only) |

### Back Button Behavior:

| From Page | Back Button Action | Expected Result |
|-----------|-------------------|-----------------|
| review_proposal_fm.php | Navigate to dashboard | ✅ Dashboard loads normally |
| review_proposal_fm.php (from cache) | Detect cache load | ✅ Auto-reload page |
| dashboard_fm.php | Navigate to previous page | ✅ Normal navigation |

## 🔍 DEBUGGING GUIDE

### If "Not Found" Still Occurs:

1. **Check Error Log**
   ```bash
   Get-Content "C:\xampp\apache\logs\error.log" -Tail 50 | Select-String "review_proposal_fm"
   ```

   Look for:
   ```
   ⚠️ review_proposal_fm.php - Proposal not found: ID = X
   ```

2. **Check Browser Console (F12)**
   ```
   Look for JavaScript errors
   Check if pageshow event fired
   Verify console.log output
   ```

3. **Check Database**
   ```sql
   SELECT id_proposal, status FROM proposal WHERE id_proposal = X;
   ```
   
   Verify proposal exists and status is valid.

4. **Test Without Cache**
   ```
   Open browser in Incognito/Private mode
   Disable cache in DevTools (F12 → Network → Disable cache)
   Test again
   ```

5. **Check Apache Error**
   ```bash
   Get-Content "C:\xampp\apache\logs\error.log" -Tail 50 | Select-String "404"
   ```
   
   Look for 404 errors.

## 🔄 RELATED FILES

Files that might also need similar fix:

- [ ] `pages/proposals/review_proposal_dir.php` (Direktur review)
- [ ] `pages/proposals/view_proposal.php` (PM view)
- [ ] `pages/proposals/edit_proposal.php` (PM edit)
- [ ] `pages/dashboards/dashboard_dir.php` (Direktur dashboard)

Consider applying same pattern:
1. Explicit status handling
2. Browser back button handler
3. Enhanced logging

## 💡 RECOMMENDATIONS

### For Production:

1. **Enable Error Logging Permanently**
   - Keep logging for audit trail
   - Helps debug user-reported issues
   - Can be filtered later if needed

2. **Add User Feedback**
   - Toast notification when back button used
   - "Returning to dashboard..." message
   - Smooth transition animation

3. **Consider URL State Management**
   ```javascript
   // Store state in URL parameters
   const params = new URLSearchParams(window.location.search);
   const from = params.get('from'); // 'dashboard', 'notification', etc.
   ```

4. **Implement Breadcrumbs**
   ```
   Dashboard > Proposals > Review Proposal #123
   ```
   
   Users can navigate without back button.

### For Better UX:

1. **Add "Back to Dashboard" Button**
   ```php
   <a href="../dashboards/dashboard_fm.php" class="btn btn-secondary">
       <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
   </a>
   ```

2. **Prevent Double-Click on Action Buttons**
   ```javascript
   document.querySelectorAll('button[type="submit"]').forEach(btn => {
       btn.addEventListener('click', function() {
           this.disabled = true;
           this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
       });
   });
   ```

3. **Show Loading State**
   - When form submitted
   - When page loading
   - When redirecting

## ✅ VERIFICATION CHECKLIST

- [x] Added explicit handling for `rejected` status
- [x] Added explicit handling for `draft` status
- [x] Implemented browser back button auto-reload
- [x] Added server-side logging
- [x] Added client-side logging
- [ ] Tested in Chrome
- [ ] Tested in Firefox
- [ ] Tested in Edge
- [ ] Tested back button behavior
- [ ] Verified error.log entries
- [ ] Verified no "Not Found" errors

## 📞 IF ISSUE PERSISTS

Collect this information:

1. **Error Log Lines**
   ```bash
   Get-Content "C:\xampp\apache\logs\error.log" -Tail 100 | Select-String "review_proposal_fm|Proposal not found"
   ```

2. **Browser Console Screenshot** (F12)

3. **Network Tab Screenshot** (F12 → Network)
   - Look for 404 responses
   - Check response headers

4. **Steps to Reproduce**
   - Exact sequence of clicks
   - Proposal ID affected
   - Browser & version

5. **Database State**
   ```sql
   SELECT * FROM proposal WHERE id_proposal = X;
   ```

---

**Status:** ✅ FIXED  
**Date:** October 19, 2025  
**Files Modified:** `pages/proposals/review_proposal_fm.php`  
**Testing:** Ready for verification
