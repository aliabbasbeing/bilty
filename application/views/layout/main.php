<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?php echo isset($page_title) ? $page_title : 'Bilty Management System'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/bootstrap/css/bootstrap.min.css'); ?>">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="<?php echo base_url('fontawesome/css/all.min.css'); ?>">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/theme.css'); ?>">
    
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

        html, body {
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            background-color: var(--page);
        }

        .bg-primary { background-color: var(--primary) !important; }
        .text-primary { color: var(--primary) !important; }
    </style>
    
    <?php if (isset($additional_css)): ?>
        <?php echo $additional_css; ?>
    <?php endif; ?>
</head>
<body>
    <?php $this->load->view('layout/header'); ?>
    
    <main class="container-fluid" style="max-width: 1400px; margin: 0 auto; padding: 2rem 1rem;">
        <?php 
        if (isset($content_view)) {
            $this->load->view($content_view); 
        }
        ?>
    </main>
    
    <?php $this->load->view('layout/footer'); ?>
    
    <!-- Bootstrap 5 JS -->
    <script src="<?php echo base_url('assets/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
</body>
</html>
