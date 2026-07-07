<?php
/**
 * AI Servers configurator — app config
 * Currency, paths, feature flags. Supplier data never here.
 */
define('AI_SERVERS_CURRENCY', 'CAD');
define('AI_SERVERS_DATA_DIR', __DIR__ . '/../data');
define('AI_SERVERS_PRODUCT_IMAGE_BASE', '/ai-servers/assets/products');
define('AI_SERVERS_PRESETS_ENABLED', true);
define('AI_SERVERS_QUOTE_EMAIL', ''); // set to your email for quote form
// Product ID for "Custom AI Server" — create product in store, note its ID, set here to enable Add to Cart
define('AI_SERVERS_PRODUCT_ID', 23); // Custom AI Server product (linked by create_product.php)
// API endpoints set their own Content-Type. Don't set here.
