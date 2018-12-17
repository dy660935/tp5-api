<?php

namespace app\index\model;

use think\Model;
use think\Db;

class Strategy extends Model
{
    protected $table = 'fb_strategy';

    /**
     * 获取首页轮播图信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSlider ()
    {
        $strategy_info = Strategy ::where( 'strategy_status' , 1 ) -> limit( 5 ) -> order( 'created_at' , 'desc' ) -> field( 'id,strategy_slider_image,created_at,is_weChat_add,strategy_describe' ) -> select() -> toArray();

        foreach ( $strategy_info as $k => $v ) {
            $strategy_info[ $k ][ 'strategy_image' ] = $v[ 'strategy_slider_image' ];
        }

        return $strategy_info;
    }

    /**
     * 获取首页的热门攻略信息
     * @return array
     */
    public function getIndexStrategy ()
    {
        $sql = "SELECT
            a.id,
            strategy_title,
            strategy_image,
            a.created_at,
            author_head_portrait,
            author_name,
            strategy_clicks,
	        is_weChat_add
            FROM fb_strategy AS a
            JOIN fb_author AS b ON a.author_id = b.id
            WHERE  strategy_status = 1
            ORDER BY strategy_weight desc
            LIMIT 1";

        $strategy = Db ::query( $sql );
        $strategy_info = [];
        foreach ( $strategy as $k => $v ) {
            $time = strtotime( $v[ 'created_at' ] );
            $strategy[ $k ][ 'created_at' ] = date( 'Y-m-d' , $time );
            $strategy_info = $strategy[ $k ];
        }

        return $strategy_info;
    }

    /**
     * 首页分页tab
     *
     * @param $category_id
     * @param $p
     *
     * @return array
     */
    public function getIndexTabStrategyInfo ( $category_id , $p )
    {
        //var_dump($p);die;
        if ( empty( $p ) ) {
            $limit = 0;
        }
        elseif ( $p % 10 != 0 ) {
            $limit = ( ( ( ( $p + 10 ) - ( $p % 10 ) ) / 10 ) ) * 1;
            if ( $limit <= $p ) {
                $limit = ( ( ( ( $p + 10 ) - ( $p % 10 ) ) / 10 ) + 1 ) * 1;
            }
        }
        else {

            $limit = ( ( $p + 10 ) / 10 - 1 ) * 1;
        }

        if ( $category_id == 0 ) {
            $where = "strategy_status = 1";
        }
        else {
            $where = "strategy_status = 1 and c.category_id = $category_id";
        }

        $sql = "SELECT
            a.id,
            strategy_title,
            strategy_image,
            a.created_at,
            c.category_id,
            author_head_portrait,
            author_name,
            strategy_clicks,
	    is_weChat_add
            FROM fb_strategy AS a
            JOIN fb_author AS b ON a.author_id = b.id
            join fb_category_strategy_mapping as c on a.id = c.strategy_id
            WHERE  $where
            ORDER BY strategy_weight desc
            LIMIT $limit ,1";

        $strategy = Db ::query( $sql );
        $strategy_info = [];
        foreach ( $strategy as $k => $v ) {
            $time = strtotime( $v[ 'created_at' ] );
            $strategy[ $k ][ 'created_at' ] = date( 'Y-m-d' , $time );
            $strategy_info = $strategy[ $k ];
        }

        return $strategy_info;
    }


    /**
     * 根据品牌的id获取攻略的信息
     *
     * @param $brand_name
     * @param string $where
     *
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStrategyBrand ( $brand_name , $where = "" )
    {

        if ( $where ) {
            $result = Strategy ::where( [ "is_deteled" => 1 , 'strategy_status' => 1 ] ) -> where( $where ) -> where( 'strategy_label' , 'like' , "%$brand_name%" ) -> whereOr( "strategy_title" , "like" , "%$brand_name%" ) -> field( 'id,strategy_title,strategy_image,strategy_describe,strategy_clicks,is_weChat_add' ) -> order( 'strategy_clicks' , "desc" ) -> select();

        }
        else {
            $result = Strategy ::where( [ "is_deteled" => 1 , 'strategy_status' => 1 ] ) -> where( 'strategy_label' , 'like' , "%$brand_name%" ) -> whereOr( "strategy_title" , "like" , "%$brand_name%" ) -> field( 'id,strategy_title,strategy_image,strategy_describe,strategy_clicks,is_weChat_add' ) -> order( 'strategy_clicks' , "desc" ) -> select();
        }
        if ( $result ) {
            $data[ 'num' ] = $result -> count();
            $data[ 'data' ] = $result -> toArray();
            return $data;
        }
        else {
            return false;
        }

    }

    /**
     * 攻略单条
     *
     * @param $strategy_id
     *
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function strategyInfoFind ( $strategy_id )
    {
        //修改总的点击量
        Strategy ::where( [ 'id' => $strategy_id ] ) -> setInc( 'strategy_clicks' );

        //今天凌晨时间戳
        $today = strtotime( date( 'Y-m-d' ) );
        $now = time();

        if ( $now - $today > 86400 ) {

            Db ::where( [ 'id' => $strategy_id ] ) -> update( [ 'strategy_daily_clicks' => 1 ] );

        }
        else {

            Strategy ::where( [ 'id' => $strategy_id ] ) -> setInc( 'strategy_daily_clicks' );
        }

        $strategy_info = Db ::table( 'fb_strategy' ) -> alias( 'a' ) -> join( 'fb_author b' , 'a.author_id=b.id' ) -> where( [ 'a.id' => $strategy_id ] ) -> field( 'strategy_title,strategy_wechat_url,strategy_slider_image,strategy_describe,author_name,author_head_portrait,a.created_at,strategy_clicks,is_weChat_add,strategy_abstract,strategy_wechat_url' ) -> find();
        //        $res = Db::table('fb_strategy')->getLastSql();
        //
        //        var_dump($res);die;
        $strategy_info[ 'strategy_image' ] = $strategy_info[ 'strategy_slider_image' ];
        
        return $strategy_info;
    }


    public function getStrategyIndex ( $strategy_type )
    {

        if ( $strategy_type == 0 ) {

            //            $where = ['strategy_status' => 1 ,'author_id' => $author_id];
            $where = [ 'strategy_status' => 1 ];

            $order = 'strategy_clicks';

            $by = 'desc';

        }
        else {
            //            $where = ['strategy_status' => 1 ,'author_id' => $author_id];
            $where = [ 'strategy_status' => 1 ];

            $order = 'fb_strategy.created_at';

            $by = 'desc';
        }

        $strategy_info = Db ::table( 'fb_strategy' ) -> alias( 'a' ) -> join( 'fb_author b' , 'a.author_id=b.id' ) -> where( $where ) -> order( $order , $by ) -> field( 'a.id,strategy_title,strategy_slider_image,strategy_abstract,strategy_clicks,is_weChat_add,author_name,author_head_portrait' ) -> select();
        foreach ( $strategy_info as $k => $v ) {
            $strategy_info[ $k ][ 'strategy_abstract' ] = mb_substr( $v[ 'strategy_abstract' ] , 0 , 50 );
            $strategy_info[ $k ][ 'strategy_image' ] = $v[ 'strategy_slider_image' ];
        }
        //        $shareModel = new Share();
        //
        //        $commentModel = new Comment();
        //
        //        $collectionModel = new Collection();

        //        $strategy_count = Db::table('fb_strategy')
        //            ->where(['strategy_status' => 1 ,'author_id' => $author_id])
        //            ->count();

        //收藏分享评论数量
        //        foreach ( $strategy_info as $k => $v){
        //
        //            $strategy_info[$k]['share_num']=$shareModel->getShareNum(2,$v['id']);
        //
        //            $strategy_info[$k]['comment_num']=$commentModel->getCommentNum(2,$v['id']);
        //
        //            $strategy_info[$k]['collection_num']=$collectionModel->getCollectionNum(2,$v['id']);
        //        }

        //        $info['strategy_info'] = $strategy_info;
        //        $info['strategy_count'] = $strategy_count;

        //        return $info;
        return $strategy_info;
    }
}
