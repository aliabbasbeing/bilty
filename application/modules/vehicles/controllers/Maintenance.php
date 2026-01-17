<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Maintenance Controller
 * 
 * Handles vehicle maintenance operations
 */
class Maintenance extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('maintenance_model');
    }
    
    /**
     * Maintenance page (vehicle_maintenance.php)
     */
    public function index()
    {
        $this->data['page_title'] = 'Vehicle Maintenance - Bilty Management';
        
        // Get all maintenance records
        $this->data['records'] = $this->maintenance_model->get_all();
        
        $this->render('vehicles/maintenance', $this->data);
    }
    
    /**
     * Save maintenance record
     */
    public function save()
    {
        if ($this->input->method() === 'post') {
            $data = array(
                'vehicle_no' => $this->input->post('vehicle_no', TRUE),
                'date' => $this->input->post('date', TRUE),
                'description' => $this->input->post('description', TRUE),
                'cost' => $this->input->post('cost', TRUE),
                'notes' => $this->input->post('notes', TRUE)
            );
            
            $result = $this->maintenance_model->insert($data);
            
            if ($result) {
                $this->session->set_flashdata('success', 'Maintenance record added successfully.');
            } else {
                $this->session->set_flashdata('error', 'Failed to add maintenance record.');
            }
        }
        
        redirect('vehicles/maintenance');
    }
}
