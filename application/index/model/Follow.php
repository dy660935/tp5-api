<?php
namespace app\index\model;

use think\Model;

class Follow extends Model
{
    protected $table='fb_following';

    /**
     * 判断用户是否关注方法
     * @param $user_id
     * @param $following_type
     * @param $id_value
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserFollow($user_id,$following_type,$id_value){

        $result=Follow::where(['user_id'=>$user_id,'following_type'=>$following_type,'id_value'=>$id_value])
            ->field('following_status,id')
            ->find();

        if($result){
            $res=$result->toArray();

            return $res;
        }else{
            return false;
        }
    }

    public function getFollowNum($follow_type,$id_value){

        $num=Follow::where(['following_type'=>$follow_type,'id_value'=>$id_value])
            ->count();
        return $num;

    }

    /**
     * 添加关注方法
     * @param $user_id
     * @param $following_type
     * @param $id_value
     * @return bool
     */
    public function addFollow($user_id,$following_type,$id_value){
        $follow = new Follow();
        $follow->user_id= $user_id;
        $follow->id_value= $id_value;
        $follow->following_type = $following_type;
        $follow->following_status = 1;
        $follow->is_deleted = 1;
        $follow->created_at = date('Y-m-d h:i:s',time());
        if($follow->save()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 修改关注方法
     * @param $following_type
     * @param $id
     * @return bool
     * @throws \think\exception\DbException
     */

    public function upFollow($following_type,$id){

        $follow = Follow::get($id);

        if($following_type==1){
            $follow->following_status = 2;
        }else{
            $follow->following_status = 1;
        }

        if($follow->save()){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 获取关注列表
     * @param $user_id
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFollowList($user_id){
        $result=Follow::where(['following_status'=>1,'user_id'=>$user_id])
            ->field('following_type,id_value')
            ->order('created_at','desc')
            ->select();

        if($result){
            $result=$result->toArray();
            $brand_model=new Brand();
            $strategy_model =new Strategy();
            $data=[];
            foreach ($result as $k =>$v){
                if($v['following_type']==1){
                    $brand_data=$brand_model->getBrandInfo($v['id_value']);
                    if($brand_data){
                        $brand_data['is_brand']=1;
                        $data[]=$brand_data;
                    }

                }else{
                    $strategy_data=$strategy_model->strategyInfoFind($v['id_value']);
                    if($strategy_data){
                        $strategy_data['is_brand']=2;
                        $strategy_data['strategy_id']=$v['id_value'];
                        $data[]=$strategy_data;
                    }
                }
            }
           return $data;
        }else{
            return false;
        }
    }
}