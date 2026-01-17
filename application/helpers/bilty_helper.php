<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bilty Helper Functions
 * 
 * Common helper functions for formatting and display
 */

if (!function_exists('format_currency')) {
    /**
     * Format currency with Rs. prefix
     * 
     * @param float $amount
     * @param int $decimals
     * @return string
     */
    function format_currency($amount, $decimals = 2)
    {
        return 'Rs. ' . number_format((float)$amount, $decimals);
    }
}

if (!function_exists('format_number')) {
    /**
     * Format number with thousands separator
     * 
     * @param float $number
     * @param int $decimals
     * @return string
     */
    function format_number($number, $decimals = 0)
    {
        return number_format((float)$number, $decimals);
    }
}

if (!function_exists('payment_status_badge')) {
    /**
     * Get HTML badge for payment status
     * 
     * @param string $status
     * @return string
     */
    function payment_status_badge($status)
    {
        $status = strtoupper(trim($status));
        
        $badges = [
            'PAID' => '<span class="badge bg-success">Paid</span>',
            'UNPAID' => '<span class="badge bg-warning">Unpaid</span>',
            'PARTIAL' => '<span class="badge bg-info">Partial</span>',
        ];
        
        return isset($badges[$status]) ? $badges[$status] : '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}

if (!function_exists('bilty_status_badge')) {
    /**
     * Get HTML badge for bilty status based on balance
     * 
     * @param float $balance
     * @return string
     */
    function bilty_status_badge($balance)
    {
        if ($balance <= 0) {
            return '<span class="badge bg-success">Paid</span>';
        } else {
            return '<span class="badge bg-warning">Unpaid</span>';
        }
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date in readable format
     * 
     * @param string $date
     * @param string $format
     * @return string
     */
    function format_date($date, $format = 'Y-m-d')
    {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : $date;
    }
}

if (!function_exists('sanitize_output')) {
    /**
     * Sanitize output for display (XSS protection)
     * 
     * @param string $str
     * @return string
     */
    function sanitize_output($str)
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('generate_bilty_number')) {
    /**
     * Generate next bilty number
     * 
     * @return string
     */
    function generate_bilty_number()
    {
        $CI =& get_instance();
        $CI->load->database();
        
        $query = $CI->db->query("SELECT MAX(id) AS maxid FROM consignments");
        $row = $query->row();
        
        $next = isset($row->maxid) ? (int)$row->maxid + 1 : 1;
        
        return (string)$next;
    }
}

if (!function_exists('calculate_balance')) {
    /**
     * Calculate balance (amount - advance)
     * 
     * @param float $amount
     * @param float $advance
     * @return float
     */
    function calculate_balance($amount, $advance)
    {
        return round((float)$amount - (float)$advance, 2);
    }
}
