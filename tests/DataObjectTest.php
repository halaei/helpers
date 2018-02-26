<?php

namespace HalaeiTests;

use Halaei\Helpers\Objects\DataCollection;
use Halaei\Helpers\Objects\DataObject;

class DataObjectTest extends \PHPUnit_Framework_TestCase
{
    public function test_a_user_object()
    {
        $input = [
            'id' => 1,
            'name' => 'Hamid Alaei V.',
            'mobile' => '+98-9131231212',
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
        $this->assertEquals(new Mobile(['code' => '+98', 'number' => '9131231212']), $user->getMobile());
        $this->assertCount(2, $user->orders);
        $this->assertInstanceOf(Order::class, $user->orders[0]);
        $this->assertInstanceOf(Item::class, $user->orders[0]->items[0]);
        $this->assertEquals('#124', $user->orders[0]->items[1]->code);
        $this->assertNull($user->orders[1]->items);
        $this->assertEquals($input, $user->toRaw());
        $user->mobile->number = '9130000000';
        $this->assertEquals('9130000000', $user->mobile->number);
        $user->getMobile()->setCode('+99');
        $this->assertEquals('+99', $user->mobile->code);
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

    public function test_fuse_users()
    {
        $user1 = new User([
            'id' => 1,
            'name' => 'Hamid',
            'mobile' => '+98-9131111111',
            'orders' => [],
        ]);
        $user2 = new User([
            'id' => 1,
            'name' => 'Hamid Alaei',
            'mobile' => '+98-9131231212',
            'orders' => null,
        ]);
        $this->assertSame($user1->fuse($user2, ['orders'])->toRaw(), [
            'id' => 1,
            'name' => 'Hamid Alaei',
            'mobile' => '+98-9131231212',
            'orders' => [],
        ]);
    }

    public function test_fuse_collections_of_users()
    {
        $users1 = new DataCollection([
            new User([
                'id' => 1,
                'name' => 'Foo',
                'orders' => [['id' => 1]],
            ]),
            new User([
                'id' => 2,
                'name' => 'Bar',
                'orders' => [['id' => 2]],
            ]),
            new User([
                'id' => 3,
                'name' => 'Baz',
                'orders' => [['id' => 3]],
            ]),
        ]);
        $users2 = new DataCollection([
            new User([
                'id' => 1,
                'name' => 'Foobar',
                'orders' => [['id' => 4]],
            ]),
            new User([
                'id' => 4,
                'name' => 'FooBarBaz',
                'orders' => null,
            ]),
        ]);
        $fused = $users1->fuse($users2, 'id', ['orders']);
        $this->assertSame([
            [
                'id' => 1,
                'name' => 'Foobar',
                'orders' => [['id' => 1]],
            ],
            [
                'id' => 2,
                'name' => 'Bar',
                'orders' => [['id' => 2]],
            ],
            [
                'id' => 3,
                'name' => 'Baz',
                'orders' => [['id' => 3]],
            ],
            [
                'id' => 4,
                'name' => 'FooBarBaz',
                'orders' => null,
            ]
        ], $fused->toArray());
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
    public static function relations()
    {
        return [
            'mobile' => [Mobile::class, 'decode'],
            'orders' => [Order::class],
        ];
    }
}

/**
 * @property string $code
 * @property string $number
 */
class Mobile extends DataObject
{
    public static function decode($str)
    {
        if (preg_match('/^\+(\d+)-(\d+)$/', $str, $parts)) {
            return new self(['code' => $parts[1], 'number' => $parts[2]]);
        }
    }

    public function toRaw()
    {
        return '+'.$this->code.'-'.$this->number;
    }
}

/**
 * @property string $id
 * @property Item[] $items
 */
class Order extends DataObject
{
    public static function relations()
    {
        return [
            'items' => [Item::class],
        ];
    }
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
    public static function relations()
    {
        return ['cells' => [[Cell::class]]];
    }
}

/**
 * @property $v
 */
class Cell extends DataObject
{
}
