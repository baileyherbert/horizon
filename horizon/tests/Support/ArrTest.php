<?php

use Horizon\Support\Arr;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{

    public function testAdd()
    {
        $input = ['name' => 'Desk'];
        $output = ['name' => 'Desk', 'attributes' => ['color' => 'brown']];

        $this->assertEquals($output, Arr::add($input, 'attributes.color', 'brown'));
    }

    public function testCollapse()
    {
        $output = [1, 2, 3, 4, 5, 6, 7, 8, 9];

        $this->assertEquals($output, Arr::collapse([[1, 2, 3], [4, 5, 6], [7, 8, 9]]));
    }

    public function testDivide()
    {
        $input = ['name' => 'Desk'];
        $output = [['name'], ['Desk']];

        $this->assertEquals($output, Arr::divide($input));
    }

    public function testDotNotation()
    {
        $input = ['a' => 1, 'b' => ['c' => 2, 'd' => 3]];
        $output = ['a' => 1, 'b.c' => 2, 'b.d' => 3];

        $this->assertEquals($output, Arr::dot($input));
    }

    public function testExcept()
    {
        $input = ['name' => 'Desk', 'price' => 100];
        $output = ['name' => 'Desk'];

        $this->assertEquals($output, Arr::except($input, 'price'));
    }

    public function testFirst()
    {
        $input = [100, 200, 300];
        $output = 200;

        $this->assertEquals($output, Arr::first($input, function($value) {
            return $value >= 150;
        }));
    }

    public function testFlatten()
    {
        $input = ['name' => 'Joe', 'languages' => ['PHP', 'Ruby']];
        $output = ['Joe', 'PHP', 'Ruby'];

        $this->assertEquals($output, Arr::flatten($input));
    }

    public function testForget()
    {
        $input = ['products' => ['desk' => ['price' => 100]]];
        $output = ['products' => []];

        Arr::forget($input, 'products.desk');

        $this->assertEquals($output, $input);
    }

    public function testGet()
    {
        $input = ['products' => ['desk' => ['price' => 100]]];
        $output = 100;

        $this->assertEquals($output, Arr::get($input, 'products.desk.price'));
        $this->assertEquals($output, Arr::get($input, 'products.desk.price.not_found', 100));
    }

    public function testHas()
    {
        $input = ['products' => ['desk' => ['price' => 100]]];

        $this->assertTrue(Arr::has($input, 'products.desk'));
        $this->assertFalse(Arr::has($input, 'products.chair'));
    }

    public function testLast()
    {
        $input = [100, 200, 300];
        $output = 300;

        $this->assertEquals($output, Arr::last($input, function($value) {
            return $value >= 150;
        }));

        $this->assertEquals($output, Arr::last($input, function($value) {
            return false;
        }, 300));
    }

}
