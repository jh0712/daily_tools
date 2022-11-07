<?php
namespace Modules\System\Helpers;
use App\Models\User;
use Illuminate\Support\Facades\Cache;


class RedisUser{ //cache-user class
    public static function user($id){
        if(!$id||$id<=0||!is_numeric($id)){return;} // if $id is not a reasonable integer, return false instead of checking users table
        #$key = 'cachedUser.'.$id;
        $key = User::getTableName().'.'.$id;
        return Cache::remember($key, 1800, function() use($id) {
            return User::find($id); // cache user instance for 30 minutes
        });
    }
}
