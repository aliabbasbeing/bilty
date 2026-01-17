<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Maintenance Model
 * 
 * Handles vehicle maintenance operations
 */
class Maintenance_model extends CI_Model
{
    private $table = 'vehicle_maintenance';
    
    /**
     * Get all maintenance records
     */
    public function get_all()
    {
        // Check if table exists
        if (!$this->db->table_exists($this->table)) {
            return array();
        }
        
        $query = $this->db->select('*')
                          ->from($this->table)
                          ->order_by('date', 'DESC')
                          ->get();
        
        return $query->result();
    }
    
    /**
     * Insert maintenance record
     */
    public function insert($data)
    {
        // Check if table exists
        if (!$this->db->table_exists($this->table)) {
            return false;
        }
        
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Get by ID
     */
    public function get_by_id($id)
    {
        if (!$this->db->table_exists($this->table)) {
            return null;
        }
        
        $query = $this->db->where('id', $id)
                          ->get($this->table);
        
        return $query->num_rows() > 0 ? $query->row() : null;
    }
}
