<?php
namespace app\index\controller;

use app\index\model\Red;
use app\index\model\Category;
use app\index\model\Strategy;
use app\index\model\Product;
use think\Config;
use think\Db;
class Index extends Common
{
    /**
     * 首页接口
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {

	    $session = request()->post('session','');

        $strategy_info = $this->getStrategyInfo();

        $brand_info = $this->getBrandInfo();

        $category_info = $this ->getCategoryInfo();

        $hot_info = $this ->getHotInfo();

        $strategy_model = new Strategy();

        $strategy = $strategy_model->getIndexStrategy();

	    //获取用户是否点击过复制
        $brand_info = $this ->getUserRed($session,$brand_info);

        if( !empty($hot_info) && !empty($strategy) ){
            $strategy['is_product'] = 2;
            array_push($hot_info, $strategy);
        }else{
            $hot_info = [];
        }
        $json_data = [
            'strategy_info'=>$strategy_info,
            'brand_info' => $brand_info,
            'category_info' => $category_info,
            'hot_info' => $hot_info
        ];

       return $this->successCode($json_data,'',1);
    }


    //攻略轮播图
    public function getStrategyInfo()
    {
        $strategyModel = new Strategy();

        return  $strategyModel ->getSlider();
    }

    //品牌比价、购物攻略、现金红包
    public function getBrandInfo()
    {
        $brand_info = Config::get('api.brand_message');

        foreach ($brand_info as $k => $v){
            $brand_info[$k]['brand_img'] = getImage($v['brand_img']);
        }

        return $brand_info;
    }


    /**
     * 获取分类名
     * @return array
     */
    public function getCategoryInfo()
    {
        $category_model = new Category();

        $category_name = Config::get('api.category_name');

        return $category_model->getIndexCategoryInfo($category_name);
    }


    //获取用户是否复制
    public function getUserRed($session,$brand_info){

        if (!empty($session)) {
            //获取用户的user_id
            $open_id = $this ->getOpenId($session);

            $user_model = new \app\index\model\User();

            $user_id = $user_model -> getUserId($open_id);

            if($user_id != false && !empty($user_id)){

                $redPackage = $this->getUserRedStatus($user_id);

                foreach ($brand_info as $k => $v) {
                    if ($v['brand_name'] == '现金红包') {
                        if ($redPackage['is_copy'] == 1) {
                            $brand_info[$k]['is_have'] = 1;
                        } else {
                            $brand_info[$k]['is_have'] = 0;
                        }
                    }
                }

            }else{
                foreach ($brand_info as $k => $v) {
                    if ($v['brand_name'] == '现金红包') {

                        $brand_info[$k]['is_have'] = 1;

                    }
                }
            }
//            $redPackage = $this->getUserRedStatus($user_id);
//
//            if (!empty($redPackage)) {
//                foreach ($brand_info as $k => $v) {
//                    if ($v['brand_name'] == '现金红包') {
//                        if ($redPackage['is_copy'] == 1) {
//                            $brand_info[$k]['is_have'] = 1;
//                        } else {
//                            $brand_info[$k]['is_have'] = 0;
//                        }
//                    }
//                }
//            }
//        } else {
//            foreach ($brand_info as $k => $v) {
//                if ($v['brand_name'] == '现金红包') {
//                    $brand_info[$k]['is_have'] = 1;
//                }
//            }
        }

        return $brand_info;
    }


    /**
     * 获取首页热卖商品
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHotInfo()
    {
        $product_model = new Product();

        $hot_product = $product_model -> getIndexCategoryProduct(0,'');

        return $hot_product;
    }

    /**
     * 首页分类的tab选项卡
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productInfo()
    {
        $category_id = request()->post("category_id",'');

        $p = request()->post("p",'');

        if($category_id == ""){
            $this->errorCode('参数有误','',1000);
        }else{
            $product_model = new Product();

            $product_info = $product_model -> getIndexCategoryProduct($category_id,$p);

            if(!empty($product_info)){
                $strategy_model = new Strategy();
                $strategy_info = $strategy_model -> getIndexTabStrategyInfo($category_id,$p);

                if(!empty($product_info)&&!empty($strategy_info) ){
                    $strategy_info['is_product'] = 2;
                    array_push($product_info, $strategy_info);
                }
            }else{
                $product_info = [];
            }

            if(!empty($product_info)){
               return  $this -> successCode($product_info,'成功',1);
            }else{
               return  $this ->errorCode('没有更多数据了','',1000);
            }
        }
    }

   /**
     * 现金红包
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashRedEnvelope(){
        $session = request()->post('session');

        if(empty($session)){
            return $this -> errorCode('参数有误','' ,1000);
        }

        //获取用户的$open_id
        $open_id = $this ->getOpenId($session);
        $user_model = new \app\index\model\User();

        $user_id = $user_model -> getUserId($open_id);

//        $user_id = 6;
        if($user_id != false && !empty($user_id)){
            $this->getUserRedStatus($user_id);
        }


        $img = Config::get('api.frontend_img');
        $data = Config::get('api.own_config');
        $data['head_img']=$img.$data['index_img'];
        $data['vipServerTag']=$img.$data['vipServerTag'];
        $data['moneyBg']=$img.$data['moneyBg'];
        $data['moneyBgCopy']=$img.$data['moneyBgCopy'];
        $data['getMoney']=$img.$data['getMoney'];
        $data['wechat_num']=$data['wechat_num'];
        $data['wechat']=$data['wechat'];
        $data['wechat_img']=$img.$data['wechat_img'];
        $data['wechat_cha_img']=$img.$data['wechat_cha_img'];
        $data['wechat_qian_img']=$img.$data['wechat_qian_img'];
        $data['wechat_hou_img']=$img.$data['wechat_hou_img'];


        return $this->successCode($data, '',1);

    }

    /**
     * 获取用户的红包状态
     * @param $user_id
     * @return array|bool|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserRedStatus($user_id){

        $red_data = Db::table('fb_bonus')
                ->where(['user_id' => $user_id])
                ->field('is_copy,red_envelopes_money')
                ->find();

        if(!empty($red_data)){

                return $red_data;

        }else{
            $now = date("Y-m-d H:i:s" ,time());

            $insertData = [
                'user_id' => $user_id,
                'is_copy' => 1,
                'red_envelopes_status'=> 1,
                'created_at' => $now
            ];
            Db::name('fb_bonus')->insert($insertData);
            return false;
        }
    }

    /**
     * 首页现金红包一键复制
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userCopy(){
        $session = request()->post('session');

//        获取用户的user_id
        $open_id = $this ->getOpenId($session);

        $user_model = new \app\index\model\User();

        $user_id = $user_model -> getUserId($open_id);

        if($user_id){

            $sql="update fb_bonus set is_copy = 0  WHERE user_id = $user_id";

            $save = Db::query($sql);

            return $this->success(1, '','');

        }else{

            return $this->error(1000, '','');
        }

        //if($save){
//            return $this->success(1, '','');
        //}else{
        //    return $this->error(1000, '','');
        //}
    }

}
