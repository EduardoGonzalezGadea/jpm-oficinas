<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (in_array(SoftDeletes::class, class_uses_recursive(get_class($model)))) {
                if (Auth::check()) {
                    $model->deleted_by = Auth::id();
                    $model->save();
                }
            }
        });
    }
}
