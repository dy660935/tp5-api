<?php
namespace app\index\controller;


class Comment extends Common
{
    //评论增加
    public function commentAdd(){

        $comment_type = request() -> post('comment_type');
        $session = request() -> post('session');
        $id_value = request() -> post('id_value');
        $parent_id = request() -> post('parent_id');
        $comment_describle = request() -> post('comment_describle');

        if(empty($session)){
            return $this ->errorCode('参数有误','',1000);
        }

        if(empty($id_value)){
            return $this ->errorCode('评论id参数有误','',1000);
        }

        if(empty($comment_type)){
            return $this -> errorCode('评论类型有误','',1000);
        }

        if(empty($parent_id)){
            $parent_id = 0;
        }

        if(empty($comment_describle)){
            return $this -> errorCode('评论内容非空','',1000);
        }

        //获取用户的user_id
        $open_id = $this ->getOpenId($session);

        $user_model = new \app\index\model\User();

        $user_id = $user_model -> getUserId($open_id);

        $comment_model = new \app\index\model\Comment();

        $res = $comment_model -> commentAdd($comment_type,$id_value,$parent_id,$comment_describle,$user_id);

        if($res){
            return $this -> successCode('','成功',1);
        }else{
            return $this -> errorCode('失败','',1000);
        }
    }
}
