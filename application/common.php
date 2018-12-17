<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

//curl GET请求 获得用户openid
function httpGet($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_URL, $url);
    $res = curl_exec($curl);
    return $res;
}

//获取图片完整路径
function getImage($imageName){

    return \think\Config::get('api.frontend_img').$imageName;

}

/**
 * 获取折扣
 * @param $shop_price
 * @param $market_price
 * @return mixed
 */
function getDiscountNumber($shop_price,$market_price){

    $str = (intval($shop_price) / intval($market_price)) * 10;
    $discount = substr($str,0,strpos($str,'.')+2);
    if($discount > 8){
        $dis['is_discount'] = 0;
        $dis['discount_num'] = $discount;
    }else{
        $dis['is_discount'] = 1;
        $dis['discount_num'] = $discount;
    }
    return $dis;
}