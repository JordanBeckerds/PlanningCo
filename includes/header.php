<?php
// Expects session to already be started and $_SESSION set by the calling page.
?>
<body class="bg-gray-50 min-h-screen flex flex-col">

<header class="bg-indigo-600 text-white shadow">
  <div class="container mx-auto px-4 py-4 flex justify-between items-center">
    <a href="../public/index.php" class="text-2xl font-bold tracking-tight">PlanningCo</a>

    <button id="menu-btn" class="md:hidden focus:outline-none" onclick="toggleMenu()" aria-label="Toggle menu" aria-expanded="false" aria-controls="mobile-menu">
      <svg id="menu-icon" class="w-6 h-6 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg"
        fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>

    <nav class="hidden md:flex space-x-4 text-sm md:text-base items-center">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="../public/dashboard.php" class="hover:underline">Dashboard</a>
        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
          <a href="../public/schedule.php" class="hover:underline">Planning</a>
        <?php else: ?>
          <a href="../public/schedule_employee.php" class="hover:underline">Mon planning</a>
        <?php endif; ?>
        <a href="../public/logout.php" class="hover:underline opacity-80">Déconnexion</a>
      <?php else: ?>
        <a href="../public/login.php" class="hover:underline">Connexion</a>
      <?php endif; ?>
    </nav>
  </div>

  <div id="mobile-menu" class="md:hidden overflow-hidden max-h-0 transition-[max-height] duration-300 ease-in-out bg-indigo-700">
    <nav class="flex flex-col text-sm">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="../public/dashboard.php" class="px-4 py-3 border-b border-indigo-600 hover:bg-indigo-800">Dashboard</a>
        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
          <a href="../public/schedule.php" class="px-4 py-3 border-b border-indigo-600 hover:bg-indigo-800">Planning</a>
        <?php else: ?>
          <a href="../public/schedule_employee.php" class="px-4 py-3 border-b border-indigo-600 hover:bg-indigo-800">Mon planning</a>
        <?php endif; ?>
        <a href="../public/logout.php" class="px-4 py-3 hover:bg-indigo-800">Déconnexion</a>
      <?php else: ?>
        <a href="../public/login.php" class="px-4 py-3 hover:bg-indigo-800">Connexion</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<script>
function toggleMenu() {
  const menu = document.getElementById('mobile-menu');
  const icon = document.getElementById('menu-icon');
  const btn  = document.getElementById('menu-btn');
  const open = menu.style.maxHeight && menu.style.maxHeight !== '0px';
  menu.style.maxHeight = open ? '0px' : menu.scrollHeight + 'px';
  icon.classList.toggle('rotate-90', !open);
  btn.setAttribute('aria-expanded', String(!open));
}
</script>
