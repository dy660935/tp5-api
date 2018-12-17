<?php
namespace app\index\controller;


class History extends Common
{
    /**
     * 浏览记录列表接口
     */
    public function historyList(){
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

        $history_model = new \app\index\model\History();

        $result=$history_model->getHistoryList($user_id);

        if($result){

            return $this->successCode($result,'',1);

        }else{

            return $this->errorCode('','','1000');
        }
    }
}
