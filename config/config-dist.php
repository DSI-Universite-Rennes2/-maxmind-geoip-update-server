<?php

// Get where Maxmind's DB are stored from ENV : MAXMIND_DATADIR 
// Example : 
// 	// if there is no MAXMIND_DATADIR define in ENV use the <project>/databases directory
// 	define("MAXMIND_DATADIR", getenvOrDefault('MAXMIND_DATADIR', dirname(__DIR__.'/../databases')));
define("MAXMIND_DATADIR", dirname(__DIR__.'/../databases'));

// list of IP (CIDR format) to authorize
define('CIDR_WHITELIST', 
    array(
        '127.0.0.1/32',
        // '192.168.0.1/32', 
    )
);

// enable/disable user's key checks
define('USERPASS_CHECK', TRUE);
// USER / KEY list
define('USERS_PASS', 
    array(
        'USER' => 'KEY', // add a comment if needed
    )
);
