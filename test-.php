<?php

use ApiClients\Client\Github\Schema\WebhookPing;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;

require 'vendor/autoload.php';

(new ObjectMapperCodeGenerator)->dump(
    [
        WebhookPing::class,
    ],
    ':poop-emoji:',
);
