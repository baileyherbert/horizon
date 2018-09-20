<?php

use PHPUnit\Framework\TestCase;
use Horizon\Utils\Path;

class PathTest extends TestCase
{

    /**
     * Converts a path to use the system-specified directory separator.
     *
     * @param string $path
     * @return string
     */
    private function sys($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public function testJoinPaths()
    {
        $this->assertEquals($this->sys('a/b/c.ext'), Path::join('a', 'b', 'c.ext'));
        $this->assertEquals($this->sys('a/b/c.ext'), Path::join('a', 'b/', 'c.ext'));
        $this->assertEquals($this->sys('/etc/drivers/hosts'), Path::join('/etc/drivers/hosts'));
        $this->assertEquals($this->sys('/etc/drivers/hosts.ext'), Path::join('/etc/drivers/', 'hosts.ext'));
    }

    public function testGetRelativePaths()
    {
        $this->assertEquals('/', Path::getRelative('/a/b/c', '/'));
        $this->assertEquals('/1', Path::getRelative('/a/b/c', '/1'));
        $this->assertEquals('/1/2', Path::getRelative('/a/b/c', '/1/2'));
        $this->assertEquals('/a/', Path::getRelative('/a/b/c', '../'));
        $this->assertEquals('/', Path::getRelative('/a/b/c', '../../'));
        $this->assertEquals('/a/c', Path::getRelative('/a/b', 'c'));
        $this->assertEquals('/a/', Path::getRelative('/a/b', './'));
        $this->assertEquals('/a/c', Path::getRelative('/a/b', './c'));
        $this->assertEquals('/a/', Path::getRelative('/a/b', '.'));
        $this->assertEquals('/a/b/e/g/', Path::getRelative('/a/b/c/d', '../e/f/../g/'));
    }

}