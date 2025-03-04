<?php

declare(strict_types=1);

namespace PhpMyAdmin\Middleware;

use PhpMyAdmin\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class LoginCookieValiditySetting implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->config->getCurrentServer() !== null) {
            $this->config->getLoginCookieValidityFromCache($GLOBALS['server']);
        }

        return $handler->handle($request);
    }
}
