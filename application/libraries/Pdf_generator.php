<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDF Generator Library
 * 
 * Handles PDF generation for bilty documents
 * Uses TCPDF or similar library
 */
class Pdf_generator
{
    protected $CI;
    
    public function __construct()
    {
        $this->CI =& get_instance();
    }
    
    /**
     * Generate Bilty PDF
     * 
     * @param array $data Bilty data
     * @param string $filename Output filename
     * @param string $output Output mode (I=inline, D=download, F=file, S=string)
     * @return mixed
     */
    public function generate_bilty($data, $filename = 'bilty.pdf', $output = 'I')
    {
        // For now, we'll use a simple HTML to PDF approach
        // In production, integrate TCPDF, FPDF, or DomPDF
        
        $html = $this->_generate_bilty_html($data);
        
        // Simple implementation - would need proper PDF library
        // This is a placeholder that shows the structure
        
        if ($output === 'I') {
            // Inline display
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            // Would output PDF content here
            return $html; // Temporary
        } elseif ($output === 'D') {
            // Force download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            // Would output PDF content here
            return $html; // Temporary
        } elseif ($output === 'F') {
            // Save to file
            $filepath = FCPATH . 'bilty_pdfs/' . $filename;
            // Would save PDF to file here
            file_put_contents($filepath, $html); // Temporary
            return $filepath;
        }
        
        return $html;
    }
    
    /**
     * Generate HTML for Bilty
     * 
     * @param array $data
     * @return string
     */
    private function _generate_bilty_html($data)
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Bilty #<?php echo htmlspecialchars($data['bilty_no']); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { margin: 0; color: #97113a; }
                .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .info-table th, .info-table td { 
                    border: 1px solid #ddd; 
                    padding: 10px; 
                    text-align: left; 
                }
                .info-table th { background-color: #f5f5f5; font-weight: bold; }
                .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>BILTY DOCUMENT</h1>
                <p>Bilty Management System</p>
            </div>
            
            <table class="info-table">
                <tr>
                    <th>Bilty No:</th>
                    <td><?php echo htmlspecialchars($data['bilty_no']); ?></td>
                    <th>Date:</th>
                    <td><?php echo htmlspecialchars($data['date']); ?></td>
                </tr>
                <tr>
                    <th>Company:</th>
                    <td colspan="3"><?php echo htmlspecialchars($data['company_name'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Vehicle No:</th>
                    <td><?php echo htmlspecialchars($data['vehicle_no']); ?></td>
                    <th>Driver:</th>
                    <td><?php echo htmlspecialchars($data['driver_name']); ?></td>
                </tr>
                <tr>
                    <th>From:</th>
                    <td><?php echo htmlspecialchars($data['from_city']); ?></td>
                    <th>To:</th>
                    <td><?php echo htmlspecialchars($data['to_city']); ?></td>
                </tr>
                <tr>
                    <th>Quantity:</th>
                    <td><?php echo htmlspecialchars($data['qty']); ?></td>
                    <th>Distance (KM):</th>
                    <td><?php echo htmlspecialchars($data['km']); ?></td>
                </tr>
                <tr>
                    <th>Amount:</th>
                    <td><strong>Rs. <?php echo number_format($data['amount'], 2); ?></strong></td>
                    <th>Advance:</th>
                    <td>Rs. <?php echo number_format($data['advance'], 2); ?></td>
                </tr>
                <tr>
                    <th>Balance:</th>
                    <td colspan="3"><strong>Rs. <?php echo number_format($data['balance'], 2); ?></strong></td>
                </tr>
                <?php if (!empty($data['details'])): ?>
                <tr>
                    <th>Details:</th>
                    <td colspan="3"><?php echo nl2br(htmlspecialchars($data['details'])); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            
            <div class="footer">
                <p>Generated on <?php echo date('Y-m-d H:i:s'); ?> | Bilty Management System</p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
