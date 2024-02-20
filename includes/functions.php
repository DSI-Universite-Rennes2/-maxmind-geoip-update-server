<?php
declare(strict_types=1);
require_once __DIR__ . '/ip_in_range.php';

use Madeorsk\Forwarded\Forwarded;

 /**
  * Retrieves the client's IP address.
  *
  * @return string
  */
function getClientIP() : string
{
    if (php_sapi_name() == "cli") {
        return '127.0.0.1';
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    // Check for IPs passing through proxies
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Check if multiple IP addresses exist in var 
        //  https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Forwarded-For
        $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($iplist as $ip) {
            if (validate_ip($ip)) {
                return $ip;
            }
        }
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED']) && validate_ip($_SERVER['HTTP_X_FORWARDED'])) {
        return $_SERVER['HTTP_X_FORWARDED'];
    }
    if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
        return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && validate_ip($_SERVER['HTTP_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_FORWARDED_FOR'];
    }
    if (!empty($_SERVER['HTTP_FORWARDED']) && validate_ip($_SERVER['HTTP_FORWARDED'])) {
        return $_SERVER['HTTP_FORWARDED'];
    }

    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Ensures given $ip is an:
 *     - valid IP address (v4 or v6)
 *     - not in reserved special ranges
 * @return bool
 */
function validate_ip(string $ip) : bool
{
    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false ) {
        return false;
    }
    return true;
}

function getDatabasePath(string $databaseName) : string
{
    return MAXMIND_DATADIR . "/{$databaseName}.mmdb";
}

function databaseNotChanged(string $databaseName, string $md5) : bool
{
    $databasePath = getDatabasePath($databaseName);
    if ( ! file_exists($databasePath) ) {
        http_response_code(500);
        throw new RuntimeException("$databasePath does not exists !");
        exit(0);
    }
    $current_md5 = md5_file($databasePath);
    if ( strcasecmp($current_md5, $md5) === 0 ) {
        return true;
    }
    return false;
}

function timestamp2DateTimeUTC(?int $ts=NULL) : DateTime
{
    if ( $ts == NULL ) {
        $ts = time();
    }
    $newDateTime = new DateTime();
    $newDateTime->setTimestamp($ts);
    $newDateTime->setTimezone(new DateTimeZone("GMT"));
    return $newDateTime;
}
function dateTimeForHTTPHeader(DateTime $date) : string
{
    return $date->format('D M d Y H:i:s e');
}

/* real headers reply from maxmind
        HTTP/2 200 
        date: Wed, 14 Feb 2024 14:13:14 GMT
        content-type: application/gzip
        content-length: 32735862
        cache-control: private, max-age=0
        content-disposition: attachment; filename=GeoLite2-City.mmdb.gz
        etag: "3daea21f76c51537ba98adfad0f27220"
        expires: Wed, 14 Feb 2024 14:13:14 GMT
        last-modified: Tue, 13 Feb 2024 17:31:08 GMT
        x-database-md5: 7566ea09b4b78224dd0dbd3503518eb4
        server: cloudflare
        cf-ray: 8555e91a7c69385a-LHR
*/
function sendDatabase(string $databaseName) 
{
    $databasePath = getDatabasePath($databaseName);
    $current_md5  = md5_file($databasePath);
    $lastModified = dateTimeForHTTPHeader(timestamp2DateTimeUTC(filemtime($databasePath)));
    $now          = dateTimeForHTTPHeader(timestamp2DateTimeUTC());
    $compressed   = gzencode(file_get_contents($databasePath), 5);

    header('Content-Type: application/gzip');
    header('Content-Length: ' . strlen($compressed) . '"');
    header('Cache-Control: private, max-age=0');
    header("Content-Disposition: attachment;filename=$databasePath.gz");
    header('etag: "' . $current_md5 . '"');
    header('expires: "' . $lastModified . '"');
    header('Last-Modified: "' . $lastModified . '"');
    header('x-database-md5: ' . $current_md5);
    echo "$compressed";
    exit(0);
}

// Check :
//    - if remote user IP in CIDR WHITELIST
//    - HTTP_AUTH User/Pass is valid (if not disabled)
function checkAuth() {
    $clientIP = getClientIP();
    foreach ( CIDR_WHITELIST as $cidr) {
        if ( ip_in_range($clientIP, $cidr) ) {
            return true;
        }
    }

    // Check user/pass HTTP_AUTH if not disabled
    if ( USERPASS_CHECK) {
        if ( ! isset($_SERVER['PHP_AUTH_USER']) ) {
            header('WWW-Authenticate: Basic realm="GeoIP update Access"');
            http_response_code(401);
            exit(0);
        }
        $user = $_SERVER['PHP_AUTH_USER'];
        $pass = $_SERVER['PHP_AUTH_PW'];
        foreach ( USERS_PASS as $s_user => $s_pass ) {
            $s_user = (string) $s_user;
            if (    ( strcmp($user, $s_user) === 0 ) && 
                    ( strcmp($pass, $s_pass) === 0 ) ) {
                return true;
            }
        }
    }

    // Access Denied
    http_response_code(403);
    exit(0);
}

function getValidUserKeys() {
    $keys = array(
        'user' => 'key', // user pass couple
        'user' => '', 
    );
    return $keys;
}

// Check database name validity, return HTTP 400 and exit if not valid
function checkValidDatabases(string $databaseName) {
    if ( array_search($databaseName, MAXMIND_DATABASES) !== FALSE ) {
        return TRUE;
    }
    http_response_code(400);
    exit(0);
}

function getenvOrDefault(string $varName, string|array $default) {
    $value = getenv($varName);
    if ( $value === FALSE ) {
        return $default;
    }
    return $varName;
}

