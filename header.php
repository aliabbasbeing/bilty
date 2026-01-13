<?php
// header.php - Modern Navigation Header
$current = basename($_SERVER['PHP_SELF']);

$navItems = [
    [
        'label' => 'Home',
        'href'  => 'index.php',
        'match' => ['index.php'],
        'icon'  => 'fa-solid fa-house'
    ],
    [
        'label' => 'New Bilty',
        'href'  => 'add_bilty.php',
        'match' => ['add_bilty.php'],
        'icon'  => 'fa-solid fa-plus'
    ],
    [
        'label' => 'All Bilties',
        'href'  => 'view_bilty.php',
        'match' => ['view_bilty.php', 'print_bulk.php'],
        'icon'  => 'fa-solid fa-rectangle-list'
    ],
    [
        'label' => 'Manage Bills',
        'href'  => 'manage_bills.php',
        'match' => ['manage_bills.php'],
        'icon'  => 'fa-solid fa-file-invoice-dollar'
    ],
    [
        'label' => 'Maintenance',
        'href'  => 'vehicle_maintenance.php',
        'match' => ['vehicle_maintenance.php'],
        'icon'  => 'fa-solid fa-screwdriver-wrench'
    ],
    [
        'label' => 'Reports',
        'href'  => 'reports.php',
        'match' => ['reports.php'],
        'icon'  => 'fa-solid fa-chart-column'
    ],
];

function isActiveNav(array $item, string $current): bool {
    if (empty($item['match'])) return false;
    return in_array($current, $item['match'], true);
}
?>
<style>
  .modern-header {
    background: linear-gradient(135deg, #97113a 0%, #b31547 100%);
    box-shadow: 0 2px 8px rgba(151, 17, 58, 0.15);
  }
  
  .modern-header-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
  }
  
  .modern-header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 70px;
  }
  
  .header-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: white;
    transition: opacity 0.2s;
  }
  
  .header-brand:hover {
    opacity: 0.9;
  }
  
  .brand-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
  }
  
  .brand-text {
    display: flex;
    flex-direction: column;
  }
  
  .brand-title {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.25rem;
  }
  
  .brand-tagline {
    font-size: 0.75rem;
    opacity: 0.9;
    font-weight: 500;
  }
  
  .desktop-nav {
    display: none;
    gap: 0.5rem;
    align-items: center;
  }
  
  @media (min-width: 768px) {
    .desktop-nav {
      display: flex;
    }
  }
  
  .nav-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    font-size: 0.9375rem;
    font-weight: 600;
    transition: all 0.2s;
  }
  
  .nav-link:hover {
    background: rgba(255, 255, 255, 0.15);
    color: white;
  }
  
  .nav-link.active {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  }
  
  .mobile-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    font-size: 1.25rem;
    cursor: pointer;
    transition: background 0.2s;
  }
  
  @media (min-width: 768px) {
    .mobile-toggle {
      display: none;
    }
  }
  
  .mobile-toggle:hover {
    background: rgba(255, 255, 255, 0.2);
  }
  
  .mobile-menu {
    display: none;
    background: rgba(151, 17, 58, 0.98);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  .mobile-menu.show {
    display: block;
  }
  
  .mobile-menu-inner {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .mobile-nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    font-size: 0.9375rem;
    font-weight: 600;
    transition: all 0.2s;
  }
  
  .mobile-nav-link:hover {
    background: rgba(255, 255, 255, 0.15);
    color: white;
  }
  
  .mobile-nav-link.active {
    background: rgba(255, 255, 255, 0.25);
    color: white;
  }
</style>

<header class="modern-header">
  <div class="modern-header-container">
    <div class="modern-header-inner">
      <!-- Brand/Logo -->
      <a href="index.php" class="header-brand">
        <div class="brand-icon">
          <i class="fa-solid fa-truck-fast"></i>
        </div>
        <div class="brand-text">
          <div class="brand-title">Bilty Management</div>
          <div class="brand-tagline">Clear. Fast. Reliable.</div>
        </div>
      </a>

      <!-- Desktop Navigation -->
      <nav class="desktop-nav">
        <?php foreach ($navItems as $item):
            $active = isActiveNav($item, $current);
            $icon   = $item['icon'] ?? '';
        ?>
          <a
            href="<?php echo htmlspecialchars($item['href']); ?>"
            class="nav-link <?php echo $active ? 'active' : ''; ?>"
            <?php if ($active): ?>aria-current="page"<?php endif; ?>
          >
            <?php if($icon): ?>
              <i class="<?php echo htmlspecialchars($icon); ?>"></i>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($item['label']); ?></span>
          </a>
        <?php endforeach; ?>
      </nav>

      <!-- Mobile Toggle Button -->
      <button 
        type="button"
        class="mobile-toggle" 
        id="navToggle"
        aria-label="Toggle menu"
        aria-expanded="false"
      >
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobileMenu" class="mobile-menu">
    <div class="mobile-menu-inner">
      <?php foreach ($navItems as $item):
        $active = isActiveNav($item, $current);
        $icon   = $item['icon'] ?? '';
      ?>
        <a
          href="<?php echo htmlspecialchars($item['href']); ?>"
          class="mobile-nav-link <?php echo $active ? 'active' : ''; ?>"
          <?php if ($active): ?>aria-current="page"<?php endif; ?>
        >
          <?php if($icon): ?>
            <i class="<?php echo htmlspecialchars($icon); ?>"></i>
          <?php endif; ?>
          <span><?php echo htmlspecialchars($item['label']); ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    (function() {
      const toggleBtn = document.getElementById('navToggle');
      const menu = document.getElementById('mobileMenu');
      if (!toggleBtn || !menu) return;

      toggleBtn.addEventListener('click', function() {
        const isShown = menu.classList.toggle('show');
        toggleBtn.setAttribute('aria-expanded', isShown ? 'true' : 'false');
      });

      // Close menu when a link is clicked
      menu.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
          menu.classList.remove('show');
          toggleBtn.setAttribute('aria-expanded', 'false');
        });
      });

      // Close on Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && menu.classList.contains('show')) {
          menu.classList.remove('show');
          toggleBtn.setAttribute('aria-expanded', 'false');
        }
      });
    })();
  </script>
</header>