<?php

namespace Halaei\Helpers\Eloquent;

trait ThroughRelationsTrait
{
    /**
     * Define a one-to-one relationship that goes through an intermediate model.
     *
     * @param  string  $related
     * @param  string  $through
     * @param  string  $thisForeignKey
     * @param  string  $throughForeignKey
     * @return HasOneThrough
     */
    public function hasOneThrough($related, $through, $thisForeignKey = null, $throughForeignKey = null)
    {
        $related = $this->newRelatedInstance($related);
        $through = new $through;
        $thisForeignKey = $thisForeignKey ?: $this->getForeignKey();
        $throughForeignKey = $throughForeignKey ?: $through->getForeignKey();

        return new HasOneThrough($related->newQuery(), $this, $through, $thisForeignKey, $throughForeignKey);
    }

    /**
     * Define an inverse one-to-one relationship that goes through an intermediate model.
     *
     * @param  string  $related
     * @param  string  $through
     * @param  string  $throughForeignKey
     * @param  string  $relatedForeignKey
     * @return BelongsToThrough
     */
    public function belongsToThrough($related, $through, $throughForeignKey = null, $relatedForeignKey = null)
    {
        $related = $this->newRelatedInstance($related);
        $through = new $through;
        $throughForeignKey = $throughForeignKey ?: $through->getForeignKey();
        $relatedForeignKey = $relatedForeignKey ?: $related->getForeignKey();

        return new BelongsToThrough($related->newQuery(), $this, $through, $throughForeignKey, $relatedForeignKey);
    }
}
