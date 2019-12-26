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

use app\admin\model\SlideModel;
use cmf\controller\AdminBaseController;
use think\Db;

class SlideController extends AdminBaseController
{

    /**
     * 幻燈片列表
     * @adminMenu(
     *     'name'   => '幻燈片管理',
     *     'parent' => 'admin/Setting/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 40,
     *     'icon'   => '',
     *     'remark' => '幻燈片管理',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $content = hook_one('admin_slide_index_view');

        if (!empty($content)) {
            return $content;
        }

        $slidePostModel = new SlideModel();
        $slides         = $slidePostModel->where('delete_time', 'eq', 0)->select();
        $this->assign('slides', $slides);
        return $this->fetch();
    }

    /**
     * 添加幻燈片
     * @adminMenu(
     *     'name'   => '添加幻燈片',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加幻燈片',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 添加幻燈片提交
     * @adminMenu(
     *     'name'   => '添加幻燈片提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加幻燈片提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        $data           = $this->request->param();
        $slidePostModel = new SlideModel();
        $result         = $this->validate($data, 'Slide');
        if ($result !== true) {
            $this->error($result);
        }
        $slidePostModel->save($data);

        $this->success("添加成功！", url("slide/index"));
    }

    /**
     * 編輯幻燈片
     * @adminMenu(
     *     'name'   => '編輯幻燈片',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '編輯幻燈片',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $id             = $this->request->param('id');
        $slidePostModel = new SlideModel();
        $result         = $slidePostModel->where('id', $id)->find();
        $this->assign('result', $result);
        return $this->fetch();
    }

    /**
     * 編輯幻燈片提交
     * @adminMenu(
     *     'name'   => '編輯幻燈片提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '編輯幻燈片提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $data           = $this->request->param();
        $slidePostModel = new SlideModel();
        $result         = $this->validate($data, 'Slide');
        if ($result !== true) {
            $this->error($result);
        }
        $slidePostModel->save($data, ['id' => $data['id']]);
        $this->success("保存成功！", url("slide/index"));
    }

    /**
     * 刪除幻燈片
     * @adminMenu(
     *     'name'   => '刪除幻燈片',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '刪除幻燈片',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id             = $this->request->param('id', 0, 'intval');
        $slidePostModel = new SlideModel();
        $result         = $slidePostModel->where('id', $id)->find();
        if (empty($result)) {
            $this->error('幻燈片不存在!');
        }

        //如果存在頁面。則不能刪除。
        $slidePostCount = Db::name('slide_item')->where('slide_id', $id)->count();
        if ($slidePostCount > 0) {
            $this->error('此幻燈片有頁面無法刪除!');
        }

        $data = [
            'object_id'   => $id,
            'create_time' => time(),
            'table_name'  => 'slide',
            'name'        => $result['name']
        ];

        $resultSlide = $slidePostModel->save(['delete_time' => time()], ['id' => $id]);
        if ($resultSlide) {
            Db::name('recycleBin')->insert($data);
        }
        $this->success("刪除成功！", url("slide/index"));
    }
}