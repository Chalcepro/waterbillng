<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Performance - WaterBill NG</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="icon" href="../../assets/images/logo-placeholder.png" type="image/png">
    <script src="../../frontend/assets/js/notify.js"></script>
    <style>
        :root {
            --primary-color: #039ed1;
            --primary-dark: #0a5962;
            --primary-light: rgba(3, 158, 209, 0.1);
            --secondary-color: #2c3e50;
            --accent-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --text-light: #ecf0f1;
            --text-dark: #2c3e50;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --gradient-primary: linear-gradient(135deg, #11c0d4 0%, #039ed1 100%);
            --gradient-secondary: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f8f9fa;
            padding-top: 0;
            min-height: 100vh;
            color: #333;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
            padding-bottom: 2rem;
        }
        
        /* Hide header on mobile */
        @media (max-width: 991.98px) {
            .desktop-header {
                display: none !important;
            }
            body {
                padding-top: 0;
                padding-bottom: 60px; /* Space for mobile footer */
            }
        }
        
        /* Show header only on desktop */
        @media (min-width: 992px) {
            body {
                padding-top: 70px;
            }
        }
        
        /* Mobile Footer */
        .mobile-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 8px 0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .mobile-footer-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #6c757d;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s;
            cursor: pointer;
            position: relative;
            width: 25%;
            text-align: center;
        }
        
        .mobile-footer-item i {
            font-size: 1.2rem;
            margin-bottom: 4px;
        }
        
        .mobile-footer-item span {
            font-size: 0.65rem;
            font-weight: 500;
            line-height: 1.1;
        }
        
        .mobile-footer-item.active,
        .mobile-footer-item.show {
            color: var(--primary-color);
            background: var(--primary-light);
        }
        
        .mobile-footer-item:hover {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        /* Mobile Dropdown Menu */
        .mobile-dropdown {
            position: fixed;
            bottom: 65px;
            left: 10px;
            right: 10px;
            max-height: 60vh;
            overflow-y: auto;
            border-radius: 12px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            padding: 10px 0;
            margin: 0;
        }
        
        .mobile-dropdown .dropdown-item {
            padding: 10px 20px;
            font-size: 0.9rem;
            color: #333;
            display: flex;
            align-items: center;
        }
        
        .mobile-dropdown .dropdown-item i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        .mobile-dropdown .dropdown-item.active,
        .mobile-dropdown .dropdown-item:active {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .mobile-dropdown .dropdown-divider {
            margin: 5px 0;
        }
        
        /* Header Styling - Matches WaterBill NG Brand */
        .navbar {
            background: var(--gradient-secondary) !important;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 0.75rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            color: white !important;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand img {
            height: 32px;
            margin-right: 10px;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 6px;
            margin: 0 2px;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white !important;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            padding: 0.6rem 1.5rem;
            font-weight: 500;
        }
        
        .dropdown-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-completed {
            background: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }
        
        .status-pending {
            background: rgba(243, 156, 18, 0.15);
            color: #f39c12;
        }
        
        .status-failed {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }
        
        .status-verification_pending {
            background: rgba(155, 89, 182, 0.15);
            color: #9b59b6;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 6px 12px;
            margin: 0 2px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            color: white;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        
        .action-btn.view {
            background: var(--accent-color);
        }
        
        .action-btn.approve {
            background: var(--success-color);
        }
        
        .action-btn.reject {
            background: var(--danger-color);
        }
        
        /* Removed button hover effects */
        
        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        /* Removed card hover effect */
        
        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0 !important;
        }
        
        /* Tables */
        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 12px 15px;
        }
        
        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: #f1f5f9;
        }
        
        /* Removed table row hover effect */
        
        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        /* Buttons */
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        /* Removed button hover effect */
        
        /* Modal Styling */
        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        /* Payment Stats */
        .payment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        /* Search and Filter */
        .search-filter-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }
        
        /* Receipt Preview */
        .receipt-preview {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .payment-stats {
                grid-template-columns: 1fr 1fr;
            }
            
            .table-responsive {
                border-radius: 8px;
            }
        }
        
        @media (max-width: 576px) {
            .payment-stats {
                grid-template-columns: 1fr;
            }
            
            .action-btn {
                padding: 4px 8px;
                margin: 1px;
            }
        }
    </style>
</head>
<body>
    <!-- Desktop Header - Only visible on desktop -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top desktop-header">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.html">
                <img src="../../assets/images/logo-placeholder.png" alt="WaterBill NG">
                <span>WaterBill NG</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.html">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.html">
                            <i class="fas fa-users me-1"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payments.html">
                            <i class="fas fa-credit-card me-1"></i> Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.html">
                            <i class="fas fa-chart-bar me-1"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="settings.html">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><a class="dropdown-item" href="notifications.html">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="logout()">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid py-4 px-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Community Performance</h1>
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search users...">
            </div>
        </div>

        <!-- Desktop Table (hidden on mobile) -->
        <div class="d-none d-md-block">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">User</th>
                                        <th>Last Payment</th>
                                        <th>Months Cleared</th>
                                        <th>Months Paid</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Total Paid</th>
                                    </tr>
                                </thead>
                                <tbody id="users-table">
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="d-flex justify-content-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                            <p class="mt-2 mb-0">Loading user data...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted">
                                    Showing <span id="showingCount">0</span> of <span id="totalCount">0</span> users
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Cards (visible only on mobile) -->
            <div class="row d-md-none g-3 mt-3" id="mobile-users-container">
                <div class="col-12">
                    <div class="d-flex flex-column align-items-center justify-content-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 mb-0 text-center">Loading user data...</p>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Footer -->
            <div class="card-footer bg-white border-0 py-3 d-md-none">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Showing <span id="mobileShowingCount">0</span> of <span id="mobileTotalCount">0</span> users
                    </div>
                </div>
            </div>

        <!-- Toast Container -->
        <div id="toastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11"></div>

        <!-- Bootstrap 5 JS Bundle (includes Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <script>
            // API base URL
            const API_BASE_URL = '/waterbill/api';
            let allUsers = [];
        
            // Initialize page
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
                
                // Load user data
                loadUsers();
                
                // Search functionality
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.addEventListener('input', filterUsers);
                }
            });
            
            // Load users data
            async function loadUsers() {
                try {
                    const response = await fetch(`${API_BASE_URL}/community-performance.php`, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        allUsers = data.data || [];
                        updateUsersTable(allUsers);
                        updateStats(allUsers);
                    } else {
                        throw new Error(data.message || 'Failed to load user data');
                    }
                } catch (error) {
                    console.error('Error loading user data:', error);
                    showToast('error', 'Failed to load user data. Please try again.');
                }
            }
            
            // Update users table with data
            function updateUsersTable(users) {
                const tbody = document.getElementById('users-table');
                const mobileContainer = document.getElementById('mobile-users-container');
                
                if (!users || users.length === 0) {
                    const noDataHtml = `
                        <div class="col-12">
                            <div class="card shadow-sm mb-3">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">No users found</p>
                                </div>
                            </div>
                        </div>`;
                    
                    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><i class="fas fa-inbox fa-2x text-muted mb-2"></i><p class="mb-0">No users found</p></td></tr>';
                    if (mobileContainer) mobileContainer.innerHTML = noDataHtml;
                    return;
                }
                
                let html = '';
                
                // Sort users: suspended first, then by subscription end date (earliest first)
                const sortedUsers = [...users].sort((a, b) => {
                    if (a.status === 'suspended' && b.status !== 'suspended') return -1;
                    if (a.status !== 'suspended' && b.status === 'suspended') return 1;
                    
                    const dateA = a.subscription_end_date ? new Date(a.subscription_end_date) : new Date(0);
                    const dateB = b.subscription_end_date ? new Date(b.subscription_end_date) : new Date(0);
                    
                    return dateA - dateB;
                });
                
                sortedUsers.forEach(user => {
                    const lastPaymentDate = user.last_payment_date ? new Date(user.last_payment_date).toLocaleDateString() : 'N/A';
                    const statusText = user.status.charAt(0).toUpperCase() + user.status.slice(1);
                    const amount = parseFloat(user.total_paid || 0).toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    
                    // Desktop row
                    desktopHtml += `
                        <tr class="user-row ${user.status === 'suspended' ? 'table-active' : ''}" data-status="${user.status}">
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar-sm bg-soft-primary rounded-circle p-2">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">${user.full_name || user.username}</h6>
                                        <small class="text-muted">${user.username}</small>
                                    </div>
                                </div>
                            </td>
                            <td>${lastPaymentDate}</td>
                            <td>${user.months_cleared}</td>
                            <td>${user.months_paid_for}</td>
                            <td>
                                <span class="badge bg-${user.status_class} bg-opacity-10 text-${user.status_class} p-2">
                                    <i class="fas fa-circle me-1 small"></i>
                                    ${statusText}
                                </span>
                            </td>
                            <td class="text-end pe-4 fw-medium">
                                ₦${amount}
                            </td>
                        </tr>`;
                    
                    // Mobile card
                    mobileHtml += `
                        <div class="col-12">
                            <div class="card shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-soft-${user.status_class} rounded-circle p-2 me-3">
                                                <i class="fas fa-user text-${user.status_class}"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">${user.full_name || user.username}</h6>
                                                <small class="text-muted">${user.username}</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-${user.status_class} bg-opacity-10 text-${user.status_class} px-3 py-2">
                                            <i class="fas fa-circle me-1 small"></i>
                                            ${statusText}
                                        </span>
                                    </div>
                                    
                                    <div class="row g-2 mt-2">
                                        <div class="col-6">
                                            <div class="text-muted small">Last Payment</div>
                                            <div class="fw-medium">${lastPaymentDate}</div>
                                        </div>
                                        <div class="col-3 text-center">
                                            <div class="text-muted small">Cleared</div>
                                            <div class="fw-medium">${user.months_cleared}</div>
                                        </div>
                                        <div class="col-3 text-center">
                                            <div class="text-muted small">Paid For</div>
                                            <div class="fw-medium">${user.months_paid_for}</div>
                                        </div>
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted small">Total Paid</span>
                                                <span class="fw-medium">₦${amount}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });
                
                // Update desktop table
                if (tbody) tbody.innerHTML = desktopHtml;
                
                // Update mobile cards
                if (mobileContainer) mobileContainer.innerHTML = mobileHtml;
                document.getElementById('totalCount').textContent = users.length;
                document.getElementById('showingCount').textContent = users.length;
            }
            
            // Update stats cards - simplified as we're not showing stats in the mobile view
            function updateStats(users) {
                if (!users || users.length === 0) return;
                
                // If stats elements exist (desktop view), update them
                const totalEl = document.getElementById('totalUsers');
                if (totalEl) {
                    const activeUsers = users.filter(u => u.status === 'active').length;
                    const pendingUsers = users.filter(u => u.status === 'pending').length;
                    const suspendedUsers = users.filter(u => u.status === 'suspended').length;
                    
                    totalEl.textContent = users.length;
                    document.getElementById('activeUsers').textContent = activeUsers;
                    document.getElementById('pendingUsers').textContent = pendingUsers;
                    document.getElementById('suspendedUsers').textContent = suspendedUsers;
                }
            }
            
            // Filter users based on search input
            function filterUsers() {
                const searchTerm = document.getElementById('searchInput')?.value?.toLowerCase() || '';
                
                if (!searchTerm) {
                    updateUsersTable(allUsers);
                    return;
                }
                
                const filteredUsers = allUsers.filter(user => {
                    return (
                        (user.username && user.username.toLowerCase().includes(searchTerm)) ||
                        (user.full_name && user.full_name.toLowerCase().includes(searchTerm)) ||
                        (user.flat_no && user.flat_no.toLowerCase().includes(searchTerm))
                    );
                });
                
                updateUsersTable(filteredUsers);
            }
    </script>
</body>
</html>