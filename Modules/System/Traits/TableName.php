<?php

namespace Modules\System\Traits;

use Illuminate\Support\Facades\DB;

trait TableName
{
    public static function getTableName()
    {
        // return '"' . with(new static)->getConnection()->getConfig('database') . '".' . with(new static)->getTable();
        return DB::raw(with(new static())->getConnection()->getConfig('database')).'.'.with(new static())->getTable();
    }
}
