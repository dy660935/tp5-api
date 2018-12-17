<?php
namespace app\index\model;

use think\Model;

class History extends Model
{
    protected $table = 'fb_history';

    /**
     * 用户浏览历史记录增加
     * @param $user_id
     * @param $history_type
     * @param $id_value
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserHistory($user_id,$history_type,$id_value){

        $result = History::where(['user_id'=>$user_id,'history_type'=>$history_type,'id_value'=>$id_value])
            ->field('history_count,id')
            ->find();

        if($result){

            $res=$result->toArray();

            $this->upHistory($res['id'],$res['history_count']);

        }else{

            $this->addHistory($user_id,$history_type,$id_value);
        }
    }

    /**
     * 添加浏览历史方法
     * @param $user_id
     * @param $history_type
     * @param $id_value
     * @return bool
     */
    public function addHistory($user_id,$history_type,$id_value){
        $history = new History();
        $history->user_id= $user_id;
        $history->id_value= $id_value;
        $history->history_type = $history_type;
        $history->history_status = 1;
        $history->history_count = 1;
        $history->is_deleted = 1;
        $history->created_at = date('Y-m-d h:i:s',time());

        if($history->save()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 修改浏览记录方法
     * @param $id
     * @param $history_count
     * @return bool
     * @throws \think\exception\DbException
     */

    public function upHistory($id,$history_count){

        $history = History::get($id);

        $history->history_count = $history_count +1;

        if($history->save()){
            return true;
        }else{
            return false;
        }

    }



    public function getHistoryList($user_id){

        $result=History::where(['user_id'=>$user_id,'history_status'=>1,'is_deleted'=>1])
            ->field('history_type,id_value')
            ->order('updated_at','asc')
            ->limit(10)
            ->select();
        $data=[];
        if($result){
            $result=$result->toArray();
            $product_model = new Product();
            $strategy_model = new Strategy();
            foreach ($result as $k =>$v){
                if($v['history_type']==1){

                    $product_data=$product_model->productInfoFind($v['id_value']);

                    if($product_data){
                        $product_data['is_product']=1;
                        unset($product_data['shop_price']);
                        $price=$product_data['price'][0];
                        $product_data['shop_price']=$price['shop_price'];
                        $product_data['website_name']=$price['website_name'];
                        $product_data['price_type']=$price['price_type'];
                        if(!$product_data['market_price']){
                            $product_data['market_price']=$product_data['shop_price'];
                        }
                        unset($product_data['price']);
                        unset($product_data['other_price']);
                        $data[]=$product_data;

                    }

                }else{
                    $product_data=$strategy_model->strategyInfoFind($v['id_value']);
                    if($product_data){
                        $time=strtotime($product_data['created_at']);
                        $product_data['created_at']=date("Y.m.d",$time);
                        $product_data['is_product']=2;
                        $product_data['strategy_id']=$v['id_value'];
                        $data[]=$product_data;
                    }
                }
            }
                return $data;
        }else{
            return false;
        }
    }

}