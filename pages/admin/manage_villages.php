<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

// Role check: Admin or Finance Manager only
$allowed_roles = ['Admin', 'Finance Manager'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    $_SESSION['error'] = "Access denied. Finance Manager access required.";
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$current_user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get all active villages with audit info
$villages_query = "SELECT v.*, 
                   uc.nama as created_by_name, 
                   uu.nama as updated_by_name
                   FROM villages v
                   LEFT JOIN user uc ON v.created_by = uc.id_user
                   LEFT JOIN user uu ON v.updated_by = uu.id_user
                   WHERE v.is_deleted = 0
                   ORDER BY v.village_name ASC";
$villages = $conn->query($villages_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Villages - PRCF Keuangan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_fm.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">Manage Villages</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">
                        <i class="fas fa-user-shield mr-1"></i><?php echo $_SESSION['user_role']; ?>
                    </span>
                    <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Alert Messages -->
        <div id="alertContainer"></div>

        <!-- Info Card -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                <div>
                    <h3 class="font-semibold text-blue-900 mb-1">Master Data Villages</h3>
                    <p class="text-sm text-blue-800">
                        Villages are used in budget allocation with Place Code format: <code class="bg-blue-100 px-2 py-1 rounded">{exp_code}-{village_abbr}-01</code>
                        <br>Example: <code class="bg-blue-100 px-2 py-1 rounded mt-1 inline-block">10101-NJ-01</code> (Exp Code 10101, Nanga Jemah)
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Villages List</h3>
                <button onclick="openCreateModal()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                    <i class="fas fa-plus mr-2"></i>Add New Village
                </button>
            </div>

            <!-- Search -->
            <div class="p-6 border-b border-gray-200">
                <input type="text" id="searchInput" placeholder="Search by village name, code, or abbreviation..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full" id="villagesTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Village Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Abbreviation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated By</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($village = $villages->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50" data-search="<?php echo strtolower($village['village_code'] . ' ' . $village['village_name'] . ' ' . $village['village_abbr']); ?>">
                            <td class="px-6 py-4 text-sm font-mono text-gray-900"><?php echo htmlspecialchars($village['village_code']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($village['village_name']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-mono font-semibold">
                                    <?php echo htmlspecialchars($village['village_abbr']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                <div class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($village['description'] ?? '-')); ?></div>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                <?php echo htmlspecialchars($village['created_by_name'] ?? 'System'); ?>
                                <div class="text-gray-400"><?php echo date('d/m/Y', strtotime($village['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                <?php echo htmlspecialchars($village['updated_by_name'] ?? '-'); ?>
                                <?php if ($village['updated_at']): ?>
                                <div class="text-gray-400"><?php echo date('d/m/Y', strtotime($village['updated_at'])); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <button onclick='openEditModal(<?php echo json_encode($village); ?>)'
                                    class="text-blue-600 hover:text-blue-900 mr-2" title="Edit village">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick='confirmDelete(<?php echo $village['id_village']; ?>, "<?php echo htmlspecialchars($village['village_name']); ?>")'
                                    class="text-red-600 hover:text-red-900" title="Delete village">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Create/Edit Modal -->
    <div id="villageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-800 mb-6">Add New Village</h3>
            <form id="villageForm" onsubmit="saveVillage(event)">
                <input type="hidden" id="villageId">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Village Code *</label>
                    <input type="text" id="villageCode" required pattern="V\d{3}"
                        placeholder="V001" maxlength="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 font-mono">
                    <p class="text-xs text-gray-500 mt-1">Format: V### (example: V001, V002)</p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Village Name *</label>
                    <input type="text" id="villageName" required 
                        placeholder="Nanga Jemah"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Abbreviation *</label>
                    <input type="text" id="villageAbbr" required pattern="[A-Z]{2,5}"
                        placeholder="NJ" maxlength="5" style="text-transform: uppercase;"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 font-mono">
                    <p class="text-xs text-gray-500 mt-1">2-5 uppercase letters (example: NJ, PR, BWI)</p>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Expense Code Description *</label>
                    <textarea id="villageDescription" rows="5" required
                        placeholder="Example: LPHD Staff Salary and Insurance&#10;Line 2: Additional details..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Enter detailed expense information. Supports multiple lines.</p>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeModal()" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn" 
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        Create Village
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let isEditMode = false;

        function openCreateModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Add New Village';
            document.getElementById('villageForm').reset();
            document.getElementById('villageId').value = '';
            document.getElementById('submitBtn').textContent = 'Create Village';
            document.getElementById('villageModal').classList.remove('hidden');
        }

        function openEditModal(village) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = 'Edit Village';
            document.getElementById('villageId').value = village.id_village;
            document.getElementById('villageCode').value = village.village_code;
            document.getElementById('villageName').value = village.village_name;
            document.getElementById('villageAbbr').value = village.village_abbr;
            document.getElementById('villageDescription').value = village.description || '';
            document.getElementById('submitBtn').textContent = 'Update Village';
            document.getElementById('villageModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('villageModal').classList.add('hidden');
        }

        async function saveVillage(event) {
            event.preventDefault();
            
            const data = {
                id_village: document.getElementById('villageId').value,
                village_code: document.getElementById('villageCode').value.trim(),
                village_name: document.getElementById('villageName').value.trim(),
                village_abbr: document.getElementById('villageAbbr').value.trim().toUpperCase(),
                description: document.getElementById('villageDescription').value.trim()
            };

            const action = isEditMode ? 'update' : 'add';
            
            try {
                const response = await fetch(`../../api/manage_village.php?action=${action}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('Error: ' + error.message, 'error');
            }
        }

        async function confirmDelete(villageId, villageName) {
            Swal.fire({
                title: 'Delete Village?',
                html: `Are you sure you want to delete village "<strong>${villageName}</strong>"?<br><br><span class="text-sm text-gray-500">Note: This is a soft delete. The village will be hidden from dropdowns but preserved for historical budget data.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, Delete'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('../../api/manage_village.php?action=delete', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({ id_village: villageId })
                        });

                        const result = await response.json();

                        if (result.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: result.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                    } catch (error) {
                        Swal.fire('Error', error.message, 'error');
                    }
                }
            });
        }

        function showAlert(message, type) {
            const icon = type === 'success' ? 'success' : 'error';
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#villagesTable tbody tr');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                if (searchData.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Auto-uppercase abbreviation field
        document.getElementById('villageAbbr').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>
