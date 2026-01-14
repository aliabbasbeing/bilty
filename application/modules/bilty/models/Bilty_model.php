<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bilty Model
 * 
 * Handles all bilty CRUD operations using Query Builder
 */
class Bilty_model extends CI_Model
{
    private $table = 'consignments';
    
    /**
     * Get all bilties with filters
     * 
     * @param array $filters
     * @return array
     */
    public function get_all($filters = array())
    {
        $this->db->select('c.*, cp.name as company_name')
                 ->from($this->table . ' c')
                 ->join('companies cp', 'cp.id = c.company_id', 'left')
                 ->order_by('c.date', 'DESC')
                 ->order_by('c.id', 'DESC');
        
        // Apply filters
        if (!empty($filters['company_id'])) {
            $this->db->where('c.company_id', $filters['company_id']);
        }
        
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('c.bilty_no', $filters['search']);
            $this->db->or_like('cp.name', $filters['search']);
            $this->db->or_like('c.driver_name', $filters['search']);
            $this->db->or_like('c.from_city', $filters['search']);
            $this->db->or_like('c.to_city', $filters['search']);
            $this->db->or_like('c.vehicle_no', $filters['search']);
            $this->db->group_end();
        }
        
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Get single bilty by ID
     * 
     * @param int $id
     * @return object|null
     */
    public function get_by_id($id)
    {
        $query = $this->db->select('c.*, cp.name as company_name')
                          ->from($this->table . ' c')
                          ->join('companies cp', 'cp.id = c.company_id', 'left')
                          ->where('c.id', $id)
                          ->limit(1)
                          ->get();
        
        return $query->num_rows() > 0 ? $query->row() : null;
    }
    
    /**
     * Get bilty by bilty number
     * 
     * @param string $bilty_no
     * @return object|null
     */
    public function get_by_bilty_no($bilty_no)
    {
        $query = $this->db->select('c.*, cp.name as company_name')
                          ->from($this->table . ' c')
                          ->join('companies cp', 'cp.id = c.company_id', 'left')
                          ->where('c.bilty_no', $bilty_no)
                          ->limit(1)
                          ->get();
        
        return $query->num_rows() > 0 ? $query->row() : null;
    }
    
    /**
     * Insert new bilty
     * 
     * @param array $data
     * @return int|bool Insert ID on success, false on failure
     */
    public function insert($data)
    {
        // Calculate balance if not set
        if (!isset($data['balance'])) {
            $data['balance'] = $data['amount'] - $data['advance'];
        }
        
        if ($this->db->insert($this->table, $data)) {
            return $this->db->insert_id();
        }
        
        return false;
    }
    
    /**
     * Update bilty
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        // Recalculate balance if amount or advance changed
        if (isset($data['amount']) || isset($data['advance'])) {
            $current = $this->get_by_id($id);
            $amount = isset($data['amount']) ? $data['amount'] : $current->amount;
            $advance = isset($data['advance']) ? $data['advance'] : $current->advance;
            $data['balance'] = $amount - $advance;
        }
        
        return $this->db->where('id', $id)
                        ->update($this->table, $data);
    }
    
    /**
     * Delete bilty
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->db->where('id', $id)
                        ->delete($this->table);
    }
    
    /**
     * Get next bilty number
     * 
     * @return string
     */
    public function get_next_bilty_number()
    {
        $query = $this->db->select_max('id')
                          ->from($this->table)
                          ->get();
        
        $max_id = $query->row()->id ?? 0;
        return (string)($max_id + 1);
    }
    
    /**
     * Get all companies for dropdown
     * 
     * @return array
     */
    public function get_companies()
    {
        $this->db->select('id, name, address')
                 ->from('companies')
                 ->order_by('name', 'ASC');
        
        // Check if address column exists
        if (!$this->db->field_exists('address', 'companies')) {
            $this->db->select('id, name');
        }
        
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Check if bilty number exists
     * 
     * @param string $bilty_no
     * @param int $exclude_id
     * @return bool
     */
    public function bilty_number_exists($bilty_no, $exclude_id = null)
    {
        $this->db->where('bilty_no', $bilty_no);
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        return $this->db->count_all_results($this->table) > 0;
    }
}
