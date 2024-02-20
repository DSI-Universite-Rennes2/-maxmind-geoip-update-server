<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

/*
 * Same service endpoint as https://updates.maxmind.com/geoip/databases/%s/update
 * 
 * This manage with same path and reply your own endpoint https://your.domain.tld/geoip/databases/%s/update
 * 
 * This can be used for internal redistribution of Maxmind's GeoIP databases
 */
/* Want to debug ?
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**/

use Steampixel\Route;

if ( ! is_dir(MAXMIND_DATADIR) ) {
    http_response_code(500);
    exit(0);
}
define("MAXMIND_DATABASES", array(
        "GeoLite2-ASN",
        "GeoLite2-City",
        "GeoLite2-Country",
    )
);
// ----------------------------------------------------------------------------
Route::add('/test/endpoint', function() {
    echo 'It works :)';
});

Route::add('/test/auth', function() {
    checkAuth();
    echo 'It works :)';
});

Route::add('/test/db//([^/]+)', function(string $databaseName) {
    checkValidDatabases($databaseName);
    echo 'It works :)';
});

Route::add('/([^/]+)/update', function(string $databaseName) {
    checkValidDatabases($databaseName);
    checkAuth();

    if ( isset($_GET['db_md5']) ) {
        if ( databaseNotChanged($databaseName, $_GET['db_md5']) ) {
            $databasePath = getDatabasePath($databaseName);
            $current_md5  = md5_file($databasePath);
            header('x-database-md5: "' . $current_md5 . '"');
            http_response_code(304);
            exit(0);
        }
    }
    sendDatabase($databaseName);
    exit(0);
}, ['get', 'head']);

// Add a 404 not found route
Route::pathNotFound(function($path) {
    header('HTTP/1.0 404 Not Found');
    exit(1);
});

// Run the router
Route::run('/geoip/databases');
