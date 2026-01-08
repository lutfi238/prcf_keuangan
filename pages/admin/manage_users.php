<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';
require_once '../../includes/csrf_helper.php';

// Check maintenance mode
check_maintenance();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$current_user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Helper function: Count total admins
function countAdmins($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'Admin'");
    return $result->fetch_assoc()['count'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!csrf_validate()) {
        $error_message = 'Invalid security token. Please refresh and try again.';
    } elseif (isset($_POST['create_user'])) {
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $no_HP = $_POST['no_HP'];
        $password = $_POST['password'];
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Pre-check: Verify email doesn't already exist
        $check_stmt = $conn->prepare("SELECT id_user FROM user WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Email already exists - show error and stop
            $error_message = "❌ Email sudah terdaftar! Silakan gunakan email yang berbeda.";
        } else {
            // Email is unique - proceed with insertion
            $stmt = $conn->prepare("INSERT INTO user (nama, role, email, no_HP, password_hash) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama, $role, $email, $no_HP, $password_hash);

            if ($stmt->execute()) {
                $success_message = "✅ User berhasil ditambahkan!";
            } else {
                $error_message = "❌ Gagal menambahkan user: " . $conn->error;
            }
        }
    } elseif (isset($_POST['update_user'])) {
        $id_user = $_POST['id_user'];
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $no_HP = $_POST['no_HP'];
        
        // 🔒 PROTECTION 1: Cannot edit yourself
        if ($id_user == $current_user_id) {
            $error_message = "❌ Tidak dapat mengubah akun Anda sendiri! Minta admin lain untuk mengubah akun Anda.";
        } else {
            // 🔒 PROTECTION 3: Check if email already exists for another user
            $email_check_stmt = $conn->prepare("SELECT id_user FROM user WHERE email = ? AND id_user != ?");
            $email_check_stmt->bind_param("si", $email, $id_user);
            $email_check_stmt->execute();
            $email_check_result = $email_check_stmt->get_result();

            if ($email_check_result->num_rows > 0) {
                $error_message = "❌ Email sudah digunakan oleh user lain! Silakan gunakan email yang berbeda.";
            } else {
                // 🔒 PROTECTION 2: Check if demoting last admin
                // Also get old name to cascade update to proposals
                $check_stmt = $conn->prepare("SELECT nama, role FROM user WHERE id_user = ?");
                $check_stmt->bind_param("i", $id_user);
                $check_stmt->execute();
                $check_user = $check_stmt->get_result();
                $current_user_data = $check_user->fetch_assoc();
                $current_role = $current_user_data['role'];
                $old_name = $current_user_data['nama'];
                $check_stmt->close();

                if ($current_role === 'Admin' && $role !== 'Admin') {
                    $admin_count = countAdmins($conn);
                    if ($admin_count <= 1) {
                        $error_message = "❌ Tidak dapat mengubah role Admin terakhir! Sistem harus memiliki minimal 1 Admin.";
                    } else {
                        // Safe to demote, proceed with update
                        $stmt = $conn->prepare("UPDATE user SET nama = ?, role = ?, email = ?, no_HP = ? WHERE id_user = ?");
                        $stmt->bind_param("ssssi", $nama, $role, $email, $no_HP, $id_user);

                        if ($stmt->execute()) {
                            // CASCADE: Update pemohon in proposal table if name changed
                            if ($old_name !== $nama) {
                                $cascade_stmt = $conn->prepare("UPDATE proposal SET pemohon = ? WHERE pemohon = ?");
                                $cascade_stmt->bind_param("ss", $nama, $old_name);
                                $cascade_stmt->execute();
                                $cascade_stmt->close();
                            }
                            $success_message = "✅ User berhasil diupdate!";
                            error_log("ADMIN ACTION: User ID $current_user_id updated user ID $id_user (role changed: $current_role → $role)");
                        } else {
                            $error_message = "Gagal mengupdate user: " . $conn->error;
                        }
                    }
                } else {
                    // Normal update (not affecting admin role)
                    $stmt = $conn->prepare("UPDATE user SET nama = ?, role = ?, email = ?, no_HP = ? WHERE id_user = ?");
                    $stmt->bind_param("ssssi", $nama, $role, $email, $no_HP, $id_user);

                    if ($stmt->execute()) {
                        // CASCADE: Update pemohon in proposal table if name changed
                        if ($old_name !== $nama) {
                            $cascade_stmt = $conn->prepare("UPDATE proposal SET pemohon = ? WHERE pemohon = ?");
                            $cascade_stmt->bind_param("ss", $nama, $old_name);
                            $cascade_stmt->execute();
                            $cascade_stmt->close();
                        }
                        $success_message = "✅ User berhasil diupdate!";
                        error_log("ADMIN ACTION: User ID $current_user_id updated user ID $id_user");
                    } else {
                        $error_message = "Gagal mengupdate user: " . $conn->error;
                    }
                }
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        $id_user = $_POST['id_user'];
        
        // 🔒 PROTECTION 1: Cannot delete yourself
        if ($id_user == $current_user_id) {
            $error_message = "❌ Tidak dapat menghapus akun Anda sendiri!";
        } else {
            // 🔒 PROTECTION 2: Check if deleting last admin
            $check_stmt = $conn->prepare("SELECT role FROM user WHERE id_user = ?");
            $check_stmt->bind_param("i", $id_user);
            $check_stmt->execute();
            $check_user = $check_stmt->get_result();
            $user_role = $check_user->fetch_assoc()['role'];
            $check_stmt->close();
            
            if ($user_role === 'Admin') {
                $admin_count = countAdmins($conn);
                if ($admin_count <= 1) {
                    $error_message = "❌ Tidak dapat menghapus Admin terakhir! Sistem harus memiliki minimal 1 Admin.";
                } else {
                    // Safe to delete, proceed
                    $stmt = $conn->prepare("DELETE FROM user WHERE id_user = ?");
                    $stmt->bind_param("i", $id_user);
                    
                    if ($stmt->execute()) {
                        $success_message = "✅ User berhasil dihapus!";
                        error_log("ADMIN ACTION: User ID $current_user_id deleted user ID $id_user (role: $user_role)");
                    } else {
                        $error_message = "Gagal menghapus user: " . $conn->error;
                    }
                }
            } else {
                // Safe to delete non-admin user
                $stmt = $conn->prepare("DELETE FROM user WHERE id_user = ?");
                $stmt->bind_param("i", $id_user);
                
                if ($stmt->execute()) {
                    $success_message = "✅ User berhasil dihapus!";
                } else {
                    $error_message = "Gagal menghapus user: " . $conn->error;
                }
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        $id_user = $_POST['id_user'];
        $new_password = $_POST['new_password'];
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE id_user = ?");
        $stmt->bind_param("si", $password_hash, $id_user);

        if ($stmt->execute()) {
            $success_message = "Password berhasil direset!";
        } else {
            $error_message = "Gagal mereset password: " . $conn->error;
        }
    } elseif (isset($_POST['toggle_status'])) {
        $id_user = $_POST['id_user'];
        $current_status = $_POST['current_status'];
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';

        // 🔒 PROTECTION: Cannot deactivate yourself
        if ($id_user == $current_user_id) {
            $error_message = "❌ Tidak dapat menonaktifkan akun Anda sendiri!";
        } else {
            $stmt = $conn->prepare("UPDATE user SET status = ? WHERE id_user = ?");
            $stmt->bind_param("si", $new_status, $id_user);

            if ($stmt->execute()) {
                $action = $new_status === 'active' ? 'activated' : 'deactivated';
                $success_message = "✅ User berhasil di{$action}!";
                error_log("ADMIN ACTION: User ID $current_user_id {$action} user ID $id_user");
            } else {
                $error_message = "Gagal mengubah status user: " . $conn->error;
            }
        }
    }
}

// Get all users
$users = $conn->query("SELECT * FROM user ORDER BY created_at DESC");

// Count total admins for protection logic
$admin_count = countAdmins($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_admin.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">Manage Users</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">User Management</h3>
                <button onclick="openCreateModal()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                    <i class="fas fa-plus mr-2"></i>Add New User
                </button>
            </div>

            <!-- Search and Filter -->
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <div class="flex space-x-4">
                    <select id="roleFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Roles</option>
                        <option value="Admin">Admin</option>
                        <option value="Direktur">Direktur</option>
                        <option value="Finance Manager">Finance Manager</option>
                        <option value="Staff Accountant">Staff Accountant</option>
                        <option value="Project Manager">Project Manager</option>
                    </select>
                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <input type="text" id="searchInput" placeholder="Search by name or email..."
                    class="px-4 py-2 border border-gray-300 rounded-lg w-64">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full" id="usersTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50" data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['status'] ?? 'active'; ?>" data-search="<?php echo strtolower($user['nama'] . ' ' . $user['email']); ?>">
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo $user['id_user']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    <?php
                                    $role_colors = [
                                        'Admin' => 'bg-red-100 text-red-800',
                                        'Direktur' => 'bg-purple-100 text-purple-800',
                                        'Finance Manager' => 'bg-blue-100 text-blue-800',
                                        'Staff Accountant' => 'bg-green-100 text-green-800',
                                        'Project Manager' => 'bg-yellow-100 text-yellow-800'
                                    ];
                                    echo $role_colors[$user['role']] ?? 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    <?php
                                    $status_colors = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'inactive' => 'bg-red-100 text-red-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800'
                                    ];
                                    $user_status = $user['status'] ?? 'active';
                                    echo $status_colors[$user_status] ?? 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php echo ucfirst($user_status); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($user['no_HP'] ?? '-'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td class="px-6 py-4 text-sm text-center">
                                <?php 
                                $is_self = ($user['id_user'] == $current_user_id);
                                $is_last_admin = ($user['role'] === 'Admin' && $admin_count <= 1);
                                $is_protected = $is_self || $is_last_admin;
                                ?>
                                
                                <?php if ($is_self): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        <i class="fas fa-user-shield mr-1"></i> You
                                    </span>
                                <?php elseif ($is_last_admin): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700" title="Last admin - cannot be modified">
                                        <i class="fas fa-shield-alt mr-1"></i> Protected
                                    </span>
                                <?php else: ?>
                                    <button onclick='toggleUserStatus(<?php echo $user['id_user']; ?>, "<?php echo $user['status'] ?? 'active'; ?>", "<?php echo htmlspecialchars($user['nama']); ?>")'
                                        class="text-<?php echo ($user['status'] ?? 'active') === 'active' ? 'orange' : 'green'; ?>-600 hover:text-<?php echo ($user['status'] ?? 'active') === 'active' ? 'orange' : 'green'; ?>-900 mr-2"
                                        title="<?php echo ($user['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate'; ?> user">
                                        <i class="fas fa-<?php echo ($user['status'] ?? 'active') === 'active' ? 'user-slash' : 'user-check'; ?>"></i>
                                    </button>
                                    <button onclick='openEditModal(<?php echo json_encode($user); ?>)'
                                        class="text-blue-600 hover:text-blue-900 mr-2" title="Edit user">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick='openResetPasswordModal(<?php echo $user['id_user']; ?>, "<?php echo htmlspecialchars($user['nama']); ?>")'
                                        class="text-yellow-600 hover:text-yellow-900 mr-2" title="Reset password">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button onclick='confirmDelete(<?php echo $user['id_user']; ?>, "<?php echo htmlspecialchars($user['nama']); ?>")'
                                        class="text-red-600 hover:text-red-900" title="Delete user">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Create/Edit Modal -->
    <div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-800 mb-6">Add New User</h3>
            <form method="POST" id="userForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_user" id="userId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Name *</label>
                    <input type="text" name="nama" id="userName" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Email *</label>
                    <input type="email" name="email" id="userEmail" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Role *</label>
                    <select name="role" id="userRole" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
                        <option value="">Select Role</option>
                        <option value="Admin">Admin</option>
                        <option value="Direktur">Direktur</option>
                        <option value="Finance Manager">Finance Manager</option>
                        <option value="Staff Accountant">Staff Accountant</option>
                        <option value="Project Manager">Project Manager</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Phone Number</label>
                    <input type="text" name="no_HP" id="userPhone" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
                </div>

                <div class="mb-6" id="passwordField">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Password *</label>
                    <input type="password" name="password" id="userPassword" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeModal()" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="create_user" id="submitBtn" 
                        class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-6">Reset Password</h3>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_user" id="resetUserId">
                
                <p class="text-gray-600 mb-4">Reset password for: <strong id="resetUserName"></strong></p>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2">New Password *</label>
                    <input type="password" name="new_password" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeResetPasswordModal()" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="reset_password" 
                        class="px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form method="POST" id="deleteForm" class="hidden">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id_user" id="deleteUserId">
        <input type="hidden" name="delete_user" value="1">
    </form>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('passwordField').classList.remove('hidden');
            document.getElementById('userPassword').required = true;
            document.getElementById('submitBtn').name = 'create_user';
            document.getElementById('submitBtn').textContent = 'Create User';
            document.getElementById('userModal').classList.remove('hidden');
        }

        function openEditModal(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.id_user;
            document.getElementById('userName').value = user.nama;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userPhone').value = user.no_HP || '';
            document.getElementById('passwordField').classList.add('hidden');
            document.getElementById('userPassword').required = false;
            document.getElementById('submitBtn').name = 'update_user';
            document.getElementById('submitBtn').textContent = 'Update User';
            document.getElementById('userModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        function openResetPasswordModal(userId, userName) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUserName').textContent = userName;
            document.getElementById('resetPasswordModal').classList.remove('hidden');
        }

        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').classList.add('hidden');
        }

        function confirmDelete(userId, userName) {
            Swal.fire({
                title: 'Hapus User?',
                text: `Anda yakin ingin menghapus user "${userName}"? Tindakan ini tidak dapat dibatalkan.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444', // red-500
                cancelButtonColor: '#6B7280', // gray-500
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteUserId').value = userId;
                    document.getElementById('deleteForm').submit();
                }
            });
        }

        function toggleUserStatus(userId, currentStatus, userName) {
            const action = currentStatus === 'active' ? 'Deactivate' : 'Activate';
            const actionText = currentStatus === 'active' ? 'menonaktifkan' : 'mengaktifkan';
            const isDeactivating = currentStatus === 'active';
            
            const htmlText = isDeactivating
                ? '<ul class="text-left text-sm mt-3 space-y-1"><li>&bull; User tidak akan bisa login</li><li>&bull; Sesi aktif akan diakhiri</li></ul>'
                : '<p class="text-sm mt-2">User akan bisa login kembali secara normal.</p>';

            Swal.fire({
                title: `${action} User?`,
                html: `Apakah Anda yakin ingin <strong>${actionText}</strong> user "${userName}"?${htmlText}`,
                icon: isDeactivating ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: isDeactivating ? '#F59E0B' : '#10B981', // yellow-500 : green-500
                cancelButtonColor: '#6B7280',
                confirmButtonText: `Ya, ${action}!`,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create a form to submit the toggle request
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';

                    const idInput = document.createElement('input');
                    idInput.name = 'id_user';
                    idInput.value = userId;
                    form.appendChild(idInput);

                    const statusInput = document.createElement('input');
                    statusInput.name = 'current_status';
                    statusInput.value = currentStatus;
                    form.appendChild(statusInput);

                    const toggleInput = document.createElement('input');
                    toggleInput.name = 'toggle_status';
                    toggleInput.value = '1';
                    form.appendChild(toggleInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Search and Filter
        document.getElementById('searchInput').addEventListener('input', filterTable);
        document.getElementById('roleFilter').addEventListener('change', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#usersTable tbody tr');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                const role = row.getAttribute('data-role');
                const status = row.getAttribute('data-status');

                const matchesSearch = searchData.includes(searchTerm);
                const matchesRole = !roleFilter || role === roleFilter;
                const matchesStatus = !statusFilter || status === statusFilter;

                if (matchesSearch && matchesRole && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

