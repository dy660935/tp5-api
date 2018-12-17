<?php
namespace app\index\model;

use think\Db;
use think\Model;

class Brand extends Model
{
    protected $table='fb_brand';

    public function getHotBrand($brand_id){

        if ($brand_id){
            $result=Brand::where(['is_hot'=>1,'brand_status'=>1,'is_deteled'=>1])
                ->where('id','in',$brand_id)
                ->order('brand_weight','asc')
                ->field(['id','brand_chinese_name','orginal_brand_logo'])
                ->select()
                ->toArray();
        }else{

            $result=Brand::where(['is_hot'=>1,'brand_status'=>1,'is_deteled'=>1])
                ->order('brand_weight','asc')
                ->field(['id','brand_chinese_name','orginal_brand_logo'])
                ->select()
                ->toArray();
        }

        if($result){

            return $result;
        }else{
            return [];
        }
    }

    /**
     * @param $brand_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *
     */

    public function getBrand($brand_id){

//        $brand_id="1,16,29,31,38,39,46,48,49,58,66,67,69,71,74,80,92,93,118";

        if ($brand_id){

            $result=Brand::where(['brand_status'=>1,'is_deteled'=>1])
                ->where('id','in',$brand_id)
                ->order('brand_weight','asc')
                ->field(['id','brand_chinese_name','orginal_brand_logo'])
                ->select()
                ->toArray();

        }else{

            $result=[];

        }

        if($result){

            return $result;

        }else{
            return [];
        }
    }

    /**
     * 获取品牌的详情
     * @param $brand_id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBrandInfo($brand_id){

        if ($brand_id){

            $result=Brand::where(['brand_status'=>1,'is_deteled'=>1])
                ->where('id','in',$brand_id)
                ->field(['id','brand_chinese_name','orginal_brand_logo'])
                ->find()
                ->toArray();

        }else{

            $result=[];

        }

        if($result){

            return $result;

        }else{
            return [];
        }
    }

}