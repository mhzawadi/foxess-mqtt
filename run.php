<?php

namespace MHorwood\loader;
use MHorwood\foxess_mqtt\controller\foxess_data;
require __DIR__ . '/vendor/autoload.php';

echo "This is our timezone: ".timezone_location_get();
$foxess = new foxess_data();
