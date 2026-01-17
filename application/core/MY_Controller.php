<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller - Base Controller for Session Management
 * 
 * All controllers should extend this base controller to ensure
 * proper session checking and common functionality.
 */
class MY_Controller extends MX_Controller
{
    protected $data = array();
    
    public function __construct()
    {
        parent::__construct();
        
        // Load necessary libraries and helpers
        $this->load->database();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->helper('bilty');
        
        // Initialize common data
        $this->data['page_title'] = 'Bilty Management System';
    }
    
    /**
     * Render view with layout
     */
    protected function render($view, $data = array())
    {
        $data = array_merge($this->data, $data);
        $data['content_view'] = $view;
        $this->load->view('layout/main', $data);
    }
}

/**
 * Auth_Controller - Controller requiring authentication
 * 
 * Use this for pages that require user login
 */
class Auth_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Check if user is logged in
        if (!$this->session->userdata('logged_in')) {
            redirect('auth/login');
        }
        
        // Set user data
        $this->data['user'] = $this->session->userdata('user_data');
    }
}

/**
 * Public_Controller - Controller for public pages
 * 
 * Use this for pages that don't require authentication (login, register, etc.)
 */
class Public_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // If already logged in, redirect to dashboard
        if ($this->session->userdata('logged_in')) {
            redirect('dashboard');
        }
    }
}
