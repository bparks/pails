<?php

require_once (__DIR__."/../lib/Router.php");
require_once (__DIR__."/../lib/Application.php");
require_once (__DIR__."/../lib/Request.php");
require_once (__DIR__."/../lib/Utilities.php");
require_once (__DIR__."/../lib/Controller.php");
require_once (__DIR__."/../lib/ActionResult.php");
require_once (__DIR__."/../lib/ContentResult.php");

use Pails\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testSegmentSplits()
    {
        $expected = explode('/', 'this/is/a/path');
        $actual = Router::splitSegments('/this/is/a/path', true);
        $this->assertEquals($actual, $expected);
    }

    public function testMatchesExactRoute()
    {
        $this->assertTrue(Router::isMatchFor('/this/is/a/path', '/this/is/a/path'));
    }

    public function testMatchesExactRouteWithTrailingSlash()
    {
        $this->assertTrue(Router::isMatchFor('/this/is/a/path', '/this/is/a/path/'));
    }

    public function testDoesNotMatchExactRouteWithMissingPieces()
    {
        $this->assertFalse(Router::isMatchFor('/this/is/', '/this/is/a/path/'));
        $this->assertFalse(Router::isMatchFor('/this/is/a/path', '/this/is'));
    }

    public function testMatchesWildcardRoute()
    {
        $this->assertTrue(Router::isMatchFor('/this/is/a/*', '/this/is/a/path'));
    }

    public function testDoesNotMatchWildcardRouteWithAdditionalPathSegments()
    {
        $this->assertFalse(Router::isMatchFor('/this/*', '/this/is/a/path'));
    }

    public function testMatchesWildcardRouteWithWildwardInMiddle()
    {
        $this->assertTrue(Router::isMatchFor('/this/*/a/path', '/this/is/a/path'));
    }

    public function testMatchesNamedWildcardRoute()
    {
        $opts = [];
        $this->assertTrue(Router::isMatchFor('/this/{id}', '/this/42', $opts));
        $this->assertEquals(count($opts), 1);
        $this->assertEquals($opts['id'], 42);
    }

    public function testMatchesNamedWildcardsRoute()
    {
        $opts = [];
        $this->assertTrue(Router::isMatchFor('/this/{id}/{action}', '/this/42/post', $opts));
        $this->assertEquals(count($opts), 2);
        $this->assertEquals($opts['id'], 42);
        $this->assertEquals($opts['action'], 'post');
    }

    public function testMatchesMultisegmentWildcardAtEndOfRoute()
    {
        $this->assertTrue(Router::isMatchFor('/this/**', '/this/is/a/path'));
    }

    public function testMatchesMultisegmentWildcardAtEndOfRouteMissingMatches()
    {
        $this->assertFalse(Router::isMatchFor('/this/**', '/this/'));
    }

    public function testGetResourceIndexReturnsProperControllerAndIndex()
    {
        $router = $this->makeResourceRouter();
        $request = $router->resolve("/things", 'GET');
        $this->assertEquals($request->action, 'index');
    }

    public function testGetResourceShowReturnsProperControllerAndShow()
    {
        $router = $this->makeResourceRouter();
        $request = $router->resolve("/things/42", 'GET');
        $this->assertEquals('show', $request->action);
        $this->assertEquals(42, $request->id);
    }

    public function testGetResourceActionReturnsProperControllerAndAction()
    {
        $router = $this->makeResourceRouter();
        $request = $router->resolve("/things/42/do-the-thing", 'GET');
        $this->assertEquals('do-the-thing', $request->action);
        $this->assertEquals(42, $request->id);
    }

    private function makeResourceRouter($app = null)
    {
        $router = new Router($app ?? new \Pails\Application([]));
        $router->resource("/things", ['controller' => 'ThingController']);
        return $router;
    }

    private function makeApplication()
    {
        $app = new \Pails\Application([]);
        $this->makeResourceRouter($app);
        return $app;
    }

    /**
     * @runInSeparateProcess
     */
    public function testFullEndToEndOnAThingControllerWithCustomAction()
    {
        $app = $this->makeApplication();
        ob_start();
        $app->run('/things/42/frobnicate', 'GET');
        $result = ob_get_clean();
        $this->assertEquals("Frobnicating item 42", $result);
    }
}

class ThingController extends \Pails\Controller
{
    public function frobnicate($id, $opts = [])
    {
        return $this->content("Frobnicating item $id");
    }
}