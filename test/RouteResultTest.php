<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router;

use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers \Mezzio\Router\RouteResult
 */
class RouteResultTest extends TestCase
{
    public function testRouteNameIsNotRetrievable()
    {
        $result = RouteResult::fromRouteFailure([]);
        $this->assertFalse($result->getMatchedRouteName());
    }

    public function testRouteFailureRetrieveAllHttpMethods()
    {
        $result = RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);
        $this->assertSame(Route::HTTP_METHOD_ANY, $result->getAllowedMethods());
    }

    public function testRouteFailureRetrieveHttpMethods()
    {
        $result = RouteResult::fromRouteFailure([]);
        $this->assertSame([], $result->getAllowedMethods());
    }

    public function testRouteMatchedParams()
    {
        $params = ['foo' => 'bar'];
        $route  = $this->prophesize(Route::class);
        $result = RouteResult::fromRoute($route->reveal(), $params);

        $this->assertSame($params, $result->getMatchedParams());
    }

    public function testRouteMethodFailure()
    {
        $result = RouteResult::fromRouteFailure(['GET']);
        $this->assertTrue($result->isMethodFailure());
    }

    public function testRouteSuccessMethodFailure()
    {
        $params = ['foo' => 'bar'];
        $route  = $this->prophesize(Route::class);
        $result = RouteResult::fromRoute($route->reveal(), $params);

        $this->assertFalse($result->isMethodFailure());
    }

    /**
     * @return Route[]|RouteResult[]
     */
    public function testFromRouteShouldComposeRouteInResult(): array
    {
        $route = $this->prophesize(Route::class);

        $result = RouteResult::fromRoute($route->reveal(), ['foo' => 'bar']);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame($route->reveal(), $result->getMatchedRoute());

        return ['route' => $route, 'result' => $result];
    }

    /**
     * @depends testFromRouteShouldComposeRouteInResult
     */
    public function testAllAccessorsShouldReturnExpectedDataWhenResultCreatedViaFromRoute(array $data)
    {
        $result = $data['result'];
        $route  = $data['route'];

        $route->getName()->willReturn('route');
        $route->getAllowedMethods()->willReturn(['HEAD', 'OPTIONS', 'GET']);

        $this->assertEquals('route', $result->getMatchedRouteName());
        $this->assertEquals(['HEAD', 'OPTIONS', 'GET'], $result->getAllowedMethods());
    }

    public function testRouteFailureWithNoAllowedHttpMethodsShouldReportTrueForIsMethodFailure()
    {
        $result = RouteResult::fromRouteFailure([]);
        $this->assertTrue($result->isMethodFailure());
    }

    public function testFailureResultDoesNotIndicateAMethodFailureIfAllMethodsAreAllowed(): RouteResult
    {
        $result = RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
        return $result;
    }

    /**
     * @depends testFailureResultDoesNotIndicateAMethodFailureIfAllMethodsAreAllowed
     */
    public function testAllowedMethodsIncludesASingleWildcardEntryWhenAllMethodsAllowedForFailureResult(
        RouteResult $result
    ) {
        $this->assertSame(Route::HTTP_METHOD_ANY, $result->getAllowedMethods());
    }

    public function testFailureResultProcessedAsMiddlewareDelegatesToHandler()
    {
        $request  = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $handler  = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request)->willReturn($response);

        $result = RouteResult::fromRouteFailure([]);

        $this->assertSame($response, $result->process($request, $handler->reveal()));
    }

    public function testSuccessfulResultProcessedAsMiddlewareDelegatesToRoute()
    {
        $request  = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $handler  = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request)->shouldNotBeCalled();

        $route = $this->prophesize(Route::class);
        $route->process($request, $handler)->willReturn($response);

        $result = RouteResult::fromRoute($route->reveal());

        $this->assertSame($response, $result->process($request, $handler->reveal()));
    }
}
