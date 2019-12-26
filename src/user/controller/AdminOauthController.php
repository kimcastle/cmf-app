<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class AdminOauthController extends AdminBaseController
{

    /**
     * 後臺第三方用戶列表
     * @adminMenu(
     *     'name'   => '第三方用戶',
     *     'parent' => 'user/AdminIndex/default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '第三方用戶',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $content = hook_one('user_admin_oauth_index_view');

        if (!empty($content)) {
            return $content;
        }

        $lists = Db::name('third_party_user')->field('a.*,u.user_nickname,u.sex,u.avatar')
            ->alias('a')
            ->join('__USER__ u', 'a.user_id = u.id')
            ->where("status", 1)
            ->order("create_time DESC")
            ->paginate(10);

        // 獲取分頁顯示
        $page = $lists->render();
        $this->assign('lists', $lists);
        $this->assign('page', $page);
        // 渲染模板輸出
        return $this->fetch();
    }

    /**
     * 後臺刪除第三方用戶綁定
     * @adminMenu(
     *     'name'   => '刪除第三方用戶綁定',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '刪除第三方用戶綁定',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id = input('param.id', 0, 'intval');
        if (empty($id)) {
            $this->error('非法數據！');
        }
        Db::name("third_party_user")->where("id", $id)->delete();
        $this->success("刪除成功！", "admin_oauth/index");
    }


}