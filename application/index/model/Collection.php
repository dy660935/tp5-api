<?php
namespace app\index\model;

use think\Model;

class Collection extends Model
{
    protected $table='fb_collection';

    /**
     *  判断用户是否收藏方法
     * @param $user_id
     * @param $collection_type
     * @param $id_value
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserCollection($user_id,$collection_type,$id_value){

        $result=Collection::where(['user_id'=>$user_id,'collection_type'=>$collection_type,'id_value'=>$id_value])
            ->field(['collection_status,id'])
            ->find();

        if($result){
            $res=$result->toArray();
            return $res;
        }else{
            return false;
        }
    }

    /**
     * 获取收藏数量
     * @param $collection_type
     * @param $id_value
     * @return int|string
     */
    public function getCollectionNum($collection_type,$id_value){

        $num=Collection::where(['collection_type'=>$collection_type,'id_value'=>$id_value])
            ->count();
        return $num;

    }

    /**
     * 添加收藏方法
     * @param $user_id
     * @param $collection_type
     * @param $id_value
     * @return bool
     */
    public function addCollection($user_id,$collection_type,$id_value){
        $collection = new Collection();
        $collection->user_id= $user_id;
        $collection->id_value= $id_value;
        $collection->collection_type = $collection_type;
        $collection->collection_status = 1;
        $collection->is_deleted = 1;
        $collection->created_at = date('Y-m-d h:i:s',time());
        if($collection->save()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 修改收藏方法
     * @param $collection_type
     * @param $id
     * @return bool
     * @throws \think\exception\DbException
     */
    public function upCollection($collection_type,$id){

        $collection = Collection::get($id);

        if($collection_type==1){
            $collection->collection_status = 2;
        }else{
            $collection->collection_status = 1;
        }

        if($collection->save()){
            return true;
        }else{
            return false;
        }

    }

    public function getcollectionList($user_id){
        $result=Collection::where(['collection_status'=>1,'user_id'=>$user_id])
            ->field('collection_type,id_value')
            ->order('created_at','desc')
            ->select();

        if($result){
            $result=$result->toArray();
            $product_model = new Product();
            $share_model = new Share();
            $comment_model= new Comment();
            $collection_model = new Collection();
            $stratrgy_model = new Strategy();
            $data=[];
            foreach ($result as $k=>$v){

                if($v['collection_type']==1){
                    $product_data=$product_model->productInfoFind($v['id_value']);
                    $product_data['is_product']=1;

                }else{

                    $product_data=$stratrgy_model->strategyInfoFind($v['id_value']);
                    $product_data['is_product']=2;
                    $product_data['strategy_id']=$v['id_value'];
                }

                $share_num=$share_model->getShareNum($v['collection_type'],$v['id_value']);

                $comment_num=$comment_model->getCommentNum($v['collection_type'],$v['id_value']);

                $collection_num=$collection_model->getCollectionNum($v['collection_type'],$v['id_value']);

                if($product_data){
                    $product_data['share_num']=$share_num;
                    $product_data['comment_num']=$comment_num;
                    $product_data['collection_num']=$collection_num;
                    $product_data['is_collection']=1;
                    $data[]=$product_data;
                }
            }
                return $data;
        }else{
            return false;
        }
    }
}