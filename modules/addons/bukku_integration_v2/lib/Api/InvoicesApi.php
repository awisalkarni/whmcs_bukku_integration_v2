<?php

namespace GBNetwork\BukkuIntegration\Api;

use GBNetwork\BukkuIntegration\Models\Invoice;

class InvoicesApi extends BaseApi
{
    /**
     * Get all invoices
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAllInvoices(int $page = 1, int $perPage = 100): array
    {
        return $this->get('/sales/invoices', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }
    
    /**
     * Get a specific invoice
     *
     * @param int $invoiceId
     * @return array
     */
    public function getInvoice(int $invoiceId): array
    {
        return $this->get("/sales/invoices/{$invoiceId}");
    }
    
    /**
     * Find an invoice by number
     *
     * @param string $invoiceNumber
     * @return array
     */
    public function findInvoiceByNumber(string $invoiceNumber): array
    {
        $result = $this->get('/sales/invoices', [
            'number' => $invoiceNumber,
        ]);
        
        if ($result['status'] === 'success' && !empty($result['data']['data'])) {
            return [
                'status' => 'success',
                'exists' => true,
                'invoice' => $result['data']['data'][0],
            ];
        }
        
        return [
            'status' => 'success',
            'exists' => false,
        ];
    }
    
    /**
     * Get invoices by contact
     *
     * @param int $contactId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getInvoicesByContact(int $contactId, int $page = 1, int $perPage = 100): array
    {
        return $this->get('/sales/invoices', [
            'contact_id' => $contactId,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }
    
    /**
     * Create a new invoice
     *
     * @param Invoice $invoice
     * @return array
     */
    public function createInvoice(Invoice $invoice): array
    {
        $result = $this->post('/sales/invoices', $invoice->toArray());
        
        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'invoice' => $result['data']['transaction'],
            ];
        }
        
        return $result;
    }
    
    /**
     * Update an existing invoice
     *
     * @param int $invoiceId
     * @param Invoice $invoice
     * @return array
     */
    public function updateInvoice(int $invoiceId, Invoice $invoice): array
    {
        $result = $this->put("/sales/invoices/{$invoiceId}", $invoice->toArray());
        
        if ($result['status'] === 'success') {
            return [
                'status' => 'success',
                'invoice' => $result['data']['transaction'],
            ];
        }
        
        return $result;
    }
    
    /**
     * Delete an invoice
     *
     * @param int $invoiceId
     * @return array
     */
    public function deleteInvoice(int $invoiceId): array
    {
        return $this->delete("/sales/invoices/{$invoiceId}");
    }
    
    /**
     * Mark an invoice as paid
     *
     * @param int $invoiceId
     * @param string $paymentDate Format: Y-m-d
     * @param string $paymentMethod
     * @param string|null $reference
     * @return array
     */
    public function markInvoiceAsPaid(
        int $invoiceId, 
        string $paymentDate, 
        string $paymentMethod = 'Bank Transfer',
        ?string $reference = null
    ): array {
        $data = [
            'payment_date' => $paymentDate,
            'payment_method' => $paymentMethod,
        ];
        
        if ($reference) {
            $data['reference'] = $reference;
        }
        
        return $this->post("/sales/invoices/{$invoiceId}/mark-as-paid", $data);
    }
    
    /**
     * Void an invoice
     *
     * @param int $invoiceId
     * @param string $reason
     * @return array
     */
    public function voidInvoice(int $invoiceId, string $reason): array
    {
        return $this->post("/sales/invoices/{$invoiceId}/void", [
            'reason' => $reason,
        ]);
    }
    
    /**
     * Send an invoice by email
     *
     * @param int $invoiceId
     * @param string $email
     * @param string|null $subject
     * @param string|null $message
     * @return array
     */
    public function sendInvoiceByEmail(
        int $invoiceId, 
        string $email, 
        ?string $subject = null, 
        ?string $message = null
    ): array {
        $data = [
            'email' => $email,
        ];
        
        if ($subject) {
            $data['subject'] = $subject;
        }
        
        if ($message) {
            $data['message'] = $message;
        }
        
        return $this->post("/sales/invoices/{$invoiceId}/send", $data);
    }
    
    /**
     * Get the PDF URL for an invoice
     *
     * @param int $invoiceId
     * @return string|null
     */
    public function getInvoicePdfUrl(int $invoiceId): ?string
    {
        $result = $this->get("/sales/invoices/{$invoiceId}/pdf");
        
        if ($result['status'] === 'success' && isset($result['data']['url'])) {
            return $result['data']['url'];
        }
        
        return null;
    }
    
    /**
     * Submit invoice to MyInvois
     *
     * @param int $invoiceId
     * @return array
     */
    public function submitToMyInvois(int $invoiceId): array
    {
        return $this->post("/sales/invoices/{$invoiceId}/submit-to-myinvois", []);
    }
    
    /**
     * Check MyInvois status
     *
     * @param int $invoiceId
     * @return array
     */
    public function checkMyInvoisStatus(int $invoiceId): array
    {
        return $this->get("/sales/invoices/{$invoiceId}/myinvois-status");
    }
}