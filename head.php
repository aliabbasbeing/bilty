<?php
// head.php - include this inside every page's <head>.
// Example usage in a page: <head><?php include 'head.php'; ?>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />

<!-- Bootstrap 5 CSS (Local) -->
<link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">

<!-- FontAwesome Icons -->
<link rel="stylesheet" href="fontawesome/css/all.min.css">

<!-- Custom Modern Theme CSS -->
<link rel="stylesheet" href="assets/css/theme.css">

<!-- Theme variables and base styles -->
<style>
  :root {
    --primary: #97113a;
    --primary-hover: #b31547;
    --page: #f0f2f5;
    --text: #111827;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
  }

  /* Base styles */
  html, body {
    color: var(--text);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  }

  body {
    background-color: var(--page);
  }

  /* Utility classes for consistency */
  .bg-primary { background-color: var(--primary) !important; }
  .text-primary { color: var(--primary) !important; }
  .bg-page { background-color: var(--page) !important; }
  .bg-success { background-color: var(--success) !important; }
  .bg-warning { background-color: var(--warning) !important; }
  .bg-danger { background-color: var(--danger) !important; }
  .bg-info { background-color: var(--info) !important; }
  
  /* Focus styles */
  a:focus, button:focus, input:focus, select:focus, textarea:focus {
    outline: 3px solid rgba(151, 17, 58, 0.14);
    outline-offset: 2px;
  }
</style>

<!-- Bootstrap 5 JS Bundle with Popper (Local) -->
<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>