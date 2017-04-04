<?php
namespace App;

use function DI\object;
use function DI\get;
use function DI\value;

use App\Service\HelloService;
use App\Component\Identity;
use App\Component\IdentityInterface;

return [
    HelloService::class => object(HelloService::class)->constructor(get('hello')),
    'hello' => value("Hello"),

    IdentityInterface::class => get(Identity::class),
];
