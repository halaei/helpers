<?php

namespace HalaeiTests;

use Halaei\Helpers\Eloquent\HasCastables;
use Halaei\Helpers\Objects\DataObject;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property Mobile $mobile
 * @property Order[] $orders
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

class UserModel extends Model
{
    use HasCastables;

    protected $fillable = ['mobile'];

    protected static $castables = [
        'mobile' => [Mobile::class, 'decode'],
    ];
}

class OrderModel extends Model
{
    use HasCastables;

    protected $fillable = ['items'];

    protected static $castables = [
        'items' => [Item::class],
    ];
}
