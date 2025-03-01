<?php

namespace GBNetwork\BukkuIntegration\Models;

class Invoice extends Model
{
    /**
     * Create a new invoice instance
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        // Set default values
        $defaults = [
            'currency_code' => 'MYR',
            'exchange_rate' => 1,
            'payment_mode' => 'credit',
            'tax_mode' => 'exclusive',
            'type' => 'sale_invoice',
            'myinvois_action' => 'NORMAL',
            'status' => 'ready'
        ];
        
        parent::__construct(array_merge($defaults, $attributes));
    }
    
    /**
     * Set the contact ID for this invoice
     *
     * @param int $contactId
     * @return $this
     */
    public function setContactId(int $contactId): self
    {
        $this->setAttribute('contact_id', $contactId);
        
        return $this;
    }
    
    /**
     * Set the billing party name
     *
     * @param string $billingParty
     * @return $this
     */
    public function setBillingParty(string $billingParty): self
    {
        $this->setAttribute('billing_party', $billingParty);
        
        return $this;
    }
    
    /**
     * Set the invoice date
     *
     * @param string $date Format: Y-m-d
     * @return $this
     */
    public function setDate(string $date): self
    {
        $this->setAttribute('date', $date);
        
        return $this;
    }
    
    /**
     * Set the form items (line items)
     *
     * @param array $formItems
     * @return $this
     */
    public function setFormItems(array $formItems): self
    {
        $this->setAttribute('form_items', $formItems);
        
        return $this;
    }
    
    /**
     * Add a form item to the invoice
     *
     * @param int $accountId
     * @param string $accountName
     * @param string $accountCode
     * @param string $description
     * @param int|null $productId
     * @param string|null $productName
     * @param string|null $productSku
     * @param float $quantity
     * @param float $unitPrice
     * @param int $taxCodeId
     * @param string $taxCode
     * @param string $classificationCode
     * @param string $classificationName
     * @return $this
     */
    public function addFormItem(
        int $accountId,
        string $accountName,
        string $accountCode,
        string $description,
        ?int $productId,
        ?string $productName,
        ?string $productSku,
        float $quantity,
        float $unitPrice,
        int $taxCodeId,
        string $taxCode,
        string $classificationCode = '008',
        string $classificationName = 'e-Commerce - e-Invoice to buyer / purchaser'
    ): self {
        $formItems = $this->getAttribute('form_items', []);
        $lineNumber = count($formItems) + 1;
        
        $amount = $quantity * $unitPrice;
        
        $formItems[] = [
            'key' => md5(uniqid('item_' . $lineNumber, true)),
            'line' => $lineNumber,
            'account_id' => $accountId,
            'account_name' => $accountName,
            'account_code' => $accountCode,
            'description' => $description,
            'product_id' => $productId,
            'product_name' => $productName,
            'product_sku' => $productSku,
            'product_bin_location' => 'GBNETWORK',
            'product_unit_id' => 3, // Assuming yearly as default
            'product_unit_label' => 'yearly',
            'quantity' => $quantity,
            'unit_price' => (string)$unitPrice,
            'amount' => (string)$amount,
            'discount' => null,
            'discount_amount' => 0,
            'tax_code_id' => $taxCodeId,
            'tax_code' => $taxCode,
            'tax_amount' => 0, // Will be calculated by Bukku
            'net_amount' => $amount,
            'classification_code' => $classificationCode,
            'classification_name' => $classificationName
        ];
        
        $this->setAttribute('form_items', $formItems);
        $this->updateTotalAmount();
        
        return $this;
    }
    
    /**
     * Set the term items (payment terms)
     *
     * @param array $termItems
     * @return $this
     */
    public function setTermItems(array $termItems): self
    {
        $this->setAttribute('term_items', $termItems);
        
        return $this;
    }
    
    /**
     * Add a term item to the invoice
     *
     * @param int $termId
     * @param string $termName
     * @param string $dueDate Format: Y-m-d
     * @param string $paymentDue
     * @param float $amount
     * @return $this
     */
    public function addTermItem(
        int $termId,
        string $termName,
        string $dueDate,
        string $paymentDue = '100%',
        ?float $amount = null
    ): self {
        $termItems = $this->getAttribute('term_items', []);
        
        if ($amount === null) {
            $amount = $this->getAttribute('amount', 0);
        }
        
        $termItems[] = [
            'key' => md5(uniqid('term_', true)),
            'term_id' => $termId,
            'term_name' => $termName,
            'date' => $dueDate,
            'payment_due' => $paymentDue,
            'amount' => $amount,
            'balance' => $amount
        ];
        
        $this->setAttribute('term_items', $termItems);
        
        return $this;
    }
    
    /**
     * Update the total amount based on form items
     *
     * @return $this
     */
    private function updateTotalAmount(): self
    {
        $formItems = $this->getAttribute('form_items', []);
        $totalAmount = 0;
        
        foreach ($formItems as $item) {
            $totalAmount += (float)($item['amount'] ?? 0);
        }
        
        $this->setAttribute('amount', $totalAmount);
        $this->setAttribute('balance', $totalAmount);
        
        return $this;
    }
    
    /**
     * Set the invoice status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        $this->setAttribute('status', $status);
        
        return $this;
    }
    
    /**
     * Set the invoice remarks
     *
     * @param string $remarks
     * @return $this
     */
    public function setRemarks(string $remarks): self
    {
        $this->setAttribute('remarks', $remarks);
        
        return $this;
    }
    
    /**
     * Set the internal note
     *
     * @param string $internalNote
     * @return $this
     */
    public function setInternalNote(string $internalNote): self
    {
        $this->setAttribute('internal_note', $internalNote);
        
        return $this;
    }
    
    /**
     * Set the MyInvois action
     *
     * @param string $action
     * @return $this
     */
    public function setMyInvoisAction(string $action): self
    {
        $this->setAttribute('myinvois_action', $action);
        
        return $this;
    }
}