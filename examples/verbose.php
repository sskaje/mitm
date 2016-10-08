<?php
use sskaje\mitm\Logger;
use sskaje\mitm\MitmProxy;

require(__DIR__ . '/inc.php');


Logger::$dump = 'hex';
Logger::$log_level = LOG_DEBUG;


$mitm = new MitmProxy($options);

echo "\nListening at 0.0.0.0:{$listen_port}\n";

$mitm->run();

# EOF