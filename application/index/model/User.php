<?php
namespace app\index\model;

use think\Db;
use think\Model;

class User extends Model
{
    protected $table='fb_user';

    /**
     * 获取用户的id
     * @param $open_id
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserId($open_id){

        if($open_id) {

            $result=User::where('user_open_id',$open_id)
                ->field('id')
                ->find();
            if($result){

                $result=$result->toArray();

                $user_id=$result['id'];

                return $user_id;

            }else{
                return false;
            }


        }else{
            return false;
        }
    }


    /**
     * 获取用户的详情
     * @param $user_id
     * @return array|bool|false|\PDOStatement|string|Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function getUserInfo($user_id){

        $result=User::where(['user_status'=>1,'is_deleted'=>1])
            ->where('id',$user_id)
            ->field('id,user_avatar,user_define_nickname,user_wechat_nickname')
            ->find();
        if($result){
            $result=$result->toArray();
//            var_dump($result);
            if(!isset($result['user_define_nickname'])){
                $result['user_name']=$result['user_wechat_nickname'];
                unset($result['user_define_nickname']);
                unset($result['user_wechat_nickname']);
            }else{
                $result['user_name']=$result['user_define_nickname'];
                unset($result['user_define_nickname']);
                unset($result['user_wechat_nickname']);
            }
            return $result;
        }else{
            return false;
        }
    }

    /*
     * 修改用户登录来源信息
     */
    public function upUser($user_id,$parent_id){

        $user=User::get($user_id);

        $user->is_invitation=1;

        $user->parent_id=$parent_id;

        if($user->save()){

            return true;
        }else{
            return false;
        }
    }

    /**
     * 添加用户的唯一标识
     * @param $id
     * @param $user_only_num
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function addUserOnlyNum($id,$user_only_num){

        Db::table('fb_user')
            ->where(['id' => $id])
            ->update(['user_only_num' => $user_only_num]);
    }


}