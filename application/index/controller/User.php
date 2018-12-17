<?php
namespace app\index\controller;

use app\index\model\Account;
use app\index\model\Red;
use think\Config;
use think\Db;
class User extends Common
{
    /**
     * 我的首页
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userIndex()
    {
        #获取用户的id
        $session_id = request()->post('session');

        $open_id=$this->getOpenId($session_id);

        $userModel = new \app\index\model\User();

        $user_id=$userModel->getUserId($open_id);

        $md_res=$open_id.$user_id;

        $only_num=substr(md5($md_res),0,15);

        $userModel->addUserOnlyNum($user_id,$only_num);

        #查出用户的基本信息
        $user_info = $userModel->getUserInfo($user_id);

        #查出用户红包钱
        $redModel = new Red();

        $red_money= $redModel->getUserRedSum($user_id);

        $bonus_max_num = Config::get('api.bonus_max_num');

        #根据用户的id查出红包的记录
        $surplus_money = $bonus_max_num - $red_money;

        if($user_info){

            $user_info['red_money'] = number_format($red_money,2);

            $red_data=$this->getConfig();

            $red_data['own']['surplus_money']=number_format($surplus_money,2);

            $user_info['red_data'] =$red_data['own'];

            $user_info['share'] =$red_data['share'];

            $user_info['share']['share_only'] =$only_num;

            //获取用户是否邀请好友成功
            $info = Db::table('fb_user')
                ->where(['parent_id' => $user_id])
                ->find();

            if(empty($info)){
                $user_info['is_have'] = 1;
            }else{
                $user_info['is_have'] = 0;
            }

            return $this ->successCode($user_info,'成功',1);

        }else{

            return $this -> errorCode('参数有误','',1000);
        }

    }

    /**
     * 获取配置项我的页面的数据
     * @return mixed
     */
    protected function getConfig(){

        $own_config = Config::get('api.own_config');
        $frontend_img = Config::get('api.frontend_img');
        $heard_img=$own_config['head_img'];
        $data['own']['own_one']=$own_config['own_one'];
        $data['own']['own_two']=$own_config['own_two'];
        $data['own']['own_three']=$own_config['own_three'];
        $data['own']['head_img'] = $frontend_img.$heard_img;
        $data['share']['share_title']=$own_config['share_title'];
        $data['share']['share_img']=$frontend_img.$own_config['share_img'];

        return $data;
    }


    /**
     * 获取红包详情接口
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userBonusInfo(){

        #获取用户的id
        $session = request()->post('session');

        $open_id=$this->getOpenId($session);

        $userModel = new \app\index\model\User();

        $user_id=$userModel->getUserId($open_id);

        $user_info = $userModel->getUserInfo($user_id);

        #获取该用户所有的红包数
        $red_model = new Red();

        $red_sum=$red_model->getUserRedSum($user_id);

        $user_info['red_num']=$red_sum;

        #获取用户已经提现的红包数
        $account_model = new Account();

        $account_sum=$account_model->getUserAccountSum($user_id);

        if($red_sum-$account_sum<=0){

            $sum_num=0;

        }else{

            $sum_num=$red_sum-$account_sum;
        }

        $user_info['cash_num']=$sum_num;
        $user_id = 36;
        $red_data=$red_model->getUserRed($user_id);

        $data['user_info']=$user_info;

        if($red_data){

            $data['red_data']=$red_data;

        }else{

            $data['red_data']='';

        }

        return $this ->successCode($data,'成功',1);

    }

    /**
     * 分享接口
     * 用户的唯一标识
     */
    public function userShare(){

        
	$session_id = request()->post('session');

        $open_id=$this->getOpenId($session_id);

        $userModel = new \app\index\model\User();

        $user_id=$userModel->getUserId($open_id);

        $share_type = request()->post('share_type','');

        $share_id = request()->post('id');

        $now = date("Y-m-d H:i:s" ,time());

        $insertData = [
            'user_id' => $user_id,
            'share_type' => $share_type,
            'id_value'=> $share_id,
            'created_at' => $now,
        ];

        //根据user_id 插入 share 表中
        $res=Db::table('fb_share')->insert($insertData);
        if($res){

          $this->successCode('','分享成功',1);

        }else{

            $this->errorCode('分享失败','',1000);
        }

    }


    /*
     * 用户提现接口
     */
    public function userCash(){
//        $a=getcwd();
        $open_id='o-hXq0MCvAQG-BrLpbggYQURHLz0';
        $cash_num='0.01';
        $a=$this->weixin_pay_person($open_id,$cash_num);
        var_dump($a);
        die;
        #获取用户的id
        $session_id = request()->post('session');

        $open_id=$this->getOpenId($session_id);

        $userModel = new \app\index\model\User();

        $user_id=$userModel->getUserId($open_id);

        #接受提现的金额
        $cash_num= request()->post('cash_num');

        #判断用户提现的金额是否超过可提现红包金额
        $red_model = new Red();
        #红包的金额
        $user_red_num=$red_model->getUserRedSum($user_id);

        $account = new Account();

        #已经提现的金额
        $cashed_num=$account->getUserAccountSum($user_id);

        #可提现的金额
        $difference_num = $user_red_num - $cashed_num;

        if($difference_num<$cash_num){

            $this->successCode($difference_num,'你最多可提现'.$difference_num.'元','1001');

        }else{
            #给流水记录表里添加数据

            $res=$this->weixin_pay_person($open_id,$cash_num);

            if($res){

            }

        }
    }




    /*
    * 企业付款到零钱
    **/
    public function weixin_pay_person($re_openid,$cash_num)
    {
        // 请求参数
        $data['mch_appid'] ='wxe7a9a545c82bce8b' ;//商户号appid
        $data['mchid'] = 1505675491;//商户账号
        $data['nonce_str'] = $this->get_unique_value();// 随机字符串
        //商户订单号，可以按要求自己组合28位的商户订单号
        $data['partner_trade_no'] = $this->get_tradeno($data['mchid']);
//        var_dump($data['partner_trade_no']);die;
        $data['openid'] = $re_openid;//用户openid
        $data['check_name'] = 'NO_CHECK';//校验用户姓名选项
        $data['amount'] = $cash_num*100;//金额,单位为分
        $data['desc'] = "恭喜你得到一个红包";//企业付款描述信息
        $data['spbill_create_ip'] = '154.8.174.77';//IP地址

        $appsecret = 'i571ln3Ew76bwJyvUKyf0IB2KCqhilGo';

        $data['sign'] = $this->sign($data, $appsecret);
        //接口地址
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";

        //将请求数据由数组转换成xml
        $xml = $this->arraytoxml($data);
        //进行请求操作
        $res = $this->curl($xml, $url);
        //将请求结果由xml转换成数组
        $arr = $this->xmltoarray($res);

        if (is_array($arr)) {
            $arr['total_amount'] = $data['amount'];
        }
        //请请求信息和请求结果录入到数据库中

        // 输出请求结果数组
        return $arr;
    }

    public function create_rand_money($start = 30, $end = 100)
    {
        return mt_rand($start, $end);
    }

    public function sign($params, $appsecret)
    {
        ksort($params);
        $beSign = array_filter($params, 'strlen');
        $pairs = array();
        foreach ($beSign as $k => $v) {
            $pairs[] = "$k=$v";
        }

        $sign_data = implode('&', $pairs);
        $sign_data .= '&key=' . $appsecret;
        return strtoupper(md5($sign_data));
    }

    /*
     * 生成32位唯一随机字符串
     **/
    private function get_unique_value()
    {
        $str = uniqid(mt_rand(), 1);
        $str = sha1($str);
        return md5($str);
    }

    /*
     * 将数组转换成xml
     **/
    private function arraytoxml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $k => $v) {
            $xml .= "<" . $k . ">" . $v . "</" . $k . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    /*
     * 将xml转换成数组
     **/
    private function xmltoarray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $arr = json_decode(json_encode($xmlstring), true);
        return $arr;
    }

    /*
     * 进行curl操作
     **/
    private function curl($param = "", $url) {
        $postUrl = $url;
        $curlPost = $param;
        //初始化curl
        $ch = curl_init();
        //抓取指定网页
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, 1);
        // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //这个是证书的位置
        curl_setopt($ch, CURLOPT_SSLCERT, __DIR__ . '/cert/apiclient_cert.pem');
        //这个也是证书的位置
        curl_setopt($ch, CURLOPT_SSLKEY, __DIR__ . '/cert/apiclient_key.pem');
        //运行curl
        $data = curl_exec($ch);
        //关闭curl
        curl_close($ch);

        return $data;

    }

    public function get_tradeno($str)
    {

        return $str . date("Ymd", time()) . date("His", time()) . rand(1111, 9999);
    }



}
