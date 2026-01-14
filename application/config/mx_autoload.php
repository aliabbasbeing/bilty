<?php
/**
 * MX/HMVC Autoloader
 * Load the core MX files before CodeIgniter tries to instantiate controllers
 */

// Load MX core files
require_once APPPATH . 'third_party/MX/Modules.php';
require_once APPPATH . 'third_party/MX/Controller.php';
