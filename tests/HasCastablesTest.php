<?php

namespace HalaeiTests;

use Halaei\Helpers\Objects\DataCollection;

class HasCastablesTest extends \PHPUnit_Framework_TestCase
{
    public function test_user_model()
    {
        // assign raw value
        $user = new UserModel();
        $this->assertNull($user->mobile);
        $user->mobile = '+98-9121231212';
        $this->assertInstanceOf(Mobile::class, $user->mobile);
        $this->assertEquals('98', $user->mobile->code);
        $this->assertEquals('9121231212', $user->mobile->number);
        $this->assertEquals(['code' => '98', 'number' => '9121231212'], $user->toArray()['mobile']);

        // assign data object
        $user = new UserModel();
        $user->mobile = new Mobile([
            'code' => '98',
            'number' => '9121231212',
        ]);
        $this->assertInstanceOf(Mobile::class, $user->mobile);
        $this->assertEquals('98', $user->mobile->code);
        $this->assertEquals('9121231212', $user->mobile->number);

        // assign in constructor
        $user = new UserModel([
            'mobile' => '+98-9121231212',
        ]);
        $this->assertInstanceOf(Mobile::class, $user->mobile);
        $this->assertEquals('98', $user->mobile->code);
        $this->assertEquals('9121231212', $user->mobile->number);

    }

    public function test_order()
    {
        $order = new OrderModel([
            'items' => [
                [
                    'code' => '#123',
                    'quantity' => 10,
                ],
                [
                    'code' => '#124',
                    'quantity' => 1,
                ],
            ]
        ]);
        $this->assertInstanceOf(DataCollection::class, $order->items);
        $this->assertInstanceOf(Item::class, $order->items[0]);
        $this->assertEquals('#124', $order->items[1]->code);
    }
}
