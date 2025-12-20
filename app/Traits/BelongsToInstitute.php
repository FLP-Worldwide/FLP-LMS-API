<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToInstitute
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToInstitute()
    {
        // 🔐 AUTO FILTER DATA (SELECT)
        static::addGlobalScope('institute', function (Builder $builder) {

            $user = Auth::user();

            // Allow Super Admin to see everything
            if (!$user || $user->role === 'super_admin') {
                return;
            }

            $institute = $user->institutes()->first();

            if ($institute) {
                $builder->where(
                    $builder->getModel()->getTable() . '.institute_id',
                    $institute->id
                );
            }
        });

        // 🧠 AUTO ATTACH institute_id (INSERT)
        static::creating(function ($model) {

            $user = Auth::user();

            if (!$user || $user->role === 'super_admin') {
                return;
            }

            // If already set, don't override
            if (!empty($model->institute_id)) {
                return;
            }

            $institute = $user->institutes()->first();

            if ($institute) {
                $model->institute_id = $institute->id;
            }
        });
    }
}
