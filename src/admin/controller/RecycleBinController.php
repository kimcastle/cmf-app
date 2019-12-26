<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\RecycleBinModel;
use app\admin\model\RouteModel;
use cmf\controller\AdminBaseController;
use think\Db;

class RecycleBinController extends AdminBaseController
{
    /**
     * 回收站
     * @adminMenu(
     *     'name'   => '回收站',
     *     'parent' => '',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '回收站',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $content = hook_one('admin_recycle_bin_index_view');

        if (!empty($content)) {
            return $content;
        }

        $recycleBinModel = new RecycleBinModel();
        $list            = $recycleBinModel->order('create_time desc')->paginate(10);
        // 獲取分頁顯示
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 回收站還原
     * @adminMenu(
     *     'name'   => '回收站還原',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '回收站還原',
     *     'param'  => ''
     * )
     */
    public function restore()
    {

        $id     = $this->request->param('id', 0, 'intval');
        $result = Db::name('recycleBin')->where('id', $id)->find();

        $tableName = explode('#', $result['table_name']);
        $tableName = $tableName[0];
        //還原資源
        if ($result) {
            $res = Db::name($tableName)
                ->where('id', $result['object_id'])
                ->update(['delete_time' => '0']);
            if ($tableName == 'portal_post') {
                Db::name('portal_category_post')->where('post_id', $result['object_id'])->update(['status' => 1]);
                Db::name('portal_tag_post')->where('post_id', $result['object_id'])->update(['status' => 1]);
            }

            if ($res) {
                $re = Db::name('recycleBin')->where('id', $id)->delete();
                if ($re) {
                    $this->success("還原成功！");
                }
            }
        }
    }

    /**
     * 回收站徹底刪除
     * @adminMenu(
     *     'name'   => '回收站徹底刪除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '回收站徹底刪除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id     = $this->request->param('id');
        $result = Db::name('recycleBin')->where('id', $id)->find();
        //刪除資源
        if ($result) {

            //頁面沒有單獨的表.
            if ($result['table_name'] === 'portal_post#page') {
                $re = Db::name('portal_post')->where('id', $result['object_id'])->delete();
                //消除路由
                $routeModel = new RouteModel();
                $routeModel->setRoute('', 'portal/Page/index', ['id' => $result['object_id']], 2, 5000);
                $routeModel->getRoutes(true);
            } else {
                $re = Db::name($result['table_name'])->where('id', $result['object_id'])->delete();
            }

            if ($re) {
                $res = Db::name('recycleBin')->where('id', $id)->delete();
                if ($result['table_name'] === 'portal_post') {
                    Db::name('portal_category_post')->where('post_id', $result['object_id'])->delete();
                    Db::name('portal_tag_post')->where('post_id', $result['object_id'])->delete();
                }
                if ($res) {
                    $this->success("刪除成功！");
                }

            }
        }
    }
}