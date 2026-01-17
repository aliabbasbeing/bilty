<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payment Model
 * 
 * Handles payment and reporting operations
 */
class Payment_model extends CI_Model
{
    /**
     * Get summary statistics
     */
    public function get_summary($filters = array())
    {
        $this->db->select('
            COUNT(*) as total_bilties,
            SUM(amount) as total_amount,
            SUM(advance) as total_advance,
            SUM(balance) as total_balance
        ')
        ->from('consignments');
        
        $this->_apply_filters($filters);
        
        $query = $this->db->get();
        return $query->row();
    }
    
    /**
     * Get daily summary
     */
    public function get_daily_summary($filters = array())
    {
        $this->db->select('
            date,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            SUM(advance) as total_advance,
            SUM(balance) as total_balance
        ')
        ->from('consignments')
        ->group_by('date')
        ->order_by('date', 'DESC');
        
        $this->_apply_filters($filters);
        
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Get company breakdown
     */
    public function get_company_breakdown($filters = array())
    {
        $this->db->select('
            cp.name as company_name,
            COUNT(*) as count,
            SUM(c.amount) as total_amount,
            SUM(c.advance) as total_advance,
            SUM(c.balance) as total_balance
        ')
        ->from('consignments c')
        ->join('companies cp', 'cp.id = c.company_id', 'left')
        ->group_by('c.company_id')
        ->order_by('total_amount', 'DESC');
        
        $this->_apply_filters($filters, 'c');
        
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Get top routes
     */
    public function get_top_routes($filters = array())
    {
        $this->db->select('
            from_city,
            to_city,
            COUNT(*) as count,
            SUM(amount) as total_amount
        ')
        ->from('consignments')
        ->group_by('from_city, to_city')
        ->order_by('total_amount', 'DESC')
        ->limit(10);
        
        $this->_apply_filters($filters);
        
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Update payment/advance
     */
    public function update_payment($bilty_id, $advance)
    {
        // Get current bilty
        $bilty = $this->db->where('id', $bilty_id)
                         ->get('consignments')
                         ->row();
        
        if (!$bilty) {
            return false;
        }
        
        // Calculate new balance
        $balance = $bilty->amount - $advance;
        
        // Update
        return $this->db->where('id', $bilty_id)
                        ->update('consignments', array(
                            'advance' => $advance,
                            'balance' => $balance
                        ));
    }
    
    /**
     * Get all companies
     */
    public function get_companies()
    {
        $query = $this->db->select('id, name')
                          ->from('companies')
                          ->order_by('name', 'ASC')
                          ->get();
        
        return $query->result();
    }
    
    /**
     * Apply filters to query
     */
    private function _apply_filters($filters, $table_alias = '')
    {
        $prefix = $table_alias ? $table_alias . '.' : '';
        
        if (!empty($filters['start_date'])) {
            $this->db->where($prefix . 'date >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $this->db->where($prefix . 'date <=', $filters['end_date']);
        }
        
        if (!empty($filters['company_id'])) {
            $this->db->where($prefix . 'company_id', $filters['company_id']);
        }
    }
}
