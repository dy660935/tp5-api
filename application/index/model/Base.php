<?php
namespace app\index\model;

use think\Config;
use think\Model;
use think\Db;
class Base extends Model
{
    /**
     * 获取商品详细信息sku
     * @param $product_id
     * @param $original_product_id
     *
     * @return array
     */
    public function getProductDetail($product_id,$original_product_id){
        $goods = [];
        $goods_info = [];

        //查询最低价sku

        $sql = "select 
            website_name,
            attribute_value,
            shop_price,
            category_id,
            a.original_goods_id
          from fb_goods as a 
          left join fb_goods_attribute_mapping as b on a.id = b.goods_id 
          left join fb_attribute_value_enum as c on b.attribute_value_id = c.id 
          left JOIN fb_website AS d ON a.orignal_website = d.website_abbreviation
          left JOIN fb_product AS e ON a.product_id = e.id
          where goods_status = 1 
          and a.product_id = $product_id 
          order by a.shop_price asc 
          limit 1";

        $goods[] = Db::query($sql);
        //sku转换二维数组
        foreach ($goods as $g_k => $g_v){
            foreach ($g_v as $gg_k => $gg_v){
                $goods_info[] = $gg_v;
            }
        }

        //查询kaola参考价和分类id
        foreach ($goods_info as $k => $v){
            $market[] = Db::table('fb_goods')
                ->alias('a')
                ->join('fb_product b','a.product_id=b.id')
                ->where('a.goods_status' ,1)
                ->where('a.product_id', $product_id)
                ->where('original_goods_id' , $original_product_id)
                ->field('a.product_id,a.market_price,b.category_id')
                ->limit(1)
                ->find();

                foreach ($market as $m_k => $m_v){
                    if(!isset($m_v['market_price'])){

                        $goods_info[$k]['market_price'] = '';

                    }else{

                        $goods_info[$k]['market_price'] = $m_v['market_price'];

                    }
                }


        }
        //增加是否为商品字段、商品是否打折字段、打折数
        foreach ($goods_info as $k => $v){

            $goods_info[$k]['is_product'] = 1;
//            $goods_info[$k]['product_name'] = $v['product_name'].' '.$v['attribute_value'];

            if(empty($v['market_price'])){

                $goods_info[$k]['is_discount'] = 0;
                $goods_info[$k]['discount_num'] = 10;

            }else{
                $dis = getDiscountNumber($v['shop_price'],$v['market_price']);
                $goods_info[$k]['is_discount'] = $dis['is_discount'];
                $goods_info[$k]['discount_num'] = $dis['discount_num'];

            }
            if(!empty($v['market_price']) && $v['market_price']<9999){
                $goods_info[$k]['market_price'] = '￥'.intval($v['market_price']);
            }
            $goods_info[$k]['shop_price'] = '￥'.intval($v['shop_price']);
        }

        return $goods_info;
    }


    //处理数组
    public function getNewProductInfo($product_info){
        foreach ($product_info as $k => $v){
            $product_info[$k]['product_name'] = $v['product_name'].' '.$v['attribute_value'];
        }
        return $product_info;
    }

    /**
     * 获取偏移量
     * @param string $p
     * @return float|int
     */
    public function getLimit($p = "")
    {
        //每页要显示总条数
        $p_num = Config::get('api.p_num');

        //商品每页要查的条数
        $product_num = $p_num - 1;

        if (empty($p)) {
            $limit = 0;
        } elseif ($p % $p_num != 0) {
            $limit = (((($p + $p_num) - ($p % $p_num)) / $p_num)) * $product_num;
            if ($limit <= $p) {
                $limit = (((($p + $p_num) - ($p % $p_num)) / $p_num) + 1) * $product_num;
            }
        } else {

            $limit = (($p + $p_num) / $p_num - 1) * $product_num;
        }

        return $limit;
    }
}