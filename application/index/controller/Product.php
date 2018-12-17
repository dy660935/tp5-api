<?php

namespace app\index\controller;

use app\index\model\Comment;
use app\index\model\Goods;
use app\index\model\User;

class Product extends Common
{
    /**
     * 产品详情页面
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productDetail()
    {

        $product_id = request()->post( "product_id" );
        //        $session = request()->post('session');
        //
        //        if(empty($session)){
        //
        //            return $this -> errorCode('session参数有误','',1000);
        //
        //        }

        if( empty( $product_id ) ) {

            return $this->errorCode( '参数有误' , '' , 1000 );

        }
        else {
            $product_model = new \app\index\model\Product();

            $product_info = $product_model->productInfoFind( $product_id );
            die;
            //获取用户的user_id
            //            $open_id = $this ->getOpenId($session);
            //
            //            $user_model = new User();
            //
            //            $user_id = $user_model -> getUserId($open_id);

            //是否收藏
            //            $collection_model = new \app\index\model\Collection();
            //
            //            $is_collection=$collection_model->getUserCollection($user_id,1,$product_id);
            //
            //            if($is_collection['collection_status']==1){
            //
            //                $product_info['is_collection'] = 1;
            //
            //            }else{
            //
            //                $product_info['is_collection'] = 0;
            //            }

            //获取商品的评论

            //            $comment_model = new Comment();
            //
            //            $comment_info = $comment_model -> getCommentAll($product_id,1,$user_id);
            //            $comment_num=$comment_model->getCommentNum(1,$product_id);
            //
            //            if($comment_info){
            //                $product_info['comment_info'] = $comment_info;
            //                $product_info['comment_num'] = $comment_num;
            //            }else{
            //                $product_info['comment_info'] = [];
            //                $product_info['comment_num'] = 0;
            //            }

            //增加浏览记录
            //            $history_model = new \app\index\model\History();
            //
            //            $history_model ->getUserHistory($user_id,1,$product_id);
            $price_count = 0;

            foreach( $product_info[ 'price' ] as $k => $v ) {
                if( !empty( $v[ 'shop_price' ] ) ) {
                    $price_count += 1;
                }
            }
            if( isset( $product_info[ 'other_price' ] ) ) {
                $count_all = $price_count + count( $product_info[ 'other_price' ] );
            }
            else {
                $count_all = $price_count;
            }
            $product_info[ 'price_count' ] = $count_all;
            //            var_dump($count_all);die;
            return $this->successCode( $product_info , '成功' , 1 );

        }

    }

    /** 商品落地页 */
    public function productDetailsPage()
    {

        $product_id = request()->post( "product_id" );

        $website_abbreviation = request()->post( "website_abbreviation" );

        if( empty( $product_id ) || empty( $website_abbreviation ) ) {
            return $this->errorCode( '参数有误' , '' , 1000 );
        }

        $goods_model = new Goods();

        $product_info = $goods_model->getProductDetailsPage( $product_id , $website_abbreviation );

        var_dump( $product_info );
        die;
    }
}
