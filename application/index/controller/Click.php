<?php
namespace app\index\controller;

use app\index\model\User;
class Click extends Common
{
    public function clickThumb()
    {
        $id_value = request() -> post('id_value');
        $click_thumbs_type = request() -> post('click_thumbs_type');
        $session = request() -> post('session');

        if(empty($id_value)){
            return $this -> errorCode('参数有误','' ,1000);
        }

        if(empty($click_thumbs_type)){
            return $this -> errorCode('参数有误','' ,1000);
        }

        if(empty($session)){
            return $this -> errorCode('参数有误','' ,1000);
        }

        //获取用户的user_id
        $open_id = $this ->getOpenId($session);

        $user_model = new User();

        $user_id = $user_model -> getUserId($open_id);


        $comment_model = new \app\index\model\Click();

        $res = $comment_model -> ClickThumbs($id_value,$click_thumbs_type,$user_id);
        if($res){
            return $this -> successCode('','点赞成功',1);
        }else{
            return $this -> successCode('','取赞成功',1);
        }
    }
}
