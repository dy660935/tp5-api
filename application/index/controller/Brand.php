<?php
namespace app\index\controller;

use app\index\model\Collection;
use app\index\model\Comment;
use app\index\model\Follow;
use app\index\model\Goods;
use app\index\model\Product;
use app\index\model\Share;
use app\index\model\Strategy;
use app\index\model\User;
use think\Config;

class Brand extends Common
{
    /**
     * 品牌主页接口
     */
    public function brandIndex()
    {
        $brand_id = request()->post('id');

        #根据品牌id查出品牌信息
        $brandModel = new \app\index\model\Brand();

        $brandInfo=$brandModel->getBrandInfo($brand_id);

        #获取用户的id
        $session = request()->post('session');

        $open_id=$this->getOpenId($session);

        $userModel = new User();

        $user_id=$userModel->getUserId($open_id);

        #判断用户是否关注
        $follow = new Follow();

        $result=$follow->getUserFollow($user_id,1,$brand_id);

        if($result){

            if($result['following_status']==1){

                $brandInfo['is_follow']=1;

            }else{

                $brandInfo['is_follow']=0;
            }

        }else{

            $brandInfo['is_follow']=0;
        }


        #根据品牌的id查出有关的商品
        $productModel  = new Product();

        $where=['is_hot'=>1];

        $res1=$productModel->getProductData($brand_id,$where);

        $product_num=$res1['num'];

        $res_product_id=$res1['data'];

        $goodsModel = new Goods();

        $shareModel = new Share();

        $commentModel = new Comment();

        $collectionModel = new Collection();

        $data=[];

        foreach ($res_product_id as $k =>$v ){

            $price=$productModel->productInfoFind($v['id']);

            $price['is_product']=1;

            $share_num=$shareModel->getShareNum(1,$v['id']);

            $comment_num=$commentModel->getCommentNum(1,$v['id']);

            $collection_num=$collectionModel->getCollectionNum(1,$v['id']);

            $is_collection=$collectionModel->getUserCollection($user_id,1,$v['id']);

            if($is_collection['collection_status']==1){
                $price['is_collection']=1;
            }else{
                $price['is_collection']=0;
            }

            $price['share_num']=$share_num;

            $price['comment_num']=$comment_num;

            $price['collection_num']=$collection_num;

            $data[]=$price;

        }
        #根据品牌的id查出有关的攻略
        $strategyModel = new Strategy();

        $where=['is_hot'=>1];

        $strategy=$strategyModel->getStrategyBrand($brandInfo['brand_chinese_name'],$where);

        $strategy_data = $strategy['data'];


        foreach ($strategy_data as $str_k=>$str_v){

            $is_collection=$collectionModel->getUserCollection($user_id,2,$str_v['id']);

            if($is_collection['collection_status']==1){

                $strategy_data[$str_k]['is_collection']=1;
            }else{
                $strategy_data[$str_k]['is_collection']=0;
            }

            $strategy_data[$str_k]['share_num']=$shareModel->getShareNum(2,$str_v['id']);

            $strategy_data[$str_k]['comment_num']=$commentModel->getCommentNum(2,$str_v['id']);

            $strategy_data[$str_k]['collection_num']=$collectionModel->getCollectionNum(2,$str_v['id']);

            $strategy_data[$str_k]['is_product']=2;
        }
        $product_info=[];
        #整合数据
        foreach ($data as $kk=>$vv){
            $product_info[]=$vv;
            if(isset($strategy_data[$kk])){
                $product_info[]=$strategy_data[$kk];
            }
        }
        $res['brand_info']=$brandInfo;

        $res['product_info']=$product_info;

     return   $this->successCode($res,'查询成功',1);

    }

    /**
     * 品牌的tab
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brandTab(){

        $brand_id = request()->post('id');

        $brand_type = request()->post('brand_type');

        $brandModel = new \app\index\model\Brand();

        $goodsModel = new Goods();

        $shareModel = new Share();

        $commentModel = new Comment();

        $collectionModel = new Collection();

        $productModel  = new Product();

        $brandInfo=$brandModel->getBrandInfo($brand_id);
        #获取用户的id
        $session = request()->post('session');

        $open_id=$this->getOpenId($session);

        $userModel = new User();

        $user_id=$userModel->getUserId($open_id);

        $data=[];
        #查询热门商品和攻略
        if($brand_type==0){
            #根据品牌的id查出有关的商品
            $where=['is_hot'=>1];

            $res1=$productModel->getProductData($brand_id,$where);

            $product_num=$res1['num'];

            $res_product_id=$res1['data'];

            foreach ($res_product_id as $k =>$v ){

                $price=$productModel->productInfoFind($v['id']);

                $price['is_product']=1;

                $share_num=$shareModel->getShareNum(1,$v['id']);

                $comment_num=$commentModel->getCommentNum(1,$v['id']);

                $collection_num=$collectionModel->getCollectionNum(1,$v['id']);

                $is_collection=$collectionModel->getUserCollection($user_id,1,$v['id']);

                if($is_collection['collection_status']==1){
                    $price['is_collection']=1;
                }else{
                    $price['is_collection']=0;
                }

                $price['share_num']=$share_num;
                $price['comment_num']=$comment_num;
                $price['collection_num']=$collection_num;
                $data[]=$price;

            }

        #根据品牌的id查出有关的攻略
        $strategyModel = new Strategy();

        $where=['is_hot'=>1];

        $strategy=$strategyModel->getStrategyBrand($brandInfo['brand_chinese_name'],$where);

        $strategy_data = $strategy['data'];


        foreach ($strategy_data as $str_k=>$str_v){

            $is_collection=$collectionModel->getUserCollection($user_id,2,$str_v['id']);

            if($is_collection['collection_status']==1){
                $strategy_data[$str_k]['is_collection']=1;
            }else{
                $strategy_data[$str_k]['is_collection']=0;
            }

            $strategy_data[$str_k]['share_num']=$shareModel->getShareNum(2,$str_v['id']);

            $strategy_data[$str_k]['comment_num']=$commentModel->getCommentNum(2,$str_v['id']);

            $strategy_data[$str_k]['collection_num']=$collectionModel->getCollectionNum(2,$str_v['id']);

            $strategy_data[$str_k]['is_product']=2;

        }
        $product_info=[];

        foreach ($data as $kk=>$vv){

            $product_info[]=$vv;

            if(isset($strategy_data[$kk])){

                $product_info[]=$strategy_data[$kk];

            }
        }

            return    $this->successCode($product_info,'查询成功',1);


        }elseif($brand_type==1){
            #查询该品牌下的商品

            $res1=$productModel->getProductData($brand_id);

            $product_num=$res1['num'];

            $res_product_id=$res1['data'];

            foreach ($res_product_id as $k =>$v ){

                $price=$productModel->productInfoFind($v['id']);

                $price['is_product']=1;

                $share_num=$shareModel->getShareNum(1,$v['id']);

                $comment_num=$commentModel->getCommentNum(1,$v['id']);

                $collection_num=$collectionModel->getCollectionNum(1,$v['id']);

                $is_collection=$collectionModel->getUserCollection($user_id,1,$v['id']);

                if($is_collection['collection_status']==1){

                    $price['is_collection']=1;

                }else{

                    $price['is_collection']=0;
                }


                $price['share_num']=$share_num;
                $price['comment_num']=$comment_num;
                $price['collection_num']=$collection_num;
                $data[]=$price;

            }
            return   $this->successCode($data,'查询成功',1);

        }else{
            #查询该品牌下的攻略
            $strategyModel = new Strategy();

            $where=['is_hot'=>1];

            $strategy=$strategyModel->getStrategyBrand($brandInfo['brand_chinese_name'],$where);

            $strategy_data = $strategy['data'];


            foreach ($strategy_data as $str_k=>$str_v){

                $is_collection=$collectionModel->getUserCollection($user_id,2,$str_v['id']);

                if($is_collection['collection_status']==1){

                    $strategy_data[$str_k]['is_collection']=1;

                }else{

                    $strategy_data[$str_k]['is_collection']=0;

                }
                $strategy_data[$str_k]['share_num']=$shareModel->getShareNum(2,$str_v['id']);

                $strategy_data[$str_k]['comment_num']=$commentModel->getCommentNum(2,$str_v['id']);

                $strategy_data[$str_k]['collection_num']=$collectionModel->getCollectionNum(2,$str_v['id']);

                $strategy_data[$str_k]['is_product']=2;

            }

            return   $this->successCode($strategy_data,'查询成功',1);
        }
    }
}
