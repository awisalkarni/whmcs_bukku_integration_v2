<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use GBNetwork\BukkuIntegration\Database\Migrations;
use GBNetwork\BukkuIntegration\Services\ContactService;
use GBNetwork\BukkuIntegration\Services\ProductService;
use GBNetwork\BukkuIntegration\Services\InvoiceService;

/**
 * Module configuration function
 *
 * @return array
 */
function bukku_integration_v2_config()
{
    return [
        'name' => 'Bukku e-Invoice Integration',
        'description' => 'Integrates WHMCS with Bukku for e-Invoice compliance in Malaysia',
        'version' => '1.0',
        'author' => 'GB Network Solutions',
        'fields' => [
            'api_token' => [
                'FriendlyName' => 'API Token',
                'Type' => 'password',
                'Size' => '100',
                'Default' => '',
                'Description' => 'Your Bukku API Bearer Token',
            ],
            'company_subdomain' => [
                'FriendlyName' => 'Company Subdomain',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Description' => 'Your Bukku company subdomain',
            ],
            'sync_frequency' => [
                'FriendlyName' => 'Sync Frequency',
                'Type' => 'dropdown',
                'Options' => [
                    'hourly' => 'Hourly',
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                ],
                'Default' => 'daily',
                'Description' => 'How often to sync data with Bukku',
            ],
            'debug_mode' => [
                'FriendlyName' => 'Debug Mode',
                'Type' => 'yesno',
                'Description' => 'Enable detailed logging for troubleshooting',
            ],
        ],
    ];
}

/**
 * Module activation function
 *
 * @return array
 */
function bukku_integration_v2_activate()
{
    // Create necessary database tables
    $migrations = new Migrations();
    $result = $migrations->up();
    
    if ($result['status'] === 'success') {
        return [
            'status' => 'success',
            'description' => 'Bukku e-Invoice Integration module activated successfully.',
        ];
    } else {
        return [
            'status' => 'error',
            'description' => 'Failed to activate module: ' . $result['message'],
        ];
    }
}

/**
 * Module deactivation function
 *
 * @return array
 */
function bukku_integration_v2_deactivate()
{
    // Option to keep or remove database tables
    return [
        'status' => 'success',
        'description' => 'Bukku e-Invoice Integration module deactivated successfully. Database tables have been preserved.',
    ];
}

/**
 * Module upgrade function
 *
 * @param array $vars
 * @return array
 */
function bukku_integration_v2_upgrade($vars)
{
    $currentVersion = $vars['version'];
    
    // Perform version-specific upgrades here
    
    return [
        'status' => 'success',
        'description' => 'Bukku e-Invoice Integration module upgraded successfully.',
    ];
}

/**
 * Admin area output
 *
 * @param array $vars
 * @return string
 */
function bukku_integration_v2_output($vars)
{
    // Get the requested action
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'overview';
    
    // Load the appropriate template
    switch ($action) {
        case 'settings':
            require_once __DIR__ . '/templates/admin/settings.tpl';
            break;
        case 'logs':
            require_once __DIR__ . '/templates/admin/logs.tpl';
            break;
        case 'contacts':
            require_once __DIR__ . '/templates/admin/contacts.tpl';
            break;
        case 'contact_invoices':
            $contactId = isset($_REQUEST['contact_id']) ? (int)$_REQUEST['contact_id'] : 0;
            $vars['contact_id'] = $contactId;
            require_once __DIR__ . '/templates/admin/contact_invoices.tpl';
            break;
        case 'products':
            require_once __DIR__ . '/templates/admin/products.tpl';
            break;
        case 'sync_contacts':
            $contactService = new ContactService();
            $result = $contactService->syncAllContacts();
            echo json_encode($result);
            break;
        case 'sync_products':
            $productService = new ProductService();
            $result = $productService->syncAllProducts();
            echo json_encode($result);
            break;
        case 'sync_invoices':
            $invoiceService = new InvoiceService();
            $result = $invoiceService->syncAllInvoices();
            echo json_encode($result);
            break;
        case 'overview':
        default:
            require_once __DIR__ . '/templates/admin/overview.tpl';
            break;
    }
}

/**
 * Client area output
 *
 * @param array $vars
 * @return array
 */
function bukku_integration_v2_clientarea($vars)
{
    // Check if this is the e-invoice settings page
    if (isset($_GET['action']) && $_GET['action'] == 'einvoice_settings') {
        return [
            'pagetitle' => 'e-Invoice Settings',
            'breadcrumb' => [
                'index.php?m=bukku_integration&action=einvoice_settings' => 'e-Invoice Settings',
            ],
            'templatefile' => 'client/einvoice_settings',
            'requirelogin' => true,
            'vars' => [
                'client' => $vars['client'],
                // Add more variables as needed
            ],
        ];
    }
    
    return [];
}