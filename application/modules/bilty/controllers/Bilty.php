<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bilty Controller
 * 
 * Handles bilty management operations
 */
class Bilty extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('bilty_model');
        $this->load->library('form_validation');
    }
    
    /**
     * List all bilties (view_bilty.php)
     */
    public function index()
    {
        $this->data['page_title'] = 'All Bilties - Bilty Management';
        
        // Get filters
        $filters = array(
            'search' => $this->input->get('q', TRUE),
            'company_id' => $this->input->get('company', TRUE)
        );
        
        // Get bilties
        $this->data['bilties'] = $this->bilty_model->get_all($filters);
        $this->data['companies'] = $this->bilty_model->get_companies();
        $this->data['filters'] = $filters;
        
        $this->render('bilty/index', $this->data);
    }
    
    /**
     * Manage bills (manage_bills.php)
     */
    public function manage()
    {
        $this->data['page_title'] = 'Manage Bills - Bilty Management';
        
        // Get filters
        $filters = array(
            'search' => $this->input->get('q', TRUE),
            'company_id' => $this->input->get('company', TRUE)
        );
        
        $this->data['bilties'] = $this->bilty_model->get_all($filters);
        $this->data['companies'] = $this->bilty_model->get_companies();
        $this->data['filters'] = $filters;
        
        $this->render('bilty/manage', $this->data);
    }
    
    /**
     * Add new bilty form (add_bilty.php)
     */
    public function add()
    {
        $this->data['page_title'] = 'Add New Bilty - Bilty Management';
        
        // Get companies
        $this->data['companies'] = $this->bilty_model->get_companies();
        $this->data['auto_bilty_no'] = $this->bilty_model->get_next_bilty_number();
        
        $this->data['errors'] = array();
        $this->data['success'] = '';
        
        $this->render('bilty/add', $this->data);
    }
    
    /**
     * Save new bilty
     */
    public function save()
    {
        // Validation rules
        $this->form_validation->set_rules('bilty_no', 'Bilty Number', 'required|max_length[50]');
        $this->form_validation->set_rules('date', 'Date', 'required');
        $this->form_validation->set_rules('company', 'Company', 'required|integer');
        $this->form_validation->set_rules('amount', 'Amount', 'required|numeric');
        $this->form_validation->set_rules('advance', 'Advance', 'numeric');
        
        if ($this->form_validation->run() === FALSE) {
            // Validation failed
            $this->data['errors'] = validation_errors();
            $this->add();
            return;
        }
        
        // Prepare data
        $fixed = $this->input->post('fixed') === '1';
        $rate_input = $this->input->post('rate', TRUE);
        $km = $this->input->post('km', TRUE);
        
        // Calculate amount
        if ($fixed) {
            $amount = $this->input->post('amount', TRUE);
            $rate_to_save = 0.0;
            $rate_type = 'Fixed';
        } else {
            $amount = $km * $rate_input;
            $rate_to_save = $rate_input;
            $rate_type = 'PerKM';
        }
        
        $advance = $this->input->post('advance', TRUE) ?: 0;
        $balance = $amount - $advance;
        
        // Additional info
        $vehicle_owner = $this->input->post('vehicle_owner') === 'rental' ? 'rental' : 'own';
        $driver_number = $this->input->post('driver_number', TRUE);
        $details = $this->input->post('details', TRUE);
        
        $extra = array();
        $extra[] = "Vehicle: " . ($vehicle_owner === 'rental' ? "Rental" : "Own");
        if ($driver_number) {
            $extra[] = "Driver number: " . $driver_number;
        }
        if (!empty($extra)) {
            if ($details) $details .= "\n\n";
            $details .= "Additional info:\n" . implode("\n", $extra);
        }
        
        $data = array(
            'bilty_no' => $this->input->post('bilty_no', TRUE),
            'date' => $this->input->post('date', TRUE),
            'company_id' => $this->input->post('company', TRUE),
            'vehicle_no' => $this->input->post('vehicle_no', TRUE),
            'driver_name' => $this->input->post('driver_name', TRUE),
            'vehicle_type' => $this->input->post('vehicle_type', TRUE),
            'sender_name' => $this->input->post('sender_name', TRUE),
            'from_city' => $this->input->post('from_city', TRUE),
            'to_city' => $this->input->post('to_city', TRUE),
            'qty' => $this->input->post('qty', TRUE) ?: 0,
            'details' => $details,
            'km' => $km ?: 0,
            'rate' => $rate_to_save,
            'amount' => $amount,
            'advance' => $advance,
            'balance' => $balance
        );
        
        // Add rate_type if column exists
        if ($this->db->field_exists('rate_type', 'consignments')) {
            $data['rate_type'] = $rate_type;
        }
        
        // Insert
        $insert_id = $this->bilty_model->insert($data);
        
        if ($insert_id) {
            $this->session->set_flashdata('success', 'Bilty has been saved successfully.');
            redirect('bilty/add');
        } else {
            $this->session->set_flashdata('error', 'Failed to save bilty. Please try again.');
            redirect('bilty/add');
        }
    }
    
    /**
     * View single bilty
     */
    public function view($id)
    {
        $bilty = $this->bilty_model->get_by_id($id);
        
        if (!$bilty) {
            show_404();
        }
        
        $this->data['page_title'] = 'View Bilty #' . $bilty->bilty_no;
        $this->data['bilty'] = $bilty;
        
        $this->render('bilty/view', $this->data);
    }
    
    /**
     * Print bilty (view_bilty_print.php)
     */
    public function print_bilty($id)
    {
        $bilty = $this->bilty_model->get_by_id($id);
        
        if (!$bilty) {
            show_404();
        }
        
        $this->load->library('pdf_generator');
        
        // Convert object to array for PDF
        $bilty_data = (array)$bilty;
        
        // Generate and display PDF
        $this->pdf_generator->generate_bilty($bilty_data, 'bilty_' . $bilty->bilty_no . '.pdf', 'I');
    }
    
    /**
     * Delete bilty
     */
    public function delete($id)
    {
        if ($this->bilty_model->delete($id)) {
            $this->session->set_flashdata('success', 'Bilty deleted successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to delete bilty.');
        }
        
        redirect('bilty');
    }
}
