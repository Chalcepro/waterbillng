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
            min-height: 100vh;
            color: #333;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Header Styling - Hidden on tablet/mobile */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--gradient-secondary);
            color: #fff;
            padding: 15px 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-container img {
            width: 45px;
            height: 45px;
            border-radius: 8px;
        }

        .logo-container h1 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .hamburger {
            display: none;
            font-size: 24px;
            cursor: pointer;
            background: none;
            border: none;
            color: white;
            padding: 5px;
        }

        nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 6px;
            transition: background 0.3s;
            white-space: nowrap;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100%;
            background: var(--gradient-secondary);
            color: white;
            padding-top: 80px;
            transition: 0.3s;
            z-index: 999;
            box-shadow: 5px 0 25px rgba(0,0,0,0.15);
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 18px 25px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            padding-left: 30px;
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
        }

        .sidebar.show {
            left: 0;
        }

        /* Overlay for sidebar */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 998;
        }

        .overlay.show {
            display: block;
        }
                                                  
        /* Hide header on tablet and mobile */
        @media (max-width: 992px) {
            header {
                display: none;
            }
            
            .main-content {
                padding: 20px 15px 100px 15px;
            }

            nav {
                gap: 8px;
            }

            nav a {
                padding: 6px 12px;
                font-size: 13px;
            }

            .hamburger {
                display: block;
            }
        }
        
        .main-content {
            flex: 1;
            padding: 30px 25px 120px 25px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Show header only on desktop */
        @media (min-width: 992px) {
            body {
                padding-top: 0; /* Remove top padding since header is hidden */
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
            padding: 12px 0;
            box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .footer-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #6b7280;
            transition: all 0.3s ease;
            position: relative;
            flex: 1;
            max-width: 70px;
            padding: 5px 0;
        }
        
        .footer-link i {
            font-size: 1.2rem;
            margin-bottom: 4px;
            transition: all 0.3s ease;
        }
        
        .footer-link span {
            font-size: 0.65rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            line-height: 1.1;
        }
        
        .footer-link.active,
        .footer-link:hover {
            color: var(--primary-color);
        }
        
        .footer-link.active i,
        .footer-link:hover i {
            transform: scale(1.1);
        }
        
        .footer-link.active::after {
            content: '';
            position: absolute;
            bottom: -12px;
            width: 5px;
            height: 5px;
            background: var(--primary-color);
            border-radius: 50%;
        }
        
        /* Mobile Dropdown Menu */
        .mobile-dropdown {
            position: fixed;
            bottom: 70px;
            left: 10px;
            right: 10px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            padding: 10px 0;
            z-index: 1001;
            display: none;
        }

        .mobile-dropdown.show {
            display: block;
            animation: slideUp 0.3s ease;
        }

        .mobile-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            text-decoration: none;
            color: #333;
            transition: background 0.2s;
        }

        .mobile-dropdown-item:hover {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .mobile-dropdown-item i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .mobile-dropdown-item span {
            font-size: 0.85rem;
            font-weight: 500;
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
        
        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
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
        
        /* Page Header */
        .page-header {
            margin-bottom: 1.5rem;
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
        
        /* Revenue Stats */
        .revenue-stats {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .revenue-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem 2rem;
            box-shadow: var(--card-shadow);
            text-align: center;
            border-left: 4px solid var(--primary-color);
            max-width: 400px;
            width: 100%;
        }
        
        .revenue-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .revenue-label {
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
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
        
        /* Toast Styling */
        .toast {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .toast-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .toast-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .toast-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .toast-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .table-responsive {
                border-radius: 8px;
            }
        }
        
        .avatar-sm {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .bg-soft-primary { background-color: rgba(3, 158, 209, 0.1); }
        .bg-soft-success { background-color: rgba(46, 204, 113, 0.1); }
        .bg-soft-warning { background-color: rgba(243, 156, 18, 0.1); }
        .bg-soft-danger { background-color: rgba(231, 76, 60, 0.1); }
        .bg-soft-secondary { background-color: rgba(108, 117, 125, 0.1); }
        .bg-soft-dark { background-color: rgba(52, 58, 64, 0.1); }
        
        .text-primary { color: var(--primary-color) !important; }
        .text-success { color: var(--success-color) !important; }
        .text-warning { color: var(--warning-color) !important; }
        .text-danger { color: var(--danger-color) !important; }
        .text-secondary { color: #6c757d !important; }
        .text-dark { color: #343a40 !important; }
        
        /* Grayed out rows for suspended accounts */
        .user-row.suspended {
            opacity: 0.6;
            background-color: #f8f9fa;
        }
        
        /* Modal Styling */
        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .receipt-image {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .no-receipt {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        .no-receipt i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Animation for footer appearance */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .mobile-footer {
            animation: slideUp 0.5s ease;
        }

        /* Adjust for very small screens */
        @media (max-width: 576px) {
            .mobile-footer {
                padding: 10px 0;
            }
            
            .footer-link {
                max-width: 65px;
            }
            
            .footer-link i {
                font-size: 1.1rem;
            }
            
            .footer-link span {
                font-size: 0.6rem;
            }
            
            .main-content {
                padding: 20px 15px 100px 15px;
            }
        }

        /* Header responsive adjustments */
        @media (max-width: 1200px) {
            header {
                padding: 15px 20px;
            }
            
            nav a {
                font-size: 13px;
                padding: 6px 12px;
            }
        }

        @media (max-width: 1100px) {
            nav {
                gap: 5px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            
            nav a {
                font-size: 12px;
                padding: 5px 10px;
            }
        }

        /* Hide header completely */
        header {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="payment.html"><i class="fas fa-credit-card"></i> Make Payment</a>
        <a href="history.html"><i class="fas fa-history"></i> Payment History</a>
        <a href="../includes/communityperformance.php"><i class="fas fa-chart-line"></i> Community Performance</a>
        <a href="report-fault.html"><i class="fas fa-exclamation-triangle"></i> Report Fault</a>
        <a href="profile.html"><i class="fas fa-user"></i> Profile</a>
        <a href="notifications.html"><i class="fas fa-bell"></i> Notifications</a>
        <a href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    
    <div class="overlay" id="overlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Community Performance</h1>
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search users...">
            </div>
        </div>

        <!-- Revenue Stats -->
        <div class="revenue-stats">
            <div class="revenue-card">
                <div class="revenue-value" id="totalRevenue">₦0</div>
                <div class="revenue-label">Total Revenue</div>
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
                                    <th>Flat No</th>
                                    <th>Last Payment</th>
                                    <th>Status</th>
                                    <th>Total Paid</th>
                                    <th class="text-end pe-4">Actions</th>
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
    </main>

    <!-- Mobile Footer -->
    <footer class="mobile-footer d-md-none" role="navigation" aria-label="Primary">
        <a href="../user/dashboard.html" class="footer-link">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="../user/payment.html" class="footer-link">
            <i class="fas fa-wallet"></i>
            <span>Payments</span>
        </a>
        <a href="../user/history.html" class="footer-link">
            <i class="fas fa-history"></i>
            <span>History</span>
        </a>
        <a href="community-performance.html" class="footer-link active">
            <i class="fas fa-chart-line"></i>
            <span>Performance</span>
        </a>
        <a href="#" class="footer-link" id="more-menu">
            <i class="fas fa-ellipsis-h"></i>
            <span>More</span>
        </a>
    </footer>

    <!-- Mobile Dropdown Menu -->
    <div class="mobile-dropdown" id="mobile-dropdown">
        <a href="../user/profile.html" class="mobile-dropdown-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="../user/report-fault.html" class="mobile-dropdown-item">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Report Issue</span>
        </a>
        <a href="../user/notifications.html" class="mobile-dropdown-item">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a>
        <a href="#" class="mobile-dropdown-item" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Payment Receipt</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="receiptContent">
                        <!-- Receipt content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="downloadReceiptBtn">Download</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // API base URL
        const API_BASE_URL = '/waterbill/api';
        let allUsers = [];
        let currentReceiptPath = '';

        // Add sidebar functionality
        function setupSidebar() {
            const menuBtn = document.getElementById('menu-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            if (menuBtn && sidebar && overlay) {
                menuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                });
                
                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
        }

        // Setup mobile dropdown menu
        function setupMobileMenu() {
            const moreMenu = document.getElementById('more-menu');
            const dropdown = document.getElementById('mobile-dropdown');
            const overlay = document.getElementById('overlay');

            if (moreMenu && dropdown) {
                moreMenu.addEventListener('click', function(e) {
                    e.preventDefault();
                    dropdown.classList.toggle('show');
                    
                    if (overlay) {
                        if (dropdown.classList.contains('show')) {
                            overlay.classList.add('show');
                        } else {
                            overlay.classList.remove('show');
                        }
                    }
                });

                // Close dropdown when clicking overlay
                if (overlay) {
                    overlay.addEventListener('click', function() {
                        dropdown.classList.remove('show');
                        overlay.classList.remove('show');
                    });
                }

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!moreMenu.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.classList.remove('show');
                        if (overlay) {
                            overlay.classList.remove('show');
                        }
                    }
                });
            }
        }

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
            
            // Download receipt button
            document.getElementById('downloadReceiptBtn').addEventListener('click', function() {
                if (currentReceiptPath) {
                    // Create a temporary link to trigger download
                    const link = document.createElement('a');
                    link.href = currentReceiptPath;
                    link.download = currentReceiptPath.split('/').pop();
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            });

            // Setup mobile menu
            setupMobileMenu();
        });
        
        // Load users data
        async function loadUsers() {
            try {
                console.log('Loading user data...');
                // In a real implementation, this would be an actual API call
                // For now, we'll use mock data that matches your database structure
                const mockData = await fetchCommunityPerformanceData();
                
                if (mockData.success) {
                    allUsers = mockData.data || [];
                    console.log('Users loaded:', allUsers.length);
                    updateUsersTable(allUsers);
                    updateStats(allUsers);
                } else {
                    throw new Error(mockData.message || 'Failed to load user data');
                }
            } catch (error) {
                console.error('Error loading user data:', error);
                showToast('error', 'Failed to load user data. Please try again.');
            }
        }
        
        // Fetch community performance data (simulating API call)
        async function fetchCommunityPerformanceData() {
            // Simulate API delay
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // This data structure matches your database tables
            return {
                success: true,
                data: [
                    {
                        id: 1,
                        username: "admin",
                        full_name: "Admin User",
                        flat_no: "Admin",
                        last_payment_date: "2025-10-31 08:15:42",
                        status: "active",
                        total_paid: 5000.00,
                        payment_status: "approved",
                        subscription_end_date: "2026-01-31",
                        receipt_path: "uploads/receipts/receipt_admin.pdf"
                    },
                    {
                        id: 3,
                        username: "testuser_1761926901",
                        full_name: "Test User",
                        flat_no: "123 Test Street",
                        last_payment_date: null,
                        status: "active",
                        total_paid: 0.00,
                        payment_status: "none",
                        subscription_end_date: "2025-10-15", // Expired
                        receipt_path: null
                    },
                    {
                        id: 9,
                        username: "admin2",
                        full_name: "magix admin",
                        flat_no: "admin",
                        last_payment_date: null,
                        status: "suspended", // Suspended account
                        total_paid: 0.00,
                        payment_status: "none",
                        subscription_end_date: null,
                        receipt_path: null
                    },
                    {
                        id: 12,
                        username: "ThatNewGuy23",
                        full_name: "Chap Dude David",
                        flat_no: "No. 02",
                        last_payment_date: null,
                        status: "pending",
                        total_paid: 0.00,
                        payment_status: "none",
                        subscription_end_date: null,
                        receipt_path: null
                    },
                    {
                        id: 13,
                        username: "David23",
                        full_name: "Chale Kal Kali",
                        flat_no: "No. 05",
                        last_payment_date: "2025-11-10 03:42:51",
                        status: "active",
                        total_paid: 5000.00,
                        payment_status: "approved",
                        subscription_end_date: "2026-01-10",
                        receipt_path: "uploads/receipts/receipt_13_1762770616.pdf"
                    },
                    {
                        id: 14,
                        username: "Thatnewguys_Girlfriend",
                        full_name: "Shy Devil",
                        flat_no: "Flat 54",
                        last_payment_date: null,
                        status: "pending",
                        total_paid: 0.00,
                        payment_status: "none",
                        subscription_end_date: null,
                        receipt_path: null
                    },
                    {
                        id: 15,
                        username: "Domimatch",
                        full_name: "Dominion Chubiyojo Matthew",
                        flat_no: "Flat 5A",
                        last_payment_date: null,
                        status: "pending",
                        total_paid: 0.00,
                        payment_status: "none",
                        subscription_end_date: null,
                        receipt_path: null
                    },
                    {
                        id: 16,
                        username: "big_xano",
                        full_name: "Benedict ikor Owali",
                        flat_no: "House 9",
                        last_payment_date: null,
                        status: "pending",
                        total_paid: 0.00,
                        payment_status: "none",
                        subscription_end_date: null,
                        receipt_path: null
                    },
                    {
                        id: 17,
                        username: "Princewil1",
                        full_name: "Ikenna Princewill Churchill",
                        flat_no: "Flat b",
                        last_payment_date: null,
                        status: "pending",
                        total_paid: 0.00,
                        payment_status: "none",
                        subscription_end_date: null,
                        receipt_path: null
                    },
                    {
                        id: 18,
                        username: "Isaiah",
                        full_name: "Isaiah Sokomba",
                        flat_no: "No. 03",
                        last_payment_date: "2025-11-15 07:58:20",
                        status: "pending",
                        total_paid: 7000.00,
                        payment_status: "pending",
                        subscription_end_date: null,
                        receipt_path: "uploads/receipts/receipt_18_1763222300.pdf"
                    },
                    {
                        id: 19,
                        username: "Rotimi",
                        full_name: "Abdul Shaqur Hamzat",
                        flat_no: "Flat 4A",
                        last_payment_date: null,
                        status: "pending",
                        total_paid: 0.00,
                        payment_status: "none",
                        subscription_end_date: null,
                        receipt_path: null
                    }
                ]
            };
        }
        
        // Update users table with data
        function updateUsersTable(users) {
            const tbody = document.getElementById('users-table');
            const mobileContainer = document.getElementById('mobile-users-container');
            
            console.log('Updating table with', users.length, 'users');
            
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
                
                // Update counts
                document.getElementById('totalCount').textContent = '0';
                document.getElementById('showingCount').textContent = '0';
                document.getElementById('mobileTotalCount').textContent = '0';
                document.getElementById('mobileShowingCount').textContent = '0';
                return;
            }
            
            let desktopHtml = '';
            let mobileHtml = '';
            
            // Sort users: active first, then by last payment date (most recent first)
            const sortedUsers = [...users].sort((a, b) => {
                if (a.status === 'active' && b.status !== 'active') return -1;
                if (a.status !== 'active' && b.status === 'active') return 1;
                
                const dateA = a.last_payment_date ? new Date(a.last_payment_date) : new Date(0);
                const dateB = b.last_payment_date ? new Date(b.last_payment_date) : new Date(0);
                
                return dateB - dateA; // Most recent first
            });
            
            sortedUsers.forEach(user => {
                const lastPaymentDate = user.last_payment_date ? formatDate(user.last_payment_date) : 'No payments';
                const statusText = getStatusText(user);
                const statusClass = getStatusClass(user);
                const amount = parseFloat(user.total_paid || 0).toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const paymentStatus = user.payment_status || 'none';
                const paymentStatusClass = getPaymentStatusClass(paymentStatus);
                const isSuspended = user.status === 'suspended';
                const hasReceipt = user.receipt_path !== null;
                
                // Desktop row
                desktopHtml += `
                    <tr class="user-row ${isSuspended ? 'suspended' : ''}" data-user-id="${user.id}">
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm bg-soft-${statusClass} rounded-circle p-2">
                                        <i class="fas fa-user text-${statusClass}"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">${user.full_name || user.username || 'Unknown User'}</h6>
                                    <small class="text-muted">@${user.username || 'N/A'}</small>
                                </div>
                            </div>
                        </td>
                        <td>${user.flat_no || 'N/A'}</td>
                        <td>
                            <div>${lastPaymentDate}</div>
                            ${paymentStatus !== 'none' ? `<small class="badge bg-${paymentStatusClass}">${paymentStatus}</small>` : ''}
                        </td>
                        <td>
                            <span class="badge bg-${statusClass} bg-opacity-10 text-${statusClass} p-2">
                                <i class="fas fa-circle me-1 small"></i>
                                ${statusText}
                            </span>
                        </td>
                        <td class="fw-medium">
                            ₦${amount}
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="viewUserDetails(${user.id})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            ${hasReceipt ? `<button class="btn btn-sm btn-outline-success" onclick="viewReceipt('${user.receipt_path}', '${user.full_name || user.username}')">
                                <i class="fas fa-receipt"></i> Receipt
                            </button>` : ''}
                        </td>
                    </tr>`;
                
                // Mobile card
                mobileHtml += `
                    <div class="col-12">
                        <div class="card shadow-sm mb-3 ${isSuspended ? 'suspended' : ''}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-soft-${statusClass} rounded-circle p-2 me-3">
                                            <i class="fas fa-user text-${statusClass}"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">${user.full_name || user.username || 'Unknown User'}</h6>
                                            <small class="text-muted">@${user.username || 'N/A'} • ${user.flat_no || 'N/A'}</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-${statusClass} bg-opacity-10 text-${statusClass} px-3 py-2">
                                        <i class="fas fa-circle me-1 small"></i>
                                        ${statusText}
                                    </span>
                                </div>
                                
                                <div class="row g-2 mt-2">
                                    <div class="col-12">
                                        <div class="text-muted small">Last Payment</div>
                                        <div class="fw-medium">${lastPaymentDate}</div>
                                        ${paymentStatus !== 'none' ? `<small class="badge bg-${paymentStatusClass} mt-1">${paymentStatus}</small>` : ''}
                                    </div>
                                    <div class="col-12 mt-2 pt-2 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small">Total Paid</span>
                                            <span class="fw-medium">₦${amount}</span>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewUserDetails(${user.id})">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </button>
                                            ${hasReceipt ? `<button class="btn btn-sm btn-outline-success" onclick="viewReceipt('${user.receipt_path}', '${user.full_name || user.username}')">
                                                <i class="fas fa-receipt me-1"></i> View Receipt
                                            </button>` : ''}
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
            
            // Update counts
            document.getElementById('totalCount').textContent = users.length;
            document.getElementById('showingCount').textContent = users.length;
            document.getElementById('mobileTotalCount').textContent = users.length;
            document.getElementById('mobileShowingCount').textContent = users.length;
        }
        
        // Helper function to get status text
        function getStatusText(user) {
            // Check if subscription has expired
            if (user.subscription_end_date) {
                const endDate = new Date(user.subscription_end_date);
                const today = new Date();
                if (endDate < today) {
                    return 'Expired';
                }
            }
            
            // Check if user has no payments
            if (parseFloat(user.total_paid || 0) === 0) {
                return 'No Payment';
            }
            
            // Return the original status
            return user.status ? user.status.charAt(0).toUpperCase() + user.status.slice(1) : 'Unknown';
        }
        
        // Helper function to get status class
        function getStatusClass(user) {
            // Check if subscription has expired
            if (user.subscription_end_date) {
                const endDate = new Date(user.subscription_end_date);
                const today = new Date();
                if (endDate < today) {
                    return 'danger';
                }
            }
            
            // Check if user has no payments
            if (parseFloat(user.total_paid || 0) === 0) {
                return 'danger';
            }
            
            // Return the original status class
            switch(user.status) {
                case 'active': return 'success';
                case 'suspended': return 'dark';
                case 'pending': return 'warning';
                default: return 'secondary';
            }
        }
        
        // Helper function to get payment status class
        function getPaymentStatusClass(paymentStatus) {
            switch(paymentStatus) {
                case 'approved': return 'success';
                case 'pending': return 'warning';
                case 'failed': return 'danger';
                default: return 'secondary';
            }
        }
        
        // Format date for display
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-NG', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }
        
        // Update stats cards
        function updateStats(users) {
            if (!users || users.length === 0) return;
            
            const totalRevenue = users.reduce((sum, user) => sum + parseFloat(user.total_paid || 0), 0);
            document.getElementById('totalRevenue').textContent = '₦' + totalRevenue.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
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
        
        // View user details
        function viewUserDetails(userId) {
            // In a real implementation, this would redirect to user details page
            // or open a modal with user information
            showToast('info', `Viewing details for user ID: ${userId}`);
        }
        
        // View receipt
        function viewReceipt(receiptPath, userName) {
            currentReceiptPath = receiptPath;
            
            // Update modal title
            document.getElementById('receiptModalLabel').textContent = `Payment Receipt - ${userName}`;
            
            // Load receipt content
            const receiptContent = document.getElementById('receiptContent');
            
            // Check if receipt is a PDF or image
            const isPdf = receiptPath.toLowerCase().endsWith('.pdf');
            
            if (isPdf) {
                receiptContent.innerHTML = `
                    <div class="text-center">
                        <p class="mb-3">This is a PDF receipt. You can download it using the button below.</p>
                        <div class="mb-3">
                            <i class="fas fa-file-pdf text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <p class="text-muted">${receiptPath.split('/').pop()}</p>
                    </div>
                `;
            } else {
                // Assume it's an image
                receiptContent.innerHTML = `
                    <div class="text-center">
                        <img src="${receiptPath}" alt="Payment Receipt" class="receipt-image img-fluid">
                    </div>
                `;
            }
            
            // Show the modal
            const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
            receiptModal.show();
        }
        
        // Show toast notification
        function showToast(type, message) {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHtml = `
                <div id="${toastId}" class="toast toast-${type}" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-body d-flex justify-content-between align-items-center">
                        <span>${message}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, { delay: 4000 });
            toast.show();
            
            // Remove toast from DOM after it's hidden
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
        
        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                // Redirect to login page
                window.location.href = '../auth/login.html';
            }
        }
    </script>
</body>
</html>