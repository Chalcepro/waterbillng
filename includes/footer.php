<div class="footer">
    <nav class="footer-nav">
        <a href="/user/dashboard.php" class="footer-link">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="/user/payment.php" class="footer-link">
            <i class="fas fa-wallet"></i>
            <span>Payments</span>
        </a>
        <a href="/user/history.php" class="footer-link">
            <i class="fas fa-history"></i>
            <span>History</span>
        </a>
        <a href="/user/profile.php" class="footer-link">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="/logout.php" class="footer-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

<style>
    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: #0369a1;
        color: white;
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
        z-index: 1000;
    }

    .footer-nav {
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 0.5rem 0;
    }

    .footer-link {
        text-align: center;
        color: white;
        text-decoration: none;
        font-size: 0.875rem;
        transition: color 0.2s ease;
    }

    .footer-link i {
        font-size: 1.25rem;
        display: block;
        margin-bottom: 0.25rem;
    }

    .footer-link:hover {
        color: #7dd3fc;
    }

    @media (min-width: 768px) {
        .footer-link {
            font-size: 1rem;
        }

        .footer-link i {
            font-size: 1.5rem;
        }
    }
</style>
