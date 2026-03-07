<?php
use PHPUnit\Framework\TestCase;

class ExampleHelloWorldTest extends TestCase
{
    public function testHelloWorld()
    {
        $this->assertEquals('Hello, World!', 'Hello, World!');
    }
}