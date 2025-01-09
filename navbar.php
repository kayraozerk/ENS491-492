<!-- Navbar -->
<nav class="navbar navbar-dark navbar-expand-lg fixed-top bg-secondary">
    <div class="container-fluid d-flex align-items-center position-relative">
        <!-- Back Arrow and Logo -->
        <div class="d-flex align-items-center">
            <a href="index.php" class="text-white" style="text-decoration:none; font-size:1.2rem; margin-right: 20px;">
                <i class="fas fa-arrow-left me-3"></i>
            </a>
            <a href="index.php" class="navbar-brand d-flex align-items-center ms-5">
                <img src="https://yabangee.com/wp-content/uploads/sabancı-university-2.jpg" alt="Logo" style="height: 40px;">
            </a>
        </div>

        <!-- Centered Title -->
        <div class="navbar-title position-absolute" style="left: 50%; transform: translateX(-50%); font-size: 1.5rem; font-weight: bold; color: white;">
            Teaching Awards
        </div>

        <!-- Toggler for Mobile -->
        <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle text-white" id="welcomeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Welcome, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="welcomeDropdown">
                        <li>
                            <a class="dropdown-item" href="index.php">
                                <i class="fas fa-home me-2"></i> Home
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-question-circle me-2"></i> Help
                            </a>
                        </li>
                        <div class="dropdown-divider"></div>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>