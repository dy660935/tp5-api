<?php
namespace app\index\model;

use think\Config;
use think\Model;

class Category extends Model
{
    protected $table='fb_category';

    public function getIndexCategoryInfo($category_name="")
    {
        $category_info = Category::where(['category_status' => 1, 'category_level' =>1])
            ->limit(10)
            ->field('id,category_name')
            ->select()
            ->toArray();
        if($category_name){

            array_unshift($category_info, $category_name);

        }


        return $category_info;
    }
}