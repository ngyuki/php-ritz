<?php
namespace Ritz\App;

use function DI\object;
use function DI\get;
use function DI\value;

use Ritz\App\Service\HelloService;
use Ritz\App\Component\Identity;
use Ritz\App\Component\IdentityInterface;

return [
    HelloService::class => object(HelloService::class)->constructor(get('hello')),
    'hello' => value("Hello"),

    IdentityInterface::class => get(Identity::class),
];
