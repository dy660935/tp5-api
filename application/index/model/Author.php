<?php
namespace app\index\model;

use think\Db;
use think\Model;

class Author extends Model
{
    protected $table='fb_author';

    public function getAuthorInfo($author_id){
        $author_info =Db::table('fb_author')
            ->where(['id' => $author_id])
            ->field('author_name,author_head_portrait')
            ->find();
        return $author_info;
    }
}