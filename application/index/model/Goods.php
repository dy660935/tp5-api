<?php

namespace app\index\model;

use think\Db;
use think\Model;

class Goods extends Base
{
    protected $table = 'fb_goods';

    public function product()
    {
        return $this->hasOne( 'Product' , 'id' , 'product_id' );
    }

    /**
     * 获取全球最低价
     *
     * @param $product_id
     *
     * @return mixed
     */
    public function getGoodBestPrice( $product_id )
    {

        $best_price = $this->getCommonPrice( $product_id );

        #根据$brand_price['id']需要查询出属性值
        $sql = "select 
            attribute_value
          from fb_attribute_value_enum as a 
          join fb_goods_attribute_mapping as b on a.id = b.attribute_value_id 
          where  b.goods_id = $best_price[id] 
          limit 1";

        $attribute_value = Db::query( $sql );

        foreach( $attribute_value as $k => $v ) {

            $attribute_value = $v[ 'attribute_value' ];

        }
        $data[ 'attribute_value' ] = $attribute_value;

        $id[] = $best_price[ 'id' ];

        $price_type1 = "全球最低价";

        $data[ 'price' ][] = $this->getPrice( $best_price , $price_type1 );

        //        var_dump($best_price);die;

        if( $best_price[ 'pay_way' ] == 1 || $best_price[ 'pay_way' ] == 2 ) {

            $main_where = [ 6 , 5 ];

            $main_price = $this->getCommonPrice( $product_id , $main_where );

            $id[] = $main_price[ 'id' ];

            $price_type2 = "免税店最低价";

            $data[ 'price' ][] = $this->getPrice( $main_price , $price_type2 );

            $hai_price = [ 3 , 4 ];

            $main_price = $this->getCommonPrice( $product_id , $hai_price );

            $id[] = $main_price[ 'id' ];

            $price_type4 = "海淘最低价";

            $data[ 'price' ][] = $this->getPrice( $main_price , $price_type4 );

        }
        elseif( $best_price[ 'pay_way' ] == 3 || $best_price[ 'pay_way' ] == 4 ) {

            $guo_where = [ 1 , 2 ];

            $guo_price = $this->getCommonPrice( $product_id , $guo_where );

            $id[] = $guo_price[ 'id' ];

            $price_type3 = "国内最低价";

            $data[ 'price' ][] = $this->getPrice( $guo_price , $price_type3 );

            $main_where = [ 6 , 5 ];

            $main_price = $this->getCommonPrice( $product_id , $main_where );

            $id[] = $main_price[ 'id' ];

            $price_type2 = "免税店最低价";

            $data[ 'price' ][] = $this->getPrice( $main_price , $price_type2 );

        }
        else {

            $guo_where = [ 1 , 2 ];

            $guo_price = $this->getCommonPrice( $product_id , $guo_where );

            $id[] = $guo_price[ 'id' ];

            $price_type3 = "国内最低价";

            $data[ 'price' ][] = $this->getPrice( $guo_price , $price_type3 );

            $hai_price = [ 3 , 4 ];

            $main_price = $this->getCommonPrice( $product_id , $hai_price );

            $id[] = $main_price[ 'id' ];

            $price_type4 = "海淘最低价";

            $data[ 'price' ][] = $this->getPrice( $main_price , $price_type4 );
        }
        $id = array_filter( $id );

        $good_id = implode( ',' , $id );

        $data[ 'other_price' ] = $this->getOtherPrice( $product_id , $good_id );

        return $data;

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
     * 获取公共的价格
     *
     * @param $product_id
     * @param string $where
     *
     * @return array|bool|false|\PDOStatement|string|Model
     */
    public function getCommonPrice( $product_id , $where = "" )
    {
        if( $where ) {

            $price = Db::table( 'fb_goods' )
                ->alias( 'a' )
                ->join( 'fb_website c' , 'c.website_abbreviation=a.orignal_website' )
                ->where( [ 'is_deleted' => 1 , 'goods_status' => 1 ] )
                ->where( 'product_id' , $product_id )->where( 'c.pay_way' , 'in' , $where )
                ->field( 'a.id,market_price,shop_price,c.pay_way,c.website_name,c.website_thumbnail,website_abbreviation' )
                ->order( 'shop_price' , 'asc' )
                ->limit( 1 )
                ->find();
        }
        else {

            $price = Db::table( 'fb_goods' )
                ->alias( 'a' )
                ->join( 'fb_website c' , 'c.website_abbreviation=a.orignal_website' )
                ->where( [ 'is_deleted' => 1 , 'goods_status' => 1 ] )
                ->where( 'product_id' , $product_id )
                ->field( 'a.id,market_price,shop_price,c.pay_way,c.website_name,c.website_thumbnail,website_abbreviation' )
                ->order( 'shop_price' , 'asc' )
                ->limit( 1 )
                ->find();
        }
        if( $price ) {
            return $price;
        }
        else {
            return false;
        }
    }

    /**
     * 获取其他价格
     *
     * @param $product_id
     * @param $goods_id
     *
     * @return bool|false|\PDOStatement|string|\think\Collection
     */
    public function getOtherPrice( $product_id , $goods_id )
    {
        $price = Db::table( 'fb_goods' )->alias( 'a' )
        ->join( 'fb_website c' , 'c.website_abbreviation=a.orignal_website' )
        ->where( [ 'is_deleted' => 1 , 'goods_status' => 1 ] )
        ->where( 'product_id' , $product_id )
        ->where( 'a.id' , 'not in' , $goods_id )
        ->field( 'a.id,shop_price,c.pay_way,c.website_name,c.website_thumbnail,c.website_abbreviation' )
        ->order( 'shop_price' , 'asc' )
        ->select();

        if( $price ) {
            foreach( $price as $k => $v ) {
                $price[ $k ][ 'shop_price' ] = '￥' . intval( $v[ 'shop_price' ] );
            }
            return $price;
        }
        else {
            return false;
        }
    }


    public function getProductDetailsPage( $product_id , $website_abbreviation )
    {

        $res = Db::table( 'fb_goods' )
            ->alias( 'a' )
            ->join( "fb_product b" , "a.product_id = b.id" )
            ->join( "fb_website c" , 'a.orignal_website=c.website_abbreviation' )
            ->join( 'fb_goods_attribute_mapping d' , 'a.id = d.goods_id' )
            ->join( 'fb_attribute_value_enum e' , 'd.attribute_value_id = e.id' )
            ->where( 'product_id' , $product_id )
            ->where( 'a.orignal_website' , $website_abbreviation )
            ->field( 'b.id,product_name,product_image,website_name,website_thumbnail,attribute_value,a.shop_price,original_product_id,a.original_goods_id' )
            ->find();

        //        var_dump($res);die;
        //        /** @var  $markets  获取参考价 */
        $res[ 'market_price' ] = $this->getProductPageMarketPrice( $product_id , $res[ 'original_product_id' ] );
        //
        //
        //        /** 获取折扣 */
        //        $res3 = getDiscountNumber( $res[ 'shop_price' ] , $res[ 'market_price' ] );
        //
        //        $res[ 'is_discount' ] = $res3[ 'is_discount' ];
        //        $res[ 'discount_num' ] = $res3[ 'discount_num' ];
        $this->getOtherMessage( $product_id , $res[ 'original_goods_id' ] );


        //        var_dump( $res );
        //        $res['is_discount'] =
    }


    public function getProductPageMarketPrice( $product_id , $original_product_id )
    {
        $markets = Db::table( 'fb_goods' )
            ->where( "product_id" , $product_id )
            ->where( 'original_goods_id' , $original_product_id )
            ->field( 'market_price' )
            ->find();
        return $markets[ 'market_price' ];
    }

    /** 获取落地页信息 */
    public function getOtherMessage( $product_id , $original_goods_id )
    {
        //查出所有的信息
        $priceEntry = Db::table( 'fb_goods' )
            ->alias( 'a' )
            ->join( 'fb_website b' , 'a.orignal_website=b.website_abbreviation' )
            ->where( 'product_id' , $product_id )
            ->where( 'original_goods_id' , $original_goods_id )
            ->field( 'tax_free_zone,orignal_website,original_price,pay_way,service,is_postage,postage_price,is_cross_border_tax_in,tax_free_zone,is_import_fee_in,import_fee,is_local_tax_in,local_tax_in_price,tax_refund_price' )
            ->find();


        $otherMessage = [];

        /** pay_way 1国内直邮 2海外直邮 3海淘直邮 4海淘转运  5免税店  6其他 */
        if( $priceEntry[ 'pay_way' ] == 1 ) {
            /** 国内直邮 */
            if( $priceEntry[ 'is_postage' ] == 1 ) {

                $otherMessage[ 'postage_price' ] = "免运费";
                /** 运费描述 */
                $otherMessage[ 'freight' ] = '免运费';

            }
            else {

                /** 运费 */
                $otherMessage[ 'postage_price' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
                /** 运费描述 */
                $otherMessage[ 'freight' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );

            }

            /** 含关税 */
            $otherMessage[ 'cross_border_tax' ] = "";

            /** 税费描述 */
            $otherMessage[ 'taxation' ] = "";
            /** 转运费描述 */
            $otherMessage[ 'transfer_fee' ] = "";

        }
        elseif( $priceEntry[ 'pay_way' ] == 2 ) {
            /** 海外直邮 */
            if( $priceEntry[ 'is_postage' ] == 1 && $priceEntry[ 'is_import_fee_in' ] == 1 ) {

                /** 运费 */
                $otherMessage[ 'postage_price' ] = '免运费';
                /** 运费描述 */
                $otherMessage[ 'freight' ] = '免运费';
                /** 含关税 */
                $otherMessage[ 'cross_border_tax' ] = '';
                /** 税费描述 */
                $otherMessage[ 'taxation' ] = "";

            }
            elseif( $priceEntry[ 'is_postage' ] == 2 && $priceEntry[ 'is_import_fee_in' ] == 1 ) {

                /** 运费 */
                $otherMessage[ 'postage_price' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
                /** 运费描述 */
                $otherMessage[ 'freight' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
                /** 含关税 */
                $otherMessage[ 'cross_border_tax' ] = '';
                /** 税费描述 */
                $otherMessage[ 'taxation' ] = "";

            }
            elseif( $priceEntry[ 'is_postage' ] == 1 && $priceEntry[ 'is_import_fee_in' ] == 0 ) {

                /** 运费 */
                $otherMessage[ 'postage_price' ] = '免运费';
                /** 运费描述 */
                $otherMessage[ 'freight' ] = '免运费';
                /** 含关税 */
                $otherMessage[ 'cross_border_tax' ] = $this->tariffHandle( $priceEntry[ 'import_fee' ] );
                /** 税费描述 */
                $otherMessage[ 'taxation' ] = $this->tariffHandle( $priceEntry[ 'import_fee' ] );

            }
            else {

                /** 运费 */
                $otherMessage[ 'postage_price' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
                /** 运费描述 */
                $otherMessage[ 'freight' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
                /** 含关税 */
                $otherMessage[ 'cross_border_tax' ] = $this->tariffHandle( $priceEntry[ 'import_fee' ] );
                /** 税费描述 */
                $otherMessage[ 'taxation' ] = $this->tariffHandle( $priceEntry[ 'import_fee' ] );

            }

            /** 转运费描述 */
            $otherMessage[ 'transfer_fee' ] = "";

        }
        elseif( $priceEntry[ 'pay_way' ] == 3 ) {
            /** 海淘直邮 */
            if( $priceEntry[ 'is_cross_border_tax_in' ] == 0 ) {
                /** 含关税 */
                $otherMessage[ 'cross_border_tax' ] = $this->tariffHandle( $priceEntry[ 'import_fee' ] );

                /** 税费描述 */
                $otherMessage[ 'taxation' ] = $this->tariffHandle( $priceEntry[ 'import_fee' ] );

            }
            else {

                /** 含关税 */
                $otherMessage[ 'cross_border_tax' ] = "";
                /** 税费描述 */
                $otherMessage[ 'taxation' ] = "";
            }

            if( $priceEntry[ 'is_postage' ] == 1 ) {
                /** 运费 */
                $otherMessage[ 'postage_price' ] = '免运费';
                /** 运费描述 */
                $otherMessage[ 'freight' ] = '免运费';
            }
            else {
                /** 运费 */
                $otherMessage[ 'postage_price' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
                /** 运费描述 */
                $otherMessage[ 'freight' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
            }


            /** 转运费描述 */
            $otherMessage[ 'transfer_fee' ] = "";

        }
        elseif( $priceEntry[ 'pay_way' ] == 4 ) {
            /** 海淘转运 */
            if( $priceEntry[ 'is_cross_border_tax_in' ] == 0 ) {
                /** 含关税 */
                $otherMessage[ 'cross_border_tax' ] = $this->tariffHandle( $priceEntry[ 'import_fee' ] );
                /** 税费描述 */
                $otherMessage[ 'taxation' ] = $this->tariffHandle( $priceEntry[ 'import_fee' ] );
            }
            else {

                /** 含关税 */
                $otherMessage[ 'cross_border_tax' ] = "";
                /** 税费描述 */
                $otherMessage[ 'taxation' ] = "";

            }

            if( $priceEntry[ 'is_postage' ] == 1 ) {
                /** 运费 */
                $otherMessage[ 'postage_price' ] = '免运费';
                /** 运费描述 */
                $otherMessage[ 'freight' ] = '免运费';
            }
            else {
                /** 运费 */
                $otherMessage[ 'postage_price' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
                /** 运费描述 */
                $otherMessage[ 'freight' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
            }


            /** 转运费 原价的10%*/
            $transport = intval( $priceEntry[ 'original_price' ] * 0.1 );

            /** 转运费描述 */
            $otherMessage[ 'transfer_fee' ] = '转运费' . $transport . '元';

        }
        elseif( $priceEntry[ 'pay_way' ] == 5 ) {
            /** 免税店 */
            if( $priceEntry[ 'is_local_tax_in' ] == 0 ) {
                /** 含关税 */
                $otherMessage[ 'cross_border_tax' ] = '不含税，退税价';
                /** 税费描述 */
                $otherMessage[ 'taxation' ] = '不含税，退税价';

            }
            else {

                /** 计算退税额 含税价-退税价*/
                $drawback = intval( $priceEntry[ 'local_tax_in_price' ] ) - intval( $priceEntry[ 'tax_refund_price' ] );

                if( $drawback == 0 ) {

                    /** 含关税 */
                    $otherMessage[ 'cross_border_tax' ] = '不含税，退税价';
                    /** 税费描述 */
                    $otherMessage[ 'taxation' ] = '不含税，退税价';

                }
                else {

                    /** 含关税 */
                    $otherMessage[ 'cross_border_tax' ] = '退税' . $drawback . '元';

                    /** 税费描述 */
                    $otherMessage[ 'taxation' ] = '退税' . $drawback . '元';

                }

            }

            if( $priceEntry[ 'is_postage' ] == 1 ) {
                /** 运费 */
                $otherMessage[ 'postage_price' ] = '免运费';
                /** 运费描述 */
                $otherMessage[ 'freight' ] = '免运费';
            }
            else {
                /** 运费 */
                $otherMessage[ 'postage_price' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
                /** 运费描述 */
                $otherMessage[ 'freight' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
            }

            /** 转运费描述 */
            $otherMessage[ 'transfer_fee' ] = "";

        }
        else {
            /** 其他 */
            if( $priceEntry[ 'is_postage' ] == 1 ) {

                $otherMessage[ 'postage_price' ] = "免运费";
                /** 运费描述 */
                $otherMessage[ 'freight' ] = '免运费';

            }
            else {

                /** 运费 */
                $otherMessage[ 'postage_price' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );
                /** 运费描述 */
                $otherMessage[ 'freight' ] = $this->postagePriceHandle( $priceEntry[ 'postage_price' ] );

            }

            /** 含关税 */
            $otherMessage[ 'cross_border_tax' ] = "";

            /** 税费描述 */
            $otherMessage[ 'taxation' ] = "";
            /** 转运费描述 */
            $otherMessage[ 'transfer_fee' ] = "";
        }

        /** 发货地 */
        $otherMessage[ 'place_of_delivery' ] = $priceEntry[ 'tax_free_zone' ];

        /** 服务描述 */
        $otherMessage[ 'service' ] = $priceEntry[ 'service' ];

        return $otherMessage;
    }

    /** 邮费处理 */
    public function postagePriceHandle( $postage_price )
    {
        if( $postage_price == 0 ) {
            return $postage_price = '免运费';
        }
        else {
            /** 运费 */
            return $postage_price = '运费' . $postage_price . '元';
        }
    }

    /** 关税处理 */
    public function tariffHandle( $import_fee )
    {
        if( $import_fee == 0 ) {
            return $import_fee = "";
        }
        else {
            return $import_fee = "含税费" . $import_fee . '元';
        }
    }

}