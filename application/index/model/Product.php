<?php

namespace app\index\model;


use think\Config;
use think\Db;

class Product extends Base
{
    protected $table = 'fb_product';

    public function goods()
    {
        return $this->hasMany( 'Goods' , 'product_id' , 'id' )->field( 'shop_price' );
        //        return $this->belongsToMany('Goods','fb_goods','id','product_id');
    }

    /*
     * 获取商品的品牌id
     */
    public function getProductAll( $where = '' )
    {
        if( $where ) {

            $result = Product::where( 'product_status' , 1 )
                ->where( $where )
                ->column( 'brand_id' );
        }
        else {

            $result = Product::where( 'product_status' , 1 )
                ->column( 'brand_id' );
        }

        if( empty( $result ) ) {

            return [];

        }
        else {

            $result = array_unique( $result );

            $result = implode( ',' , $result );

            return $result;
        }
    }

    /**
     * 获取获取首页tab分页
     *
     * @param $category_id
     * @param $p
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getIndexCategoryProduct( $category_id , $p )
    {
        $limit = $this->getLimit( $p );

        $product_num = Config::get( 'api.p_num' ) - 1;

        if( $category_id == 0 ) {
            //            $where = ['is_hot' => 1, 'product_status' => 1];
            $where = [ 'product_status' => 1 ];
        }
        else {
            $where = [ 'product_status' => 1 , 'category_id' => $category_id ];
        }

        //查询spu
        $product_info = Product::where( $where )
            ->field( 'id,product_name,product_image,original_product_id' )
            ->order( 'product_weight' , 'desc' )
            ->limit( $limit , $product_num )
            ->select()
            ->toArray();

        $product_info = $this->getSku( $product_info );

        $product_info = $this->getNewProductInfo( $product_info );

        return $product_info;
    }


    public function getSku( $product_info )
    {
        foreach( $product_info as $k => $v ) {

            $product_info[ $k ][ 'detail' ] = $this->getProductDetail( $v[ 'id' ] , $v[ 'original_product_id' ] );

        }

        foreach( $product_info as $k => $v ) {

            foreach( $v[ 'detail' ] as $d_k => $d_v ) {

                $product_info[ $k ][ 'website_name' ] = $d_v[ 'website_name' ];
                $product_info[ $k ][ 'attribute_value' ] = $d_v[ 'attribute_value' ];
                $product_info[ $k ][ 'shop_price' ] = $d_v[ 'shop_price' ];
                $product_info[ $k ][ 'market_price' ] = $d_v[ 'market_price' ];
                $product_info[ $k ][ 'is_product' ] = $d_v[ 'is_product' ];
                $product_info[ $k ][ 'is_discount' ] = $d_v[ 'is_discount' ];
                $product_info[ $k ][ 'discount_num' ] = $d_v[ 'discount_num' ];

            }

            unset( $product_info[ $k ][ 'detail' ] );
        }
        return $product_info;
    }


    public function productInfoFind( $product_id )
    {
        /** 查询最低价*/
        $lower_price = Db::table( "fb_product" )
            ->alias( 'a' )
            ->join( "fb_goods b" , 'a.id=b.product_id' )
            ->join( 'fb_website c' , 'b.orignal_website=c.website_abbreviation' )
            ->where( [ 'product_id' => $product_id ] )
            ->where( [ 'is_best_price' => 1 ] )
            ->field( 'a.id,shop_price,original_goods_id,good_specs,website_name,pay_way' )
            ->find();
        var_dump( $lower_price );

        $res = $this->getWebsitePrice( $product_id );


        /**查询主体参考价*/
        $subject_price = Db::table( "fb_product" )
            ->alias( 'a' )
            ->join( "fb_goods b" , 'a.id=b.product_id' )
            ->where( [ 'product_id' => $product_id ] )
            ->where( [ 'original_product_id' => $lower_price[ 'original_product_id' ] ] )
            ->field( 'a.id,market_price' )
            ->find();


        //        $product_info = Db::table( "fb_product" )
        //            ->field( 'id,product_name,product_image,original_product_id' )
        //            ->where( [ 'id' => $product_id ] )
        //            ->find();
        //
        //        $detail = $this->getProductDetail( $product_id , $product_info[ 'original_product_id' ] );
        //
        //        foreach( $detail as $k => $v ) {
        //            $product_info[ 'attribute_value' ] = $v[ 'attribute_value' ];
        //            $product_info[ 'shop_price' ] = $v[ 'shop_price' ];
        //            $product_info[ 'market_price' ] = $v[ 'market_price' ];
        //            $product_info[ 'category_id' ] = $v[ 'category_id' ];
        //            $product_info[ 'is_discount' ] = $v[ 'is_discount' ];
        //            $product_info[ 'discount_num' ] = $v[ 'discount_num' ];
        //        }
        //
        //        $goods_model = new Goods();
                       $price_all = $goods_model->getGoodBestPrice( $product_id );
        //        $product_info[ 'price' ] = $price_all[ 'price' ];
        //        $other_price = $price_all[ 'other_price' ];
        //        if( $other_price ) {
        //            $product_info[ 'other_price' ] = $price_all[ 'other_price' ];
        //        }
        //
        //        $product_info[ 'product_name' ] = $product_info[ 'product_name' ] . ' ' . $product_info[ 'attribute_value' ];
        //
        //        return $product_info;
    }

    public function getWebsitePrice( $product_id )
    {

        /** 全球最低价 */
        $lower_message = $this->getCommonPrice( $product_id );


        $lower_price = [
            [
                'shop_price' => $lower_message[ 'shop_price' ] ,
                'website_name' => $this->isDuty( $lower_message[ 'pay_way' ] , $lower_message[ 'website_name' ] , $lower_message[ 'shop_name' ] ) ,
                'website_thumbnail' => $lower_message[ 'shop_price' ] ,
                'price_type' => '全球最低价'
            ]
        ];


        if( $lower_message[ 'pay_way' ] == 1 || $lower_message[ 'pay_way' ] == 2 ) {

            /** 免税店最低价 */
            $duty_where = [ 5 , 6 ];
            $duty_message = $this->getCommonPrice( $product_id ,$duty_where);

            /** 海淘最低价 */
            $sea_where = [ 3 , 4 ];
            $sea_message = $this->getCommonPrice( $product_id ,$sea_where);


        }
        elseif( $lower_message[ 'pay_way' ] == 3 || $lower_message[ 'pay_way' ] == 4 ) {

            /** 国内最低价 */
            $china_where = [ 1 , 2 ];
            $china_message = $this->getCommonPrice( $product_id ,$china_where);

            /** 免税店最低价 */
            $duty_where = [ 5 , 6 ];
            $duty_message = $this->getCommonPrice( $product_id ,$duty_where);

        }
        else {

            /** 国内最低价 */
            $china_where = [ 1 , 2 ];
            $china_message = $this->getCommonPrice( $product_id ,$china_where);

            /** 海淘最低价 */
            $sea_where = [ 3 , 4 ];
            $sea_message = $this->getCommonPrice( $product_id ,$sea_where);

        }


    }


    public function getCommonPrice( $product_id , $where = [] )
    {
        if( empty( $where ) ) {
            $price = Db::table( "fb_product" )
                ->alias( 'a' )
                ->join( "fb_goods b" , 'a.id=b.product_id' )
                ->join( 'fb_website c' , 'b.orignal_website=c.website_abbreviation' )
                ->where( [ 'product_id' => $product_id ] )
                ->where( [ 'is_best_price' => 1 ] )
                ->where( [ 'goods_status' => 1 ] )
                ->field( 'a.id,shop_price,pay_way,original_goods_id,website_name,pay_way,shop_name' )
                ->find();
        }
        else {
            $price = Db::table( "fb_product" )
                ->alias( 'a' )
                ->join( "fb_goods b" , 'a.id=b.product_id' )
                ->join( 'fb_website c' , 'b.orignal_website=c.website_abbreviation' )
                ->where( [ 'product_id' => $product_id ] )
                ->where( 'c.pay_way' , 'in' , $where )
                ->where( [ 'goods_status' => 1 ] )
                ->field( 'a.id,shop_price,pay_way,original_goods_id,website_name,pay_way,shop_name' )
                ->order( 'shop_price' , 'asc' )
                ->find();

        }

        return $price;
    }

    public function getPrice( $price , $price_type )
    {

        if( $price ) {
            $data[ 'shop_price' ] = '￥' . intval( $price[ 'shop_price' ] );
            $data[ 'website_name' ] = $price[ 'website_name' ];
            $data[ 'website_thumbnail' ] = $price[ 'website_thumbnail' ];
            $data[ 'price_type' ] = $price_type;
            $data[ 'website_abbreviation' ] = $price[ 'website_abbreviation' ];
        }
        else {
            //            $data['shop_price']="暂无";
            $data[ 'shop_price' ] = '';
            //            $data['website_name']="----";
            $data[ 'website_name' ] = "";
            $data[ 'website_thumbnail' ] = "";
            $data[ 'price_type' ] = $price_type;
            $data[ 'website_abbreviation' ] = '';
        }
        return $data;
    }


    /**
     * 是否是免税店商品
     *
     * @param $pay_way
     * @param $website_name
     * @param $shop_name
     *
     * @return mixed
     */
    public function isDuty( $pay_way , $website_name , $shop_name )
    {
        if( $pay_way == 5 ) {
            return $shop_name;
        }
        else {
            return $website_name;
        }
    }


    /**
     * 根据品牌获取商品
     */
    public function getProductData( $brand_id , $where = '' )
    {

        if( !$where ) {
            #根据品牌的id获取品牌下的商品
            $result = Product::where( [ 'is_deleted' => 1 , 'product_status' => 1 , 'brand_id' => $brand_id ] )
                ->field( 'id,product_name,click_number,product_image' )->select();
        }
        else {
            $result = Product::where( [ 'is_deleted' => 1 , 'product_status' => 1 , 'brand_id' => $brand_id ] )
                ->where( $where )
                ->field( 'id,product_name,click_number,product_image' )->select();
        }

        if( $result ) {
            $data[ 'num' ] = $result->count();

            $data[ 'data' ] = $result->toArray();

            return $data;

        }
        else {

            return false;
        }

    }

}
