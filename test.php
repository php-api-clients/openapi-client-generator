<?php

use ApiClients\Client\Github\Client;
use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use React\Http\Browser;

require 'vendor/autoload.php';

(new Client(new class () implements AuthenticationInterface {
    public function authHeader(): string
    {
        return 'token ';
    }
}, new Browser()))->call('https://api.github.com/repos/octocat/Hello-World/pulls/1347');
