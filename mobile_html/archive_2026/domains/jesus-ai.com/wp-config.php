<?php
define( 'WP_CACHE', false ); // Added by WP Rocket

 // Added by WP Rocket

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'gositeme_wp448' );

/** Database username */
define( 'DB_USER', 'gositeme_wp448' );

/** Database password */
define( 'DB_PASSWORD', '6-!82pn0SB' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'luemp5nfk4etpmnmptiff9xgwhhmpkprebbpjgphwjcscj179iajftw7ekkq3hkl' );
define( 'SECURE_AUTH_KEY',  'xivhzt6zqmkdkhiqjjqcw9n5xosyamq2vonu4rb2haqjgnid8vmsatfn7oi6t8ry' );
define( 'LOGGED_IN_KEY',    'ixo7kqyxw8x43mrbbeiioywceesmqmgczzycm8d78tvi662wlwznjws147n5ofhk' );
define( 'NONCE_KEY',        'jmhxs9egpi3ef5wazdrk9qel0ohxirjbsv6bbrokwwobvb1g1nyrhbdtwgeynysf' );
define( 'AUTH_SALT',        'je4cvdgbq8igsq0nxcijpxigerpikrxhplvfaideauxbyvpcsbsnlbysojj0rjok' );
define( 'SECURE_AUTH_SALT', 'rfdhpsqeywtetx6pa1tszyjeewdgp8tn3ugdad423ogeufehpqskyqlfbajrszxv' );
define( 'LOGGED_IN_SALT',   '3sarhrd0be1xnof5eqznlamsueg7i656x94rdow1givvr1r6aeqh9irotc1ivpwv' );
define( 'NONCE_SALT',       'vtzpwzqrrma36zcsnya9kp0kwcsheakacvx1ggl3qouovhsadkjrbbbwlrvze0pu' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wptc_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
