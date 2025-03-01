<?php

namespace GBNetwork\BukkuIntegration\Database;

use WHMCS\Database\Capsule;

class Migrations
{
    /**
     * Run the migrations to create necessary tables
     *
     * @return array
     */
    public function up(): array
    {
        try {
            // Create contacts mapping table
            if (!Capsule::schema()->hasTable('mod_bukku_integration_contacts')) {
                Capsule::schema()->create('mod_bukku_integration_contacts', function ($table) {
                    $table->increments('id');
                    $table->integer('whmcs_id')->unique();
                    $table->integer('bukku_id')->nullable();
                    $table->string('name');
                    $table->string('email');
                    $table->string('type')->default('individual');
                    $table->string('sync_status')->default('pending');
                    $table->timestamp('last_synced')->nullable();
                    $table->text('error_message')->nullable();
                    $table->timestamps();
                });
            }
            
            // Create products mapping table
            if (!Capsule::schema()->hasTable('mod_bukku_integration_products')) {
                Capsule::schema()->create('mod_bukku_integration_products', function ($table) {
                    $table->increments('id');
                    $table->integer('whmcs_id')->unique();
                    $table->integer('bukku_id')->nullable();
                    $table->string('name');
                    $table->string('type')->nullable();
                    $table->decimal('price', 10, 2)->default(0.00);
                    $table->string('sync_status')->default('pending');
                    $table->timestamp('last_synced')->nullable();
                    $table->text('error_message')->nullable();
                    $table->timestamps();
                });
            }
            
            // Create invoices mapping table
            if (!Capsule::schema()->hasTable('mod_bukku_integration_invoices')) {
                Capsule::schema()->create('mod_bukku_integration_invoices', function ($table) {
                    $table->increments('id');
                    $table->integer('whmcs_id')->unique();
                    $table->integer('bukku_id')->nullable();
                    $table->string('sync_status')->default('pending');
                    $table->timestamp('last_synced')->nullable();
                    $table->text('error_message')->nullable();
                    $table->timestamps();
                });
            }
            
            // Create logs table
            if (!Capsule::schema()->hasTable('mod_bukku_integration_logs')) {
                Capsule::schema()->create('mod_bukku_integration_logs', function ($table) {
                    $table->increments('id');
                    $table->string('level')->default('info');
                    $table->string('message');
                    $table->text('context')->nullable();
                    $table->timestamp('created_at')->useCurrent();
                });
            }
            
            // Create settings table
            if (!Capsule::schema()->hasTable('mod_bukku_integration_settings')) {
                Capsule::schema()->create('mod_bukku_integration_settings', function ($table) {
                    $table->increments('id');
                    $table->string('key')->unique();
                    $table->text('value')->nullable();
                    $table->timestamps();
                });
                
                // Insert default settings
                Capsule::table('mod_bukku_integration_settings')->insert([
                    ['key' => 'last_contact_sync', 'value' => null],
                    ['key' => 'last_product_sync', 'value' => null],
                    ['key' => 'last_invoice_sync', 'value' => null],
                ]);
            }
            
            return [
                'status' => 'success',
                'message' => 'Database tables created successfully'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to create database tables: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Reverse the migrations
     *
     * @return array
     */
    public function down(): array
    {
        try {
            // Drop all tables
            $tables = [
                'mod_bukku_integration_contacts',
                'mod_bukku_integration_products',
                'mod_bukku_integration_invoices',
                'mod_bukku_integration_logs',
                'mod_bukku_integration_settings'
            ];
            
            foreach ($tables as $table) {
                if (Capsule::schema()->hasTable($table)) {
                    Capsule::schema()->drop($table);
                }
            }
            
            return [
                'status' => 'success',
                'message' => 'Database tables dropped successfully'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to drop database tables: ' . $e->getMessage()
            ];
        }
    }
}