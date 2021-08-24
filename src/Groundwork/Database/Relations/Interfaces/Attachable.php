<?php

namespace Groundwork\Database\Relations\Interfaces;

interface Attachable
{
    /**
     * Attaches the given model ID's to the parent model.
     *
     * @param int|int[] $ids
     *
     * @return bool
     */
    public function attach($ids) : bool;

    /**
     * Detaches the given model ID's from the parent model.
     *
     * @param int|int[] $ids
     *
     * @return bool
     */
    public function detach($ids) : bool;

    /**
     * Synchronizes the attached model ID's for the parent model.
     *
     * @param int|int[] $ids
     *
     * @return bool
     */
    public function sync($ids) : bool;

    /**
     * Toggles the given model ID's with the parent model.
     *
     * @param int|int[] $ids
     *
     * @return bool
     */
    public function toggle($ids) : bool;
}