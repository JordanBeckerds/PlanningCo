<body class="bg-gray-50 min-h-screen flex flex-col">
  <header class="bg-indigo-600 text-white shadow">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
      <a href="../public/index.php" class="text-2xl font-bold">PlanningCo</a>

      <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Mobile Menu Button -->
          <button class="md:hidden focus:outline-none" onclick="toggleMenu()" aria-label="Toggle menu">
            <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <!-- Desktop Nav -->
          <nav class="hidden md:flex space-x-4 text-sm md:text-base">
            <?php if (isset($_SESSION['user_id'])): ?>
              <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="../public/dashboard.php" class="hover:underline">Dashboard</a>
                <a href="../public/schedule.php" class="hover:underline">Planning</a>
                <a href="../public/logout.php" class="hover:underline">Déconnexion</a>
              <?php elseif ($_SESSION['user_role'] === 'employee'): ?>
                <a href="../public/dashboard.php" class="hover:underline">Dashboard</a>
                <a href="../public/schedule_employee.php" class="hover:underline">Planning</a>
                <a href="../public/logout.php" class="hover:underline">Déconnexion</a>
              <?php endif; ?>
            <?php else: ?>
              <a href="/public/login.php" class="hover:underline">Connexion</a>
            <?php endif; ?>
          </nav>
        </div>

        <!-- Mobile Nav Menu -->
        <div id="mobile-menu" class="md:hidden px-4 pb-4 hidden">
          <nav class="flex flex-col space-y-2 text-md">
            <?php if (isset($_SESSION['user_id'])): ?>
              <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="../public/dashboard.php" class="hover:underline">Dashboard</a>
                <a href="../public/schedule.php" class="hover:underline">Planning</a>
                <a href="../public/logout.php" class="hover:underline">Déconnexion</a>
              <?php elseif ($_SESSION['user_role'] === 'employee'): ?>
                <a href="../public/dashboard.php" class="hover:underline">Dashboard</a>
                <a href="../public/schedule_employee.php" class="hover:underline">Planning</a>
                <a href="../public/logout.php" class="hover:underline">Déconnexion</a>
              <?php endif; ?>
            <?php else: ?>
              <a href="/public/login.php" class="hover:underline">Connexion</a>
            <?php endif; ?>
          </nav>
        </div>    
      <?php endif; ?>
  </header>

  <main class="flex-grow container mx-auto px-4 py-6">

        <?php if (isset($_SESSION['user_id'])): ?>
          
        <?php endif; ?>