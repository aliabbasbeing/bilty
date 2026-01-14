<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard Controller
 * 
 * Main dashboard with statistics and quick search
 */
class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dashboard_model');
    }
    
    /**
     * Dashboard index page
     */
    public function index()
    {
        $this->data['page_title'] = 'Dashboard - Bilty Management System';
        
        // Get statistics
        $this->data['stats'] = $this->dashboard_model->get_statistics();
        
        // Handle bilty search
        $this->data['bilty_no'] = '';
        $this->data['bilty_row'] = null;
        
        if ($this->input->post('bilty_no')) {
            $bilty_no = $this->input->post('bilty_no', TRUE);
            $this->data['bilty_no'] = $bilty_no;
            $this->data['bilty_row'] = $this->dashboard_model->search_bilty($bilty_no);
        }
        
        $this->render('dashboard/index', $this->data);
    }
}
