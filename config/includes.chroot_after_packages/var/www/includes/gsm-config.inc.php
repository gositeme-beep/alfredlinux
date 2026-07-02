<?php
/**
 * GSM Token — Centralized Configuration
 * ══════════════════════════════════════
 * Single source of truth for all GSM on-chain data.
 * Include this file instead of hardcoding addresses.
 *
 * Usage:
 *   require_once __DIR__ . '/gsm-config.inc.php';
 *   echo GSM_MINT_ADDRESS;
 *
 * @deployed 2026-04-08T23:40:42Z
 */

// On-chain addresses (Solana mainnet-beta)
if (!defined('GSM_MINT_ADDRESS'))     define('GSM_MINT_ADDRESS',     '7Uix6nuVfPEPnqV9o9rffDvA6bX2YSLUjUJSQxU5Q7un');
if (!defined('GSM_TREASURY_ADDRESS')) define('GSM_TREASURY_ADDRESS', 'FniRLQgZ7WhLiZhcTXHDKf8YiC4v3vytYmNubWkapA5Z');
if (!defined('GSM_MINT_AUTHORITY'))   define('GSM_MINT_AUTHORITY',   'HwyigTKuCYzoQ2qNqFWJyvaWGT4PCNUemtwLwHxbXEx1');
if (!defined('GSM_DEPLOYER_WALLET'))  define('GSM_DEPLOYER_WALLET',  '8dXHQj7kX2JTZ9524VxyYaxLVpS5rV1T6CeVUL9svnEz');

// Token specs
if (!defined('GSM_SYMBOL'))           define('GSM_SYMBOL',           'GSM');
if (!defined('GSM_DECIMALS'))         define('GSM_DECIMALS',         8);
if (!defined('GSM_TOTAL_SUPPLY'))     define('GSM_TOTAL_SUPPLY',     1000000000);
if (!defined('GSM_NETWORK'))          define('GSM_NETWORK',          'mainnet-beta');
if (!defined('GSM_DEPLOY_DATE'))      define('GSM_DEPLOY_DATE',      '2026-04-08T23:40:42Z');

// Explorer links
if (!defined('GSM_SOLSCAN_URL'))      define('GSM_SOLSCAN_URL',      'https://solscan.io/token/' . GSM_MINT_ADDRESS);
if (!defined('GSM_EXPLORER_URL'))     define('GSM_EXPLORER_URL',     'https://explorer.solana.com/address/' . GSM_MINT_ADDRESS);

// Distribution (percentages)
if (!defined('GSM_DIST_TREASURY'))    define('GSM_DIST_TREASURY',    30); // 300M
if (!defined('GSM_DIST_MINING'))      define('GSM_DIST_MINING',      25); // 250M
if (!defined('GSM_DIST_COMMUNITY'))   define('GSM_DIST_COMMUNITY',   15); // 150M
if (!defined('GSM_DIST_FOUNDER'))     define('GSM_DIST_FOUNDER',     15); // 150M, 4yr vest
if (!defined('GSM_DIST_EDEN'))        define('GSM_DIST_EDEN',         5); // 50M, locked to 2030-08-21
if (!defined('GSM_DIST_ECOSYSTEM'))   define('GSM_DIST_ECOSYSTEM',    5); // 50M
if (!defined('GSM_DIST_DEX'))         define('GSM_DIST_DEX',          5); // 50M

// Status flag
if (!defined('GSM_IS_LIVE'))          define('GSM_IS_LIVE',          true);
