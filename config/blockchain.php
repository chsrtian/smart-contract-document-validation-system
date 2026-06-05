<?php
// filepath: config/blockchain.php

return [
    /*
    |--------------------------------------------------------------------------
    | Blockchain Network Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to Ethereum-compatible blockchain networks.
    | For development, we use Ganache GUI (local private network).
    |
    */

    'network' => [
        // Ganache GUI local development network
        'provider' => env('BLOCKCHAIN_PROVIDER', 'http://127.0.0.1:7545'),
        
        // Network ID from Ganache (5777 is Ganache GUI default)
        'network_id' => env('BLOCKCHAIN_NETWORK_ID', '5777'),
        
        // Chain ID for transaction signing (same as network ID)
        'chain_id' => env('BLOCKCHAIN_CHAIN_ID', 5777),
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Configuration
    |--------------------------------------------------------------------------
    |
    | The account that your Laravel app will use to send transactions.
    | This is Account 0 from your Ganache GUI.
    |
    */

    'account' => [
        'address' => env('BLOCKCHAIN_ACCOUNT_ADDRESS', env('GANACHE_FROM_ADDRESS')),
        'private_key' => env('BLOCKCHAIN_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Smart Contract Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the DocumentRegistry smart contract.
    | Contract address will be set after deployment in Phase 2.
    |
    */

    'contract' => [
        'address' => env('BLOCKCHAIN_CONTRACT_ADDRESS', null),
        'gas_limit' => env('BLOCKCHAIN_GAS_LIMIT', env('GANACHE_GAS_LIMIT', 6721975)),
        'gas_price' => env('BLOCKCHAIN_GAS_PRICE', env('GANACHE_GAS_PRICE', 20000000000)),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    */

    'enabled' => env('BLOCKCHAIN_ENABLED', true),
    'timeout' => env('BLOCKCHAIN_TIMEOUT', env('GANACHE_TIMEOUT', 30)),
    'retry_attempts' => env('BLOCKCHAIN_RETRY_ATTEMPTS', 3),
    
    /*
    |--------------------------------------------------------------------------
    | Ganache GUI Specific Settings
    |--------------------------------------------------------------------------
    */
    
    'ganache' => [
        'url' => env('GANACHE_URL', 'http://127.0.0.1:7545'),
        'network_id' => env('GANACHE_NETWORK_ID', '5777'),
        'from_address' => env('GANACHE_FROM_ADDRESS'),
        'gas_limit' => (int) env('GANACHE_GAS_LIMIT', 6721975),
        'gas_price' => env('GANACHE_GAS_PRICE', '20000000000'),
        'timeout' => (int) env('GANACHE_TIMEOUT', 30),
        'automining' => true,
        'hardfork' => 'merge',
        'block_time' => 0,
    ],
];