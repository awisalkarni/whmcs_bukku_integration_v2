<?php

namespace GBNetwork\BukkuIntegration\Services;

use GBNetwork\BukkuIntegration\Api\InvoicesApi;
use GBNetwork\BukkuIntegration\Models\Invoice;
use WHMCS\Database\Capsule;
use WHMCS\Billing\Invoice as WhmcsInvoice;

class InvoiceService
{
    private InvoicesApi $invoicesApi;
    private ContactService $contactService;
    
    public function __construct()
    {
        $this->invoicesApi = new InvoicesApi();
        $this->contactService = new ContactService();
    }
    
    /**
     * Sync all WHMCS invoices to Bukku
     *
     * @return array
     */
    public function syncAllInvoices(): array
    {
        try {
            // Get invoices from last 3 months
            $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
            $invoices = WhmcsInvoice::where('date', '>=', $threeMonthsAgo)->get();
            
            $results = [
                'total' => count($invoices),
                'success' => 0,
                'failed' => 0,
                'details' => []
            ];
            
            foreach ($invoices as $invoice) {
                $result = $this->syncInvoice($invoice->id);
                
                if ($result['status'] === 'success') {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
                
                $results['details'][] = $result;
            }
            
            return [
                'status' => 'success',
                'message' => "Synced {$results['success']} invoices, {$results['failed']} failed",
                'data' => $results
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to sync invoices: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync a specific WHMCS invoice to Bukku
     *
     * @param int $invoiceId
     * @return array
     */
    public function syncInvoice(int $invoiceId): array
    {
        try {
            // Get invoice from WHMCS
            $invoice = WhmcsInvoice::find($invoiceId);
            
            if (!$invoice) {
                return [
                    'status' => 'error',
                    'message' => "Invoice not found: {$invoiceId}"
                ];
            }
            
            // First ensure the client is synced to Bukku
            $clientSyncResult = $this->contactService->syncContact($invoice->userid);
            
            if ($clientSyncResult['status'] !== 'success') {
                return [
                    'status' => 'error',
                    'message' => "Failed to sync client: {$clientSyncResult['message']}"
                ];
            }
            
            $bukkuContactId = $clientSyncResult['bukku_id'];
            
            // Check if invoice already exists in Bukku by invoice number
            $existingInvoice = $this->invoicesApi->findInvoiceByNumber($invoice->invoicenum);
            
            // Prepare invoice data
            $invoiceData = $this->prepareInvoiceData($invoice, $bukkuContactId);
            
            // Create or update invoice in Bukku
            if ($existingInvoice['exists']) {
                $bukkuInvoiceId = $existingInvoice['invoice']['id'];
                $invoiceModel = new Invoice($invoiceData);
                $result = $this->invoicesApi->updateInvoice($bukkuInvoiceId, $invoiceModel);
                $action = 'updated';
            } else {
                $invoiceModel = new Invoice($invoiceData);
                $result = $this->invoicesApi->createInvoice($invoiceModel);
                $action = 'created';
            }
            
            // Update sync status in database
            if ($result['status'] === 'success') {
                $bukkuInvoiceId = $result['invoice']['id'] ?? $existingInvoice['invoice']['id'];
                
                $this->updateSyncStatus($invoiceId, $bukkuInvoiceId, 'success');
                
                return [
                    'status' => 'success',
                    'message' => "Invoice {$action} successfully",
                    'whmcs_id' => $invoiceId,
                    'bukku_id' => $bukkuInvoiceId
                ];
            } else {
                $this->updateSyncStatus($invoiceId, null, 'failed', $result['message'] ?? 'Unknown error');
                
                return [
                    'status' => 'error',
                    'message' => "Failed to {$action} invoice: " . ($result['message'] ?? 'Unknown error'),
                    'whmcs_id' => $invoiceId
                ];
            }
        } catch (\Exception $e) {
            $this->updateSyncStatus($invoiceId, null, 'failed', $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
                'whmcs_id' => $invoiceId
            ];
        }
    }
    
    /**
     * Get all synced invoices
     *
     * @return array
     */
    public function getAllSyncedInvoices(): array
    {
        try {
            return Capsule::table('mod_bukku_integration_invoices')
                ->orderBy('last_synced', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get invoices by contact
     *
     * @param int $bukkuContactId
     * @return array
     */
    public function getInvoicesByContact(int $bukkuContactId): array
    {
        try {
            // Get WHMCS client ID from Bukku contact ID
            $contactMapping = Capsule::table('mod_bukku_integration_contacts')
                ->where('bukku_id', $bukkuContactId)
                ->first();
            
            if (!$contactMapping) {
                return [];
            }
            
            $clientId = $contactMapping->whmcs_id;
            
            // Get all invoices for this client
            return Capsule::table('mod_bukku_integration_invoices')
                ->join('tblinvoices', 'mod_bukku_integration_invoices.whmcs_id', '=', 'tblinvoices.id')
                ->where('tblinvoices.userid', $clientId)
                ->select('mod_bukku_integration_invoices.*', 'tblinvoices.invoicenum', 'tblinvoices.date', 'tblinvoices.duedate', 'tblinvoices.total', 'tblinvoices.status as whmcs_status')
                ->orderBy('tblinvoices.date', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Sync all invoices for a specific contact
     *
     * @param int $bukkuContactId
     * @return array
     */
    public function syncAllInvoicesForContact(int $bukkuContactId): array
    {
        try {
            // Get WHMCS client ID from Bukku contact ID
            $contactMapping = Capsule::table('mod_bukku_integration_contacts')
                ->where('bukku_id', $bukkuContactId)
                ->first();
            
            if (!$contactMapping) {
                return [
                    'status' => 'error',
                    'message' => 'Contact mapping not found'
                ];
            }
            
            $clientId = $contactMapping->whmcs_id;
            
            // Get all invoices for this client
            $invoices = WhmcsInvoice::where('userid', $clientId)->get();
            
            $results = [
                'total' => count($invoices),
                'success' => 0,
                'failed' => 0,
                'details' => []
            ];
            
            foreach ($invoices as $invoice) {
                $result = $this->syncInvoice($invoice->id);
                
                if ($result['status'] === 'success') {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
                
                $results['details'][] = $result;
            }
            
            return [
                'status' => 'success',
                'message' => "Synced {$results['success']} invoices, {$results['failed']} failed",
                'data' => $results
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to sync invoices: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync selected invoices
     *
     * @param array $invoiceIds
     * @return array
     */
    public function syncSelectedInvoices(array $invoiceIds): array
    {
        $results = [
            'total' => count($invoiceIds),
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($invoiceIds as $invoiceId) {
            $result = $this->syncInvoice((int)$invoiceId);
            
            if ($result['status'] === 'success') {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            $results['details'][] = $result;
        }
        
        return [
            'status' => 'success',
            'message' => "Synced {$results['success']} invoices, {$results['failed']} failed",
            'data' => $results
        ];
    }
    
    /**
     * Update sync status in database
     *
     * @param int $invoiceId
     * @param int|null $bukkuId
     * @param string $status
     * @param string|null $errorMessage
     * @return void
     */
    private function updateSyncStatus(int $invoiceId, ?int $bukkuId, string $status, ?string $errorMessage = null): void
    {
        $invoice = WhmcsInvoice::find($invoiceId);
        
        if (!$invoice) {
            return;
        }
        
        $data = [
            'whmcs_id' => $invoiceId,
            'sync_status' => $status,
            'last_synced' => date('Y-m-d H:i:s'),
            'error_message' => $errorMessage
        ];
        
        if ($bukkuId) {
            $data['bukku_id'] = $bukkuId;
        }
        
        // Check if record exists
        $exists = Capsule::table('mod_bukku_integration_invoices')
            ->where('whmcs_id', $invoiceId)
            ->exists();
        
        if ($exists) {
            Capsule::table('mod_bukku_integration_invoices')
                ->where('whmcs_id', $invoiceId)
                ->update($data);
        } else {
            Capsule::table('mod_bukku_integration_invoices')->insert($data);
        }
    }
    
    /**
     * Prepare invoice data from WHMCS invoice
     *
     * @param WhmcsInvoice $invoice
     * @param int $bukkuContactId
     * @return array
     */
    private function prepareInvoiceData(WhmcsInvoice $invoice, int $bukkuContactId): array
    {
        // Get client details
        $client = Capsule::table('tblclients')
            ->where('id', $invoice->userid)
            ->first();
        
        $clientName = $client->firstname . ' ' . $client->lastname;
        if (!empty($client->companyname)) {
            $clientName = $client->companyname;
        }
        
        // Get invoice items
        $items = Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoice->id)
            ->get();
        
        $formItems = [];
        $totalAmount = 0;
        
        foreach ($items as $item) {
            $formItems[] = [
                'key' => md5(uniqid('item_' . $item->id, true)),
                'line' => count($formItems) + 1,
                'account_id' => 20, // Default account ID for Sales
                'account_name' => 'Sales',
                'account_code' => '5000-00',
                'description' => $item->description,
                'product_id' => null, // We'll need to map WHMCS products to Bukku products
                'product_name' => $item->description,
                'product_sku' => 'WHMCS-' . $item->id,
                'product_bin_location' => 'GBNETWORK',
                'product_unit_id' => 3, // Assuming yearly as default
                'product_unit_label' => 'yearly',
                'quantity' => 1,
                'unit_price' => (string)$item->amount,
                'amount' => (string)$item->amount,
                'discount' => null,
                'discount_amount' => 0,
                'tax_code_id' => 22, // Default tax code ID for SV8
                'tax_code' => 'SV8',
                'tax_amount' => 0,
                'net_amount' => $item->amount,
                'classification_code' => '008',
                'classification_name' => 'e-Commerce - e-Invoice to buyer / purchaser'
            ];
            
            $totalAmount += $item->amount;
        }
        
        // Calculate due date (30 days from invoice date by default)
        $dueDate = date('Y-m-d', strtotime($invoice->duedate));
        
        return [
            'contact_id' => $bukkuContactId,
            'billing_party' => $clientName,
            'date' => date('Y-m-d', strtotime($invoice->date)),
            'currency_code' => 'MYR', // Default to MYR
            'exchange_rate' => 1,
            'payment_mode' => 'credit',
            'form_items' => $formItems,
            'amount' => $totalAmount,
            'balance' => $totalAmount,
            'status' => $this->mapInvoiceStatus($invoice->status),
            'tax_mode' => 'exclusive',
            'type' => 'sale_invoice',
            'myinvois_action' => 'NORMAL',
            'term_items' => [
                [
                    'key' => md5(uniqid('term_', true)),
                    'term_id' => 3, // NET30
                    'term_name' => 'NET30',
                    'date' => $dueDate,
                    'payment_due' => '100%',
                    'amount' => $totalAmount,
                    'balance' => $totalAmount
                ]
            ],
            'remarks' => "WHMCS Invoice #{$invoice->invoicenum}"
        ];
    }
    
    /**
     * Map WHMCS invoice status to Bukku status
     *
     * @param string $whmcsStatus
     * @return string
     */
    private function mapInvoiceStatus(string $whmcsStatus): string
    {
        $statusMap = [
            'Unpaid' => 'ready',
            'Paid' => 'paid',
            'Cancelled' => 'void',
            'Refunded' => 'void',
            'Collections' => 'ready',
            'Payment Pending' => 'ready',
        ];
        
        return $statusMap[$whmcsStatus] ?? 'ready';
    }
}