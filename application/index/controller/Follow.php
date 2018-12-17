<?php
namespace app\index\controller;


class Follow extends Common
{
    /**
     * 用户关注或取消接口
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userFollow(){

        #获取用户的id
        $session_id = request()->post('session');

        if(!$session_id){

            return $this -> errorCode('参数有误','',1000);
        }

        $open_id=$this->getOpenId($session_id);

        if(!$session_id){

            return $this -> errorCode('参数有误','',1000);
        }

        $userModel = new \app\index\model\User();

        $user_id=$userModel->getUserId($open_id);

        $id = request()->post('id');

        if(!$id){

            return $this -> errorCode('参数有误','',1000);
        }

        $follow_type = request()->post('follow_type');

        if(!$follow_type){

            return $this -> errorCode('参数有误','',1000);
        }

        $followModel = new \app\index\model\Follow();

        $result=$followModel->getUserFollow($user_id,$follow_type,$id);

        if(!$result){

            $add=$followModel->addFollow($user_id,$follow_type,$id);

            if($add){

                return $this->successCode('','关注成功',1);

            }else{

                return $this->errorCode('请稍后再试','','1000');
            }
        }elseif($result['following_status']==1){

            $updata=$followModel->upFollow($result['following_status'],$result['id']);

            if($updata){

                return $this->successCode('','你已成功取消关注',1);

            }else{

                return $this->errorCode('','','1000');
            }

        }else{

            $updata=$followModel->upFollow($result['following_status'],$result['id']);

            if($updata){

                return $this->successCode('','关注成功',1);

            }else{

                return $this->errorCode('','','1000');
            }

        }
    }

    /**
     * 关注列表接口
     */
    public function followList(){
        #获取用户的id
        $session_id = request()->post('session');

        if(!$session_id){

            return $this -> errorCode('参数有误','',1000);
        }

        $open_id=$this->getOpenId($session_id);

        if(!$session_id){

            return $this -> errorCode('参数有误','',1000);
        }

        $userModel = new \app\index\model\User();

        $user_id=$userModel->getUserId($open_id);

        $followModel = new \app\index\model\Follow();

        $result=$followModel->getFollowList($user_id);

        if($result){

            return $this->successCode($result,'',1);

        }else{

            return $this->errorCode('','','1000');
        }
    }
}
