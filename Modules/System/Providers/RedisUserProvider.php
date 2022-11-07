<?php
namespace Modules\System\Providers;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Modules\System\Helpers\RedisUser;


class RedisUserProvider extends EloquentUserProvider{
    public function __construct(HasherContract $hasher)
    {
        parent::__construct($hasher,User::class);
    }
    public function retrieveById($identifier)
    {
        return RedisUser::user($identifier);
    }
     public function retrieveByToken($identifier, $token)
    {
        $model = RedisUser::user($identifier);

        if (! $model) {
            return null;
        }
        $rememberToken = $model->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $model : null;
    }
}
