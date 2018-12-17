<?php
namespace app\index\model;

use think\Model;

class Share extends Model
{
    protected $table='fb_share';

    /**
     * 判断用户是否分享
     */
    public function getUserShare($user_id,$share_type,$id_value){

        $result=Share::where(['user_id'=>$user_id,'share_type'=>$share_type,'id_value'=>$id_value])
            ->field(['share_status'])
            ->find();

        if($result){
            $res=$result->toArray();
            if($res['share_status']==1){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function getShareNum($share_type,$id_value){

        $num=Share::where(['share_type'=>$share_type,'id_value'=>$id_value])
            ->count();

        return $num;

    }
}