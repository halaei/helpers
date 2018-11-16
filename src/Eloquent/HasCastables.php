<?php

namespace Halaei\Helpers\Eloquent;

use Halaei\Helpers\Objects\Casting;
use Halaei\Helpers\Objects\DataCollection;
use Halaei\Helpers\Objects\DataObject;
use Halaei\Helpers\Objects\Rawable;

trait HasCastables
{
    /**
     * @var DataCollection[]|DataObject[]
     */
    protected $castedAttributes = [];

    /**
     * Boots HasCustomAttributes Trait. By addi
     */
    public static function bootHasCastables()
    {
        static::saving(function ($model) {
            $model->prepareSaving();
        });
    }

    protected function getCastedAttribute($key, $value, $cast)
    {
        if (! array_key_exists($key, $this->castedAttributes)) {
            $this->castedAttributes[$key] = is_null($value) ? null : Casting::cast($value, $cast, $key);
        }
        return $this->castedAttributes[$key];
    }

    /*
     * Overrides
     */

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        foreach ($this->castedAttributes as $key => $value) {
            if ($value instanceof Rawable) {
                $attributes[$key] = $value->toArray();
            }
        }

        return $attributes;
    }

    public function getAttribute($key)
    {
        if (array_key_exists($key, static::$castables)) {
            return $this->getCastedAttribute($key, parent::getAttribute($key), static::$castables[$key]);
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if ($value instanceof Rawable) {
            $this->castedAttributes[$key] = $value;
            return $this;
        }
        unset($this->castedAttributes[$key]);
        return parent::setAttribute($key, $value);
    }

    public function prepareSaving()
    {
        foreach ($this->castedAttributes as $key => $value) {
            $this->attributes[$key] = $value instanceof Rawable ? $value->toRaw() : $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->castedAttributes[$offset]);

        parent::offsetUnset($offset);
    }
}
