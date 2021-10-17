<?php

require_once (__DIR__."/../lib/Router.php");

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
}