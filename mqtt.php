<?php

namespace MHorwood\loader;
use MHorwood\foxess_mqtt\controller\ha_mqtt;
require __DIR__ . '/vendor/autoload.php';

echo "This is our timezone: ".date_default_timezone_get()."\n";
$foxess = new ha_mqtt();
