<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Router\Middleware;

use Psr\Container\ContainerInterface;

class DispatchMiddlewareFactory
{
    /**
     * @return DispatchMiddleware
     */
    public function __invoke(ContainerInterface $container)
    {
        return new DispatchMiddleware();
    }
}