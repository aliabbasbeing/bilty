<?php
// head.php - include this inside every page's <head>.
// Example usage in a page: <head><?php include 'head.php'; ?>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

<!-- Tailwind CSS (compiled) -->
<link href="output.css" rel="stylesheet">

<!-- FontAwesome Icons -->
<link rel="stylesheet" href="fontawesome/css/all.min.css">

<!-- Custom Modern Theme CSS -->
<link rel="stylesheet" href="assets/css/theme.css">

<!-- Immediate CSS fallback and theme variables (applies before other CSS loads) -->
<style>
  :root {
    --primary: #97113a;
    --page: #e4d7e8;
    --text: #111827;
  }

  /* minimal fallback utilities so header/buttons are themed even if CDN blocked */
  .bg-primary { background-color: var(--primary) !important; }
  .text-primary { color: var(--primary) !important; }
  .bg-page { background-color: var(--page) !important; }
  .focus-ring-primary:focus { box-shadow: 0 0 0 3px rgba(151,17,58,0.14); outline: none; }
  .btn-primary { background-color: var(--primary); color: #fff; padding: .45rem .9rem; border-radius: .375rem; display:inline-block; text-decoration:none; }
  html,body { color: var(--text); -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; }
</style>

<!-- Tailwind runtime config (MUST be before the CDN script) -->
<script>
  window.tailwind = window.tailwind || {};
  window.tailwind.config = {
    theme: {
      extend: {
        colors: {
          primary: '#97113a',
          page: '#e4d7e8'
        }
      }
    }
  };
</script>

<!-- Tailwind CDN (optional, commented out as we use compiled version) -->
<!-- <script src="https://cdn.tailwindcss.com"></script> -->

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

<!-- tiny helper style (optional) -->
<style>
  a:focus { outline: 3px solid rgba(151,17,58,0.14); outline-offset: 2px; }
</style>