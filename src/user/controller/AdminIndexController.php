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
use think\db\Query;

/**
 * Class AdminIndexController
 * @package app\user\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'用戶管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'group',
 *     'remark' =>'用戶管理'
 * )
 *
 * @adminMenuRoot(
 *     'name'   =>'用戶組',
 *     'action' =>'default1',
 *     'parent' =>'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'',
 *     'remark' =>'用戶組'
 * )
 */
class AdminIndexController extends AdminBaseController
{

    /**
     * 後臺本站用戶列表
     * @adminMenu(
     *     'name'   => '本站用戶',
     *     'parent' => 'default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用戶',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $content = hook_one('user_admin_index_view');

        if (!empty($content)) {
            return $content;
        }

        $list = Db::name('user')
            ->where(function (Query $query) {
                $data = $this->request->param();
                if (!empty($data['uid'])) {
                    $query->where('id', intval($data['uid']));
                }

                if (!empty($data['keyword'])) {
                    $keyword = $data['keyword'];
                    $query->where('user_login|user_nickname|user_email|mobile', 'like', "%$keyword%");
                }

            })
            ->order("create_time DESC")
            ->paginate(10);
        // 獲取分頁顯示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板輸出
        return $this->fetch();
    }

    /**
     * 本站用戶拉黑
     * @adminMenu(
     *     'name'   => '本站用戶拉黑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用戶拉黑',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 0);
            if ($result) {
                $this->success("會員拉黑成功！", "adminIndex/index");
            } else {
                $this->error('會員拉黑失敗,會員不存在,或者是管理員！');
            }
        } else {
            $this->error('數據傳入失敗！');
        }
    }

    /**
     * 本站用戶啟用
     * @adminMenu(
     *     'name'   => '本站用戶啟用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用戶啟用',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 1);
            $this->success("會員啟用成功！", '');
        } else {
            $this->error('數據傳入失敗！');
        }
    }
}
