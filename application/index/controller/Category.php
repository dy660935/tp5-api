<?php
namespace app\index\controller;

use app\index\model\Brand;
use app\index\model\Product;
use think\Config;

class Category extends Common
{
    /**
     * 分类首页接口
     */
    public function categoryIndex()
    {
        $productModel = new Product();

        $res = $productModel ->getProductAll();

        $categoryModel = new \app\index\model\Category();

        $category_name = Config::get('api.category_index');

        $categoryInfo=$categoryModel->getIndexCategoryInfo($category_name);

        $brandModel = new Brand();

        if($res){

            $hotBrand=$brandModel->getHotBrand($res);

        }else{

            $hotBrand=$brandModel->getHotBrand();

        }
        $json_data = [
            'category_info' => $categoryInfo,
            'hot_brand_info' => $hotBrand
        ];


        if($json_data){

            return $this->successCode($json_data,'查询成功',1);

        }else{

            return $this->errorCode('','暂时没有哦','1000');
        }

    }

    /**
     * 分类下品牌接口
     */
    public function categoryBrand(){

        $category_id = request()->post('id');

        $productModel = new Product();

        $brandModel = new Brand();

        if($category_id==0){

            $res = $productModel ->getProductAll();

            $brand_category_info=$brandModel->getHotBrand($res);

        }else{

            $where=['category_id'=>$category_id];

            $res = $productModel ->getProductAll($where);

            $brand_category_info=$brandModel->getBrand($res);

        }

        return $this->successCode($brand_category_info,'查询成功',1);
    }
}
