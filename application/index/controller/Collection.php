<?php
namespace app\index\controller;


class Collection extends Common
{
    /**
     *  用户收藏或取消接口
     */

    public function userCollection(){

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

        $collection_type = request()->post('collection_type');

        if(!$collection_type){

            return $this -> errorCode('参数有误','',1000);
        }

        $collectionModel = new \app\index\model\Collection();

        $result=$collectionModel->getUsercollection($user_id,$collection_type,$id);

        if(!$result){

            $add=$collectionModel->addCollection($user_id,$collection_type,$id);

            if($add){

                return $this->successCode('','收藏成功',1);

            }else{

                return $this->errorCode('请稍后再试','','1000');
            }
        }elseif($result['collection_status']==1){

            $updata=$collectionModel->upCollection($result['collection_status'],$result['id']);

            if($updata){

                return $this->successCode('','你已成功取消收藏',1);

            }else{

                return $this->errorCode('','','1000');
            }

        }else{

            $updata=$collectionModel->upCollection($result['collection_status'],$result['id']);

            if($updata){

                return $this->successCode('','收藏成功',1);

            }else{

                return $this->errorCode('','','1000');
            }

        }
    }

    /**
     * 收藏列表接口
     */
    public function collectionList(){
        #获取用户的id
        $session = request()->post('session');

        $open_id=$this->getOpenId($session);

        $userModel = new \app\index\model\User();

        $user_id=$userModel->getUserId($open_id);

        $collectionModel = new \app\index\model\Collection();

        $result=$collectionModel->getcollectionList($user_id);

        if($result){

        return $this -> successCode($result,'',1);

        }else{

        return $this->errorCode('','','1000');
        }
    }
}
