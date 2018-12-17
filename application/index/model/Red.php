<?php
namespace app\index\model;

use think\Config;
use think\Db;
use think\Model;

class Red extends Model
{
    protected $table='fb_red_envelopes';

    /**
     * 获取用户的红包总额
     * @param $user_id
     * @return float|int
     */
    public function getUserRedSum($user_id,$where=""){
        if($where){
            $result=Red::where(['red_envelopes_status'=>1])
                ->where(['user_id'=>$user_id])
                ->where($where)
                ->sum('red_envelopes_money');
        }else{
            $result=Red::where(['red_envelopes_status'=>1])
                ->where(['user_id'=>$user_id])
                ->sum('red_envelopes_money');
        }
        if($result){

            return $result;

        }else{
            return 0;
        }
    }

    public function getUserRed($user_id){
        $result=Red::where(['user_id'=>$user_id,'red_envelopes_status'=>1])
            ->field('bonus_from,bonus_type,red_envelopes_money,created_at')
            ->select();
        if($result){
            $result=$result->toArray();
            $user_model = new User();
            foreach ($result as $k=>$v){
                $time=strtotime($v['created_at']);
                $result[$k]['created_at'] =date("Y.m.d H:i:s",$time);
                if($v['bonus_type']==1){
                    $user_info=$user_model->getUserInfo($v['bonus_from']);
                    if($user_info){
                        $result[$k]['user_name']=$user_info['user_name'];
                        $result[$k]['red_envelopes_money'] ='+'.$v['red_envelopes_money'];
                        $result[$k]['mark'] ='注册成功';
                    }

                }else{		
               	    $result[$k]['user_name']='最低价小助手';
                    $result[$k]['red_envelopes_money'] ='+'.$v['red_envelopes_money'];
                    $result[$k]['mark'] ='系统奖励';
		}
            }
           return $result;
        }else{
            return false;
        }
    }

    /*
     * 给邀请人红包
     */
    public function addUserRed($user_id,$id){

        #根据id获取用户邀请好友所的红包总额
        $bonus_user_num=$this->getUserRedSum($user_id);
        $bonus_max_num = Config::get('api.bonus_max_num');
        $bonus_one_max_num = Config::get('api.bonus_one_max_num');
        $bonus_num = Config::get('api.bonus_num');

        $bonus_max_num=number_format($bonus_max_num,2);
        $bonus_user_num=number_format($bonus_user_num,2);


        $overplus_num=$bonus_max_num-$bonus_user_num;
        $overplus_num=number_format($overplus_num,2);

        if($overplus_num==0){

            return false;

        }else{

            #根据红包的总额与邀请好友红包的上线进行对比
            if($overplus_num<=$bonus_one_max_num){

                $bonus_user_add=$overplus_num;

            }else{

                $bonus_user_add=array_rand($bonus_num,1);

                $bonus_user_add=$bonus_num[$bonus_user_add];
            }

            $res=$this->giveUserAdd($user_id,$bonus_user_add,$id);

            return $res;
        }
    }


    protected function giveUserAdd($user_id,$add_num,$bonus_from){

        $now = date("Y-m-d H:i:s" ,time());

        $insertData = [
            'user_id' => $user_id,
            'is_copy' => 1,
            'red_envelopes_status'=> 1,
            'created_at' => $now,
            'red_envelopes_money'=>$add_num,
            'bonus_from'=>$bonus_from,
            'bonus_type'=>1
        ];

        $res=Db::name('fb_red_envelopes')->insert($insertData);

        if($res){
            return true;
        }else{
            return false;
        }

    }


}
