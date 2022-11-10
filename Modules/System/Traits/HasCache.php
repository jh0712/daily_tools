<?php
namespace Modules\Blackwell\Traits;


use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Cache;
trait HasCache{
    /**
     *    register observer
     *    path: \Modules\{$module_name}\Observers\{Model_class_name}Observer
     *    Observer name rule: {Model_class_name} + Observer
     *    @example
     *       Model path: \Modules\User\Entities\Partner\User.php
     *       Observer path:\Modules\User\Observers\UserObserver.php
     */
    public static function bootHasCache()
    {
        $class = static::class;
        $arr_class = explode("\\", $class);
        $module_name = $arr_class[1];
        $observer_path = "Modules\\$module_name\\Observers\\".class_basename($class).'Observer';
        static::observe(app($observer_path));
    }
    /**
     *    //dropdown_cache
     */
    public function dropdown_cache()
    {
        $model = $this->model;
        if(empty($this->model)){
            //empty
            $model = $this;
        }else{
            //not empty
            $model = $this->model;
        }
        $key = $model->getTableName();
        return Cache::remember($key, 1800, function()use ($model){
            return $model->all();
        });
    }
    /**
     *    find
     */
    public  function find_cache($id){
        $model = $this->model;
        if(empty($this->model)){
            //empty
            $model = $this;
        }else{
            //not empty
            $model = $this->model;
        }
        $key = $model->getTableName();
        if (is_array($id) || $id instanceof Arrayable) {
            return $this->findMany_cache($id,$model,$key);
        }

        return Cache::remember($key, 1800, function()use ($model){
            return $model->all();
        })->where($model->getKeyName(),$id)->first();
    }
    /**
     *    find_many
     */
    public function findMany_cache($id,$model,$key){
        return Cache::remember($key, 1800, function()use ($model){
            return $model->all();
        })->whereIn($model->getKeyName(),$id);
    }
}
