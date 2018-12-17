<?php
namespace app\index\controller;

use app\index\model\Red;
use app\index\model\Strategy;
use think\Config;
use think\Db;

class Login
{
    //获取sessionKey 返回授权头像
    public function sessionKey(){

        $code = $_REQUEST['code'];

        $appId = "wxe7a9a545c82bce8b";
        $app_secret = "ce54a4bfa4a4993cb2b47ca340f9c4ee";

        $api = "https://api.weixin.qq.com/sns/jscode2session?appid=$appId&secret=$app_secret&js_code=$code&grant_type=authorization_code";

        $result = httpGet($api);

        $result = json_decode($result, true);

        if (!empty($result['openid'])) {

            session_start();

            $session_id = session_id();

            $info = $result['session_key'] . $result['openid'];

            $session_key = $result['session_key'];

            $_SESSION['session'] = $info;

            $_SESSION['session_key'] = $session_key;

            $res['rd_session'] = $session_id;

            $user_model = new \app\index\model\User();

            $user_id = $user_model ->getUserId($result['openid']);

            if(empty($user_id)){
                $res['is_login'] = 0;
            }else{
                $res['is_login'] = 1;
            }
            //授权头像
            $img = Config::get('api.frontend_img');

            $data = Config::get("api.own_config");

            $res['share_img'] = $img.$data['share_img'];

        } else {
            $res['errcode'] = 1;
            $res['errmsg'] = 'no openid';
        }

        $json_info = [
            'status_code' => 1,
            'message' => '',
            'data'=>$res
        ];
        return   $json_info;
    }


    public function loginDo()
    {
        $userInfo = request()->post('userinfo');

        $userInfo = json_decode($userInfo, true);

        $session = request()->post('session');

        $flag = request() ->post('share_only','');

        $open_id = $this->GetOpenId($session);

//	file_put_contents(__DIR__ . '/tuling.log', print_r($session, true).PHP_EOL , FILE_APPEND);
//	file_put_contents(__DIR__ . '/tuling.log', print_r($flag, true).PHP_EOL , FILE_APPEND);

        $result = Db::name('fb_user')->where("user_open_id = '$open_id'")->find();

        if ($result) {

//            $this->getUserResEnvelopesInfo($result['id']);

            $json_info = [
                'status_code' => 1,
                'message' => '成功',
                'data' =>''
            ];
            
            return   $json_info;

        } else {

            $time = date('Y-m-d H:i:s',time());

            if(empty($flag)){

                $insertData = [
                    'user_wechat_nickname' => $userInfo['nickName'],
                    'user_define_nickname' => $userInfo['nickName'],
                    'user_avatar' => $userInfo['avatarUrl'],
                    'user_login_time' => $time,
                    'user_genter' => $userInfo['gender'],
                    'user_status' => 1,
                    'user_open_id' => $open_id,
                    'created_at' => date("Y-m-d H:i:s",time())
                ];
                $res = Db::name('fb_user')->insert($insertData);
                $id  = Db::name('fb_user')->getLastInsID();

            }else{

                $select_res = Db::table('fb_user')
                    ->where(['user_only_num' => $flag])
                    ->field('id,user_open_id')
                    ->find();

                $insertData = [
                    'user_wechat_nickname' => $userInfo['nickName'],
                    'user_define_nickname' => $userInfo['nickName'],
                    'user_avatar' => $userInfo['avatarUrl'],
                    'user_login_time' => $time,
                    'user_genter' => $userInfo['gender'],
                    'user_status' => 1,
                    'user_open_id' => $open_id,
                    'parent_id' => $select_res['id'],
                    'created_at' => date("Y-m-d H:i:s",time())
                ];

                $res = Db::name('fb_user')->insert($insertData);
                $id  = Db::name('fb_user')->getLastInsID();

                if($result['id']!=$id){
                    #需要给邀请用户发红包
                    $red_model = new Red();
                    $red_model->addUserRed($select_res['id'],$id);
                }
            }


            if ($res) {

//                $this->getUserResEnvelopesInfo($id);

                $json_info = [
                    'status_code' => 1,
                    'message' => '成功',
                    'data' =>''
                ];
		        return $json_info;

            } else {

                $json_info = [
                    'status_code' => 1000,
                    'message' => '失败',
                    'data' =>''
                ];
		return $json_info;
            }
        }
    }

    /**
     * 添加用户的唯一标识
     * @param $id
     * @param $open_id
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function addUserOnlyNum($id,$open_id){
        $user_only_num = substr(md5($open_id.$id),0,15);
        Db::table('fb_user')
            ->where(['id' => $id])
            ->update(['user_only_num' => $user_only_num]);
    }

    /**
     * 获取用户的openID
     * @param $session_id
     * @return bool|string
     */
    public function GetOpenId($session_id)
    {
        session_id($session_id);

        session_start();

        $session_value = $_SESSION['session'];

        $session_key_value = $_SESSION['session_key'];

        $count = strlen($session_key_value);

        $open_id = substr($session_value, $count);

        return $open_id;
    }

    public function sendNotice($openId,$share_user_name){

        $token = $this ->getToken();

        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$token;

         $param = '{
              "offset":0,
              "count":10
         }';
         $res = $this -> httpPost($url ,$param,1);

         var_dump($res);
    }
    public function getToken(){
        $appId = "wxe7a9a545c82bce8b";
        $app_secret = "ce54a4bfa4a4993cb2b47ca340f9c4ee";

        $token_save_path = __DIR__.'/token.txt';

        #判断token文件是否存在，不存在吊接口，存在但是超过7200s重新调用接口，7100防止token过期
        if( !file_exists( $token_save_path ) || time() - filemtime($token_save_path) > 7100 ){
            #接口调用请求
            $get_token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$app_secret";

            #token有效期2小时
            $token_data = $this->httpPost( $get_token_url , '' , 0 );

            if( isset( $token_data['errcode'] ) ){
                echo 'get token fail';
                exit;
            }


            $token = $token_data['access_token'];

            #把token写入文件
            file_put_contents($token_save_path , $token);
        }else{
            $token = file_get_contents($token_save_path);
        }

        return $token;
    }

    //curl POST请求获取token
    function httpPost($url, $param = null, $is_post = 1 ,$timeout = 10){
        //初始化curl
        $curl = curl_init();

        if( $is_post == 1 ){
            curl_setopt($curl, CURLOPT_POST , 0);
        }

        curl_setopt($curl, CURLOPT_URL, $url); // 设置请求的路径
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //显示输出结果 1 代表 把结果转化为字符串进行处理
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);//设置请求超时时间
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST , false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER , false);

        if( $is_post == 1 ){
            //提交数据
            if (is_array($param)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($param));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
            }
        }

        //执行请求
        $data = $data_str = curl_exec($curl);

        //处理错误
        if ($error = curl_error($curl)) {
            $log_data = array(
                'url' => $url,
                'param' => $param,
                'error' => '<span style="color:red;font-weight: bold">' . $error . '</span>',
            );

            var_dump($log_data);
            exit;
        }

        # 关闭CURL
        curl_close($curl);

        //json数据转换为数组
        $data = json_decode($data, true);

        if (!is_array($data)) {
            $data = $data_str;
        }
        //调用玩接口写日志
        $log = array(
            'url' => $url,
            'param' => $param,
            'response' => $data_str
        );
        file_put_contents(__DIR__.'/wechat.log' , print_r( $log , true ) , 8);

        return $data;
    }
}
