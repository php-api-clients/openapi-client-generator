<?php

require 'vendor/autoload.php';

$generator = new \ApiClients\Tools\OpenApiClientGenerator\Generator('https://raw.githubusercontent.com/github/rest-api-description/main/descriptions/api.github.com/api.github.com.yaml');
$generator->generate('ApiClients\Github\\', 'generated');
