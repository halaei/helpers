<?php

namespace HalaeiTests;

use Halaei\Helpers\Objects\DataObject;

class DataObjectTest extends \PHPUnit_Framework_TestCase
{
    public function test_a_user_object()
    {
        $input = [
            'id' => 1,
            'name' => 'Hamid Alaei V.',
            'mobile' => [
                'code' => '+98',
                'number' => '9131231212',
            ],
            'orders' => [
                [
                    'id' => 11,
                    'items' => [
                        [
                            'code' => '#123',
                            'quantity' => 10,
                        ],
                        [
                            'code' => '#124',
                            'quantity' => 1,
                        ],
                    ],
                ],
                [
                    'id' => 12,
                ],
            ]
        ];
        $user = new User($input);
        $this->assertEquals(1, $user->id);
        $this->assertNull($user->password);
        $this->assertInstanceOf(Mobile::class, $user->mobile);
        $this->assertCount(2, $user->orders);
        $this->assertInstanceOf(Order::class, $user->orders[0]);
        $this->assertInstanceOf(Item::class, $user->orders[0]->items[0]);
        $this->assertEquals('#124', $user->orders[0]->items[1]->code);
        $this->assertNull($user->orders[1]->items);
        $this->assertEquals($input, $user->toArray());
    }

    public function test_matrix()
    {
        $input = [
            [['v' => '1'], ['v' => '2']],
            [['v' => '3'], ['v' => '4']],
        ];
        $matrix = new Matrix(['cells' => $input]);
        $this->assertInstanceOf(Cell::class, $matrix->cells[0][0]);
        $this->assertEquals(['cells' => $input], $matrix->toArray());
    }
}

/**
 * @property int $id
 * @property string $name
 * @property Mobile $mobile
 * @property Order[] $orders
 * @property
 */
class User extends DataObject
{
    protected static $relations = [
        'mobile' => Mobile::class,
        'orders' => Order::class.'[]',
    ];
}

/**
 * @property string $code
 * @property string $number
 */
class Mobile extends DataObject
{
}

/**
 * @property string $id
 * @property Item[] $items
 */
class Order extends DataObject
{
    protected static $relations = [
        'items' => Item::class.'[]',
    ];
}

/**
 * @property string $code
 * @property int $quantity
 */
class Item extends DataObject
{
}

/**
 * @property Cell[][] $cells
 */
class Matrix extends DataObject
{
    protected static $relations = ['cells' => Cell::class.'[][]'];
}

/**
 * @property $v
 */
class Cell extends DataObject
{
}