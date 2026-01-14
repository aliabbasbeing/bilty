<?php
$current_page = $this->router->fetch_method();
$current_controller = $this->router->fetch_class();

$navItems = [
    ['label' => 'Home', 'href' => 'dashboard', 'icon' => 'fa-solid fa-house'],
    ['label' => 'New Bilty', 'href' => 'bilty/add', 'icon' => 'fa-solid fa-plus'],
    ['label' => 'All Bilties', 'href' => 'bilty', 'icon' => 'fa-solid fa-rectangle-list'],
    ['label' => 'Manage Bills', 'href' => 'bilty/manage', 'icon' => 'fa-solid fa-file-invoice-dollar'],
    ['label' => 'Maintenance', 'href' => 'vehicles/maintenance', 'icon' => 'fa-solid fa-screwdriver-wrench'],
    ['label' => 'Reports', 'href' => 'finance/reports', 'icon' => 'fa-solid fa-chart-column'],
];
?>
<style>
    .modern-header {
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: sticky;
        top: 0;
        z-index: 1000;
        padding: 1rem 0;
    }
    
    .header-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .header-logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        color: var(--primary);
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .header-nav {
        display: flex;
        gap: 0.5rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: 10px;
        text-decoration: none;
        color: #6b7280;
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    
    .nav-link:hover {
        background: #f3f4f6;
        color: var(--primary);
    }
    
    .nav-link.active {
        background: var(--primary);
        color: white;
    }
    
    @media (max-width: 768px) {
        .header-nav {
            display: none;
        }
    }
</style>

<header class="modern-header">
    <div class="header-container">
        <a href="<?php echo site_url('dashboard'); ?>" class="header-logo">
            <i class="fa-solid fa-truck-fast"></i>
            <span>Bilty MS</span>
        </a>
        
        <nav>
            <ul class="header-nav">
                <?php foreach ($navItems as $item): ?>
                    <li>
                        <a href="<?php echo site_url($item['href']); ?>" 
                           class="nav-link <?php echo (strpos($this->uri->uri_string(), $item['href']) === 0) ? 'active' : ''; ?>">
                            <i class="<?php echo $item['icon']; ?>"></i>
                            <span><?php echo $item['label']; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>
