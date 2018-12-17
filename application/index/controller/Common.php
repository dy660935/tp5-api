<?php
namespace app\index\controller;

use think\Controller;

use app\index\model\User;
use think\Db;
use lib\SphinxClient;
use think\Config;

class Common extends Controller
{
    /**
     * 提示错误的方法
     * @param string $msg
     * @param array $data
     * @param int $status
     * @return array
     */
    public function errorCode($msg='fail',$data=[],$status=1)
    {
        $arr=[
            'status_code'=>$status,
            'message'=>$msg,
            'data'=>$data
        ];
        return $arr;
       
    }


    /**
     * 提示成功的方法
     * @param array $data
     * @param string $msg
     * @param int $status
     * @return array
     */
    public function successCode($data=[],$msg='',$status=1000)
    {
        $arr= [
            'status_code'=>$status,
            'message'=>$msg,
            'data'=>$data
        ];
        return  $arr;
    }

    /**
     * 获取用户的open_id
     * @param $session_id
     * @return bool|string
     */
    public function getOpenId($session_id)
    {
        session_id($session_id);

        session_start();

        $session_value = $_SESSION['session'];

        $session_key_value = $_SESSION['session_key'];

        $count = strlen($session_key_value);

        $open_id = substr($session_value, $count);

        return $open_id;
    }

    /**
     * 获取userId
     * @param $session
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserId($session)
    {
        //获取用户id
        $open_id = $this -> getOpenId($session);

        $user_model = new User();

        $user_id = $user_model -> getUserId($open_id);

        return $user_id;
    }

    /**
     * 搜索主页
     */
    public function searchIndex()
    {
        //热门搜索品牌
//        $sql = "SELECT id,brand_chinese_name FROM fb_brand WHERE brand_status = 1 ORDER BY  RAND() LIMIT
//        10 ";
        $sql = "SELECT id,brand_chinese_name FROM fb_brand WHERE brand_status = 1 ORDER BY  brand_weight desc LIMIT
        10 ";

        $hot_search_brand = Db::query($sql);

        return $this -> successCode($hot_search_brand,'success',1);

    }

    /**
     * 搜索结果页接口
     */
    public function searchResult(){
        $search_name = request()->post('search_name');
        if(empty($search_name)){
            return $this -> errorCode('请输入关键字','',1000);
        }else{
            return $this -> getSearchInfo($search_name);
        }
    }

    private function _sphinxBrand($keyword) {
        $cl = new SphinxClient ();
        //$cl->SetServer ( '127.0.0.1', 9312);
        $cl->SetServer ( Config::get('sphinx.host'), 9312);
        $cl->SetConnectTimeout ( 3 );
        $cl->SetArrayResult ( true );
        $cl->SetFilter('brand_status',[1]);
        $cl->SetMatchMode ( SPH_MATCH_ALL);
        //$cl->SetSortMode(SPH_SORT_EXTENDED,' shop_price asc, @id desc ');
        //$cl->SetLimits(0,10);
        //$cl->setLimits(0,300);
        $res = $cl->Query ( $keyword, "sphinx_brand" );
        $brandIdAry = [];
        if(isset($res['matches']) && $res['matches']) {
            foreach($res['matches'] as $k => $v) {
                $brandIdAry[] = ['id' => $v['id']];
            }
        }
        return $brandIdAry;
    }

    private function _sphinxProduct($keyword) {
        $cl = new SphinxClient ();
        //$cl->SetServer ( '127.0.0.1', 9312);
        $cl->SetServer ( Config::get('sphinx.host'), 9312);
        $cl->SetConnectTimeout ( 3 );
        $cl->SetArrayResult ( true );
        $cl->SetFilter('product_status',[1]);
        $cl->SetFilter('is_deleted',[1]);
        $cl->SetMatchMode ( SPH_MATCH_ALL);
        $cl->SetSortMode(SPH_SORT_EXTENDED,' product_weight desc, @id desc ');
        //$cl->SetSortMode(SPH_SORT_ATTR_DESC,' product_weight desc, @id desc ');
        $cl->SetLimits(0,20);
        $res = $cl->Query ( $keyword, "sphinx_product" );
        $productIdAry = [];
        if(isset($res['matches']) && $res['matches']) {
            foreach($res['matches'] as $k => $v) {
                $productIdAry[] = ['id' => $v['id'],'product_name'=>$v['attrs']['product_name']];
            }
        }
        return $productIdAry;
    }

    /**
     * 搜索结果页接口
     * @param $search_name
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSearchInfo($search_name){
        $where = 1;
        if(Config::get('sphinx.enable')) {
            $brand_info = $this->_sphinxBrand($search_name);
        } else {
            $where .= " and brand_chinese_name like '%$search_name%' or brand_english_name like '%$search_name%'";

            $brand_info = Db::table('fb_brand')
                ->where($where)
                ->field('id')
                ->select();
        }
        $product_model = new \app\index\model\Product();
        if (empty($brand_info)) {
            if(Config::get('sphinx.enable')) {
                $product_info = $this->_sphinxProduct($search_name);
            } else {
                $product_info = Db::table('fb_product')
                ->where('product_name', 'like', "%".$search_name."%")
                ->where('product_status',1)
                ->field('id,product_name,product_image')
                ->order('product_weight','desc')
                ->page(1,20)
                ->select();
            }
            $product_info = $product_model ->getSku($product_info);
            if(!empty($product_info)){
                foreach ($product_info as $k => $v){
                    $product_info[$k]['shop_price_website_name'] = $v['website_name'];
                    $product_info[$k]['market_price_website_name'] = '网易考拉自营';
                    unset($product_info[$k]['website_name']);
                }
                $product_info = $product_model ->getNewProductInfo($product_info);
            }

        } else {
            $str = "";
            foreach ($brand_info as $k => $v) {

                $str .= $v['id'] . ',';
            }

            $str = trim($str, ',');

            $product_info = Db::table('fb_product')
                ->where('brand_id','in',$str)
                ->where(['product_status'=>1])
                ->where(['is_deleted'=>1])
                ->field('id,product_name,product_image')
                ->order('product_weight','desc')
                ->page(1,20)
                ->select();

            $product_info = $product_model ->getSku($product_info);

            if(!empty($product_info)){
                foreach ($product_info as $k => $v){
                    $product_info[$k]['shop_price_website_name'] = $v['website_name'];
                    $product_info[$k]['market_price_website_name'] = '网易考拉自营';
                    unset($product_info[$k]['website_name']);
                }

                $product_info = $product_model ->getNewProductInfo($product_info);
            }
        }

        if(empty($product_info)){

            return $this -> errorCode('暂无数据',[],1000);

        }else{

            return $this ->successCode($product_info,'成功',1);

        }
    }


    /**
     * 搜索申请收录接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function applyRecord()
    {
        $search_name = request()->post('search_name');

        if(empty($search_name)){

            return $this ->errorCode('参数有误','',1000);

        }else{

            $res = Db::table('fb_apply_record')
                ->where(['record_name' => $search_name])
                ->find();

            if($res){

                return $this ->successCode('','申请成功',1);

            }else{

                $result = Db::table('fb_apply_record')
                    ->insert([
                        'record_name' => $search_name,
                        'created_at' => date("Y-m-d H:i:s",time())
                    ]);

                if($result){

                    return $this ->successCode('','申请成功',1);

                }else{

                    return $this ->errorCode('服务器内部错误','',1000);

                }
            }
        }
    }


    /**
     * 图片兼容
     *
     * @param string $imageUrl
     *
     * @return string
     */
    public function imageCompatible ( $imageUrl )
    {
        if ( empty( $imageUrl ) ) {

            return $imageUrl;

        }
        else {
            $num = substr_count( $imageUrl , 'http' );

            if ( $num ) {

                $newImageUrl = $imageUrl;

            }
            else {

                $domain = Config::get( 'imgDomain' );

                $newImageUrl = $domain . $imageUrl;

            }
            return $newImageUrl;
        }
    }

}
