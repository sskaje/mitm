<?php
use sskaje\mitm\MitmProxy;

require(__DIR__ . '/inc.php');

$mitm = new MitmProxy($options);

echo "Listening at 0.0.0.0:{$listen_port}\n";

$mitm->run();

# EOF