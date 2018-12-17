<?php
namespace app\index\model;

use think\Db;
use think\Model;
use lib\PhpAnalysis;
class Comment extends Model
{
    protected $table='fb_comment';

    //评论增加
    public function commentAdd($comment_type,$id_value,$parent_id,$comment_describle,$user_id){

        $comment_title = $this ->getCommentTitle($comment_type,$id_value);

        $comment_bool = $this ->getCommentStatus($comment_describle);

        if($comment_bool){

            $comment_status = 1;

        }else{

            $comment_status = 3;
        }

        $res = Db::table('fb_comment')
                ->insert([
                    'user_id'=>$user_id,
                    'id_value'=>$id_value,
                    'comment_type' => $comment_type,
                    'parent_id' => $parent_id,
                    'comment_describle' => $comment_describle,
                    'user_id' => $user_id,
                    'comment_title' => $comment_title,
                    'comment_status' => $comment_status
                ]);

        return $res;
    }

    /**
     * 关键字过滤
     * @param $comment_describle
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCommentStatus($comment_describle)
    {
	$obj = new PhpAnalysis();

        $obj ->differFreq = false;

        $obj ->SetSource($comment_describle,'utf8','utf8');

        $obj ->StartAnalysis();

        $res = $obj ->GetFinallyKeywords(2);

        $result = Db::table('fb_sensitive')
            ->where('sensitive_words','in',$res)
            ->whereOr('sensitive_words','like',$comment_describle)
            ->select();

        if($result){

            return false;

        }else{

            return true;
        }
    }
    /**
     * 通过评论类型获取对应的商品或者攻略的标题
     * @param $comment_type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCommentTitle($comment_type,$id_value)
    {
        if($comment_type == 1){

            $comment_title = Db::table("fb_product")
                ->where(['id' => $id_value])
                ->field('product_name')
                ->find();
	
        return $comment_title['product_name'];
        }else{

            $comment_title = Db::table('fb_strategy')
                ->where(['id' => $id_value])
                ->field('strategy_title')
                ->find();

        return $comment_title['strategy_title'];
        }

    }

    /**
     * 判断用户是否评论方法
     */
    public function getUserComment($user_id,$comment_type,$id_value){

        $result=Comment::where(['user_id'=>$user_id,'comment_type'=>$comment_type,'id_value'=>$id_value])
            ->field(['comment_status'])
            ->find();

        if($result){
            $res=$result->toArray();
            if($res['comment_status']==1){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 获取用户下的评论
     * @param $id_value
     * @param $type
     * @param $user_id
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCommentAll($id_value,$type,$user_id)
    {
        $comment_all = Db::table('fb_comment')
                ->alias('a')
                ->join('fb_user b','a.user_id = b.id')
                ->where(['id_value' => $id_value ,'comment_type' => $type])
                ->field('a.comment_describle,a.user_id,a.comment_status,b.user_avatar,b.user_define_nickname')
                ->select();

        if(!empty($comment_all)){
            foreach ($comment_all as $k => $v){
                if($v['user_id'] != $user_id && $v['comment_status'] != 1){
                    unset($comment_all[$k]);
                }
            }

            return $comment_all;

        }else{

            return [];
        }
    }

    public function getCommentNum($comment_type,$id_value){

        $num=Comment::where(['comment_type'=>$comment_type,'id_value'=>$id_value])
               ->where('comment_status',1)
	       ->count();
        return $num;

    }
}
