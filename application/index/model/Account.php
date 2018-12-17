<?php
namespace app\index\model;

use think\Model;

class Account extends Model
{
    protected $table='fb_user_account';

    /*
     * 获取用户已经提现的金额
     */
    public function getUserAccountSum($user_id){

        $result=Account::where('id_paid',1)
            ->where(['user_id'=>$user_id])
            ->sum('amount');
        return $result;
    }

}