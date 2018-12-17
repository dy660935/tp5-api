<?php
namespace app\index\controller;

use app\index\model\Author;
use app\index\model\Collection;
use app\index\model\Follow;
use app\index\model\User;

class Strategy extends Common
{
    /**
     * 攻略详情页面
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function strategyDetail(){

        $strategy_id = request() -> post("strategy_id");
//        $session = request()->post('session');

//        if(empty($session)){
//
//            return $this -> errorCode('session参数有误','',1000);
//
//        }

        if (empty($strategy_id)){

            return $this -> errorCode('参数有误','',1000);

        }else{
            $strategy_model = new \app\index\model\Strategy();

            $strategy_info = $strategy_model -> strategyInfoFind($strategy_id);
            
	    //获取用户的openId
//            $open_id = $this ->getOpenId($session);
//
//            $user_model = new User();
//
//            $user_id = $user_model -> getUserId($open_id);
//
//            //获取商品的评论
//            $comment_model = new \app\index\model\Comment();
//
//            $comment_info = $comment_model -> getCommentAll($strategy_id,2,$user_id);
//
//            $collection_model = new Collection();
//
//            $is_collection = $collection_model ->getUserCollection($user_id,2,$strategy_id);
//
//	    if($is_collection['collection_status']==1){
//		$strategy_info['is_collection'] = 1;
//	    }else{
//		$strategy_info['is_collection'] = 0;
//	    }
//            $strategy_info['comment_info'] = $comment_info;

            //增加浏览记录
//            $history_model = new \app\index\model\History();

//            $history_model ->getUserHistory($user_id,2,$strategy_id);

            return $this ->successCode($strategy_info,'成功',1);

        }

    }

    //攻略列表
    public function strategyIndex(){
//        $author_id = request()->post('author_id');

        $strategy_type = request()->post('strategy_type');

//        $session = request()->post('session');

//        if(empty($author_id)){
//            $author_id = 8;
//        }

        if(empty($strategy_type)){
            $strategy_type = 0;
        }

//        if(empty($session)){
//            return $this -> errorCode('参数错误','',1000);
//        }

//        $author_model = new Author();
//
//        $author_info = $author_model -> getAuthorInfo($author_id);

//        $strategy_all = $this -> strategyTabPublic($strategy_type,$author_id,$session);
        $strategy_all = $this -> strategyTabPublic($strategy_type);
        #数据整理
//        $json_data['author_info'] = $author_info;
//        $json_data['strategy_count'] = $strategy_all['strategy_count'];
//        $json_data['strategy_info'] = $strategy_all['strategy_info'];

        return $this -> successCode($strategy_all ,'成功',1);
    }



    public function strategyTabPublic($strategy_type)
    {
//        $user_id = $this ->getUserId($session);

        //获取是否关注
//        $following_model = new Follow();
//
//        $following_status = $following_model -> getUserFollow($user_id,2,$author_id);
//
//        if($following_status['following_status']==1){
//            $author_info['is_following'] = 1;
//        }else{
//            $author_info['is_following'] = 0;
//        }

        //获取热门、攻略
        $strategy_model = new \app\index\model\Strategy();

//        $strategy_all = $strategy_model -> getStrategyIndex($strategy_type,$author_id);
        $strategy_all = $strategy_model -> getStrategyIndex($strategy_type);

        //获取是否收藏

//        $collection_model = new Collection();

//        foreach ($strategy_all['strategy_info'] as $k => $v){
//
//            $is_collection=$collection_model->getUserCollection($user_id,2,$v['id']);
//            if($is_collection['collection_status']==1){
//                 $strategy_all['strategy_info'][$k]['is_collection']=1;
//                }else{
//                $strategy_all['strategy_info'][$k]['is_collection']=0;
//
//            }
//	    }
        	return $strategy_all;
	}
}
