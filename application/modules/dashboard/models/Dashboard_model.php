<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard Model
 * 
 * Handles dashboard statistics and data
 */
class Dashboard_model extends CI_Model
{
    /**
     * Get dashboard statistics
     * 
     * @return array
     */
    public function get_statistics()
    {
        $stats = array();
        
        // Total bilties
        $query = $this->db->select('COUNT(*) as total')
                          ->from('consignments')
                          ->get();
        $stats['total_bilties'] = $query->row()->total;
        
        // Total amount
        $query = $this->db->select('SUM(amount) as total')
                          ->from('consignments')
                          ->get();
        $stats['total_amount'] = $query->row()->total ?? 0;
        
        // Pending balance
        $query = $this->db->select('SUM(balance) as total')
                          ->from('consignments')
                          ->where('balance >', 0)
                          ->get();
        $stats['pending_balance'] = $query->row()->total ?? 0;
        
        // This month count
        $query = $this->db->select('COUNT(*) as total')
                          ->from('consignments')
                          ->where('MONTH(date)', date('m'))
                          ->where('YEAR(date)', date('Y'))
                          ->get();
        $stats['month_count'] = $query->row()->total;
        
        return $stats;
    }
    
    /**
     * Search bilty by number
     * 
     * @param string $bilty_no
     * @return object|null
     */
    public function search_bilty($bilty_no)
    {
        $query = $this->db->select('c.*, cp.name as company_name')
                          ->from('consignments c')
                          ->join('companies cp', 'cp.id = c.company_id', 'left')
                          ->where('c.bilty_no', $bilty_no)
                          ->limit(1)
                          ->get();
        
        return $query->num_rows() > 0 ? $query->row() : null;
    }
}
