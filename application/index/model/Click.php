<?php
namespace app\index\model;

use think\Db;
use think\Model;

class Click extends Model
{
    protected $table='fb_click_thumbs';

    public function ClickThumbs($id_value,$click_thumbs_type,$user_id)
    {
        //是否有点赞记录
        $result = Db::table('fb_click_thumbs')
            ->where(['id_value' => $id_value , 'click_thumbs_type' => $click_thumbs_type , 'user_id' => $user_id])
            ->field('click_thumbs_status')
            ->find();

        //无
        if(empty($result)){
            Db::table('fb_click_thumbs')
                ->insert([
                    'id_value' => $id_value,
                    'click_thumbs_type' => $click_thumbs_type,
                    'user_id' => $user_id,
                    'click_thumbs_status' => 1,
                    'created_at' => date('Y-m-d H:i:s',time()),
                ]);

            //攻略和评论点赞增加

            $res = $this ->clickInc($click_thumbs_type,$id_value);

        }else{
           //有
            if($result['click_thumbs_status'] == 0){

                Db::table('fb_click_thumbs')
                    ->where(['id_value' => $id_value , 'user_id' => $user_id])
                    ->update(['click_thumbs_status' => 1]);

                //攻略和评论点赞增加

                $res = $this ->clickInc($click_thumbs_type,$id_value);
            }else{
                Db::table('fb_click_thumbs')
                    ->where(['id_value' => $id_value , 'user_id' => $user_id])
                    ->update(['click_thumbs_status' => 0]);

                //攻略和评论点赞自减
                $res = $this ->clickDoc($click_thumbs_type,$id_value);
            }
        }

        return $res;
    }

    public function clickInc($click_thumbs_type,$id_value){
        //攻略
        if($click_thumbs_type == 1){
            Strategy::where(['id' => $id_value])->setInc('strategy_clicks');
            Strategy::where(['id' => $id_value])->setInc('strategy_daily_clicks');
        }else{
            //评论
            Comment::where(['id' => $id_value])->setInc('click_thumbs_number');
        }

        return true;
    }

    public function clickDoc($click_thumbs_type,$id_value){
        if($click_thumbs_type == 1){
            Strategy::where(['id' => $id_value])->setDec('strategy_clicks');
            Strategy::where(['id' => $id_value])->setDec('strategy_daily_clicks');
        }else{
            //评论
            Comment::where(['id' => $id_value])->setDec('click_thumbs_number');
        }

        return false;
    }
}