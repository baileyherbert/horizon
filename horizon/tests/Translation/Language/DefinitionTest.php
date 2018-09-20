<?php

use PHPUnit\Framework\TestCase;
use Horizon\Translation\Language\Definition;

class DefinitionTest extends TestCase
{

    public function testCompile()
    {
        $a = new Definition('Hello world.');
        $b = new Definition('Case insensitive.', '', array('i'));
        $c = new Definition('Ignoring extra whitespace.', '', array('x'));
        $d = new Definition('Let\'s try both.', '', array('i', 'x'));
        $e = new Definition('Hello there {{username}}!');
        $f = new Definition('/(custom regex)/', '', array('r'));

        $this->assertEquals('/(\\QHello world.\\E)(?=(?:[^}]|{[^{]*})*$)/', $a->compile());
        $this->assertEquals('/(\\QCase insensitive.\\E)(?=(?:[^}]|{[^{]*})*$)/i', $b->compile());
        $this->assertEquals('/(\\QIgnoring\\E\\s+\\Qextra\\E\\s+\\Qwhitespace.\\E)(?=(?:[^}]|{[^{]*})*$)/', $c->compile());
        $this->assertEquals('/(\\QLet\'s\\E\\s+\\Qtry\\E\\s+\\Qboth.\\E)(?=(?:[^}]|{[^{]*})*$)/i', $d->compile());
        $this->assertEquals('/(\\QHello there \\E{{\\s*\\Qusername\\E\\s*}}\\Q!\\E)(?=(?:[^}]|{[^{]*})*$)/', $e->compile());
        $this->assertEquals('/(custom regex)/', $f->compile());
    }

}
