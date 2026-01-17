<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payments Controller
 * 
 * Handles payment updates and reports
 */
class Payments extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('payment_model');
    }
    
    /**
     * Reports page (reports.php)
     */
    public function reports()
    {
        $this->data['page_title'] = 'Reports & Analytics - Bilty Management';
        
        // Get filter parameters
        $start_date = $this->input->get('start') ?: date('Y-m-01');
        $end_date = $this->input->get('end') ?: date('Y-m-d');
        $company_id = $this->input->get('company_id') ?: 0;
        
        $filters = array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'company_id' => $company_id
        );
        
        // Get report data
        $this->data['summary'] = $this->payment_model->get_summary($filters);
        $this->data['daily_summary'] = $this->payment_model->get_daily_summary($filters);
        $this->data['company_breakdown'] = $this->payment_model->get_company_breakdown($filters);
        $this->data['top_routes'] = $this->payment_model->get_top_routes($filters);
        $this->data['filters'] = $filters;
        $this->data['companies'] = $this->payment_model->get_companies();
        
        $this->render('finance/reports', $this->data);
    }
    
    /**
     * Update payment
     */
    public function update_payment()
    {
        if ($this->input->method() === 'post') {
            $bilty_id = $this->input->post('bilty_id', TRUE);
            $advance = $this->input->post('advance', TRUE);
            
            if ($bilty_id && $advance !== null) {
                $result = $this->payment_model->update_payment($bilty_id, $advance);
                
                if ($result) {
                    $this->session->set_flashdata('success', 'Payment updated successfully.');
                } else {
                    $this->session->set_flashdata('error', 'Failed to update payment.');
                }
            }
        }
        
        redirect($this->input->server('HTTP_REFERER') ?: 'bilty/manage');
    }
}
