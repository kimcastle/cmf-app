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

use cmf\controller\AdminBaseController;
use think\Db;
use think\db\Query;

/**
 * Class UserController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   => '管理組',
 *     'action' => 'default',
 *     'parent' => 'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   => '',
 *     'remark' => '管理組'
 * )
 */
class UserController extends AdminBaseController
{

    /**
     * 管理員列表
     * @adminMenu(
     *     'name'   => '管理員',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理員管理',
     *     'param'  => ''
     * )
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $content = hook_one('admin_user_index_view');

        if (!empty($content)) {
            return $content;
        }

        /**搜索條件**/
        $userLogin = $this->request->param('user_login');
        $userEmail = trim($this->request->param('user_email'));

        $users = Db::name('user')
            ->where('user_type', 1)
            ->where(function (Query $query) use ($userLogin, $userEmail) {
                if ($userLogin) {
                    $query->where('user_login', 'like', "%$userLogin%");
                }

                if ($userEmail) {
                    $query->where('user_email', 'like', "%$userEmail%");
                }
            })
            ->order("id DESC")
            ->paginate(10);
        $users->appends(['user_login' => $userLogin, 'user_email' => $userEmail]);
        // 獲取分頁顯示
        $page = $users->render();

        $rolesSrc = Db::name('role')->select();
        $roles    = [];
        foreach ($rolesSrc as $r) {
            $roleId           = $r['id'];
            $roles["$roleId"] = $r;
        }
        $this->assign("page", $page);
        $this->assign("roles", $roles);
        $this->assign("users", $users);
        return $this->fetch();
    }

    /**
     * 管理員添加
     * @adminMenu(
     *     'name'   => '管理員添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理員添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $content = hook_one('admin_user_add_view');

        if (!empty($content)) {
            return $content;
        }

        $roles = Db::name('role')->where('status', 1)->order("id DESC")->select();
        $this->assign("roles", $roles);
        return $this->fetch();
    }

    /**
     * 管理員添加提交
     * @adminMenu(
     *     'name'   => '管理員添加提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理員添加提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        if ($this->request->isPost()) {
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                $role_ids = $_POST['role_id'];
                unset($_POST['role_id']);
                $result = $this->validate($this->request->param(), 'User');
                if ($result !== true) {
                    $this->error($result);
                } else {
                    $_POST['user_pass'] = cmf_password($_POST['user_pass']);
                    $result             = DB::name('user')->insertGetId($_POST);
                    if ($result !== false) {
                        //$role_user_model=M("RoleUser");
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("為了網站的安全，非網站創建者不可創建超級管理員！");
                            }
                            Db::name('RoleUser')->insert(["role_id" => $role_id, "user_id" => $result]);
                        }
                        $this->success("添加成功！", url("user/index"));
                    } else {
                        $this->error("添加失敗！");
                    }
                }
            } else {
                $this->error("請為此用戶指定角色！");
            }

        }
    }

    /**
     * 管理員編輯
     * @adminMenu(
     *     'name'   => '管理員編輯',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理員編輯',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $content = hook_one('admin_user_edit_view');

        if (!empty($content)) {
            return $content;
        }

        $id    = $this->request->param('id', 0, 'intval');
        $roles = DB::name('role')->where('status', 1)->order("id DESC")->select();
        $this->assign("roles", $roles);
        $role_ids = DB::name('RoleUser')->where("user_id", $id)->column("role_id");
        $this->assign("role_ids", $role_ids);

        $user = DB::name('user')->where("id", $id)->find();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 管理員編輯提交
     * @adminMenu(
     *     'name'   => '管理員編輯提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理員編輯提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                if (empty($_POST['user_pass'])) {
                    unset($_POST['user_pass']);
                } else {
                    $_POST['user_pass'] = cmf_password($_POST['user_pass']);
                }
                $role_ids = $this->request->param('role_id/a');
                unset($_POST['role_id']);
                $result = $this->validate($this->request->param(), 'User.edit');

                if ($result !== true) {
                    // 驗證失敗 輸出錯誤信息
                    $this->error($result);
                } else {
                    $result = DB::name('user')->update($_POST);
                    if ($result !== false) {
                        $uid = $this->request->param('id', 0, 'intval');
                        DB::name("RoleUser")->where("user_id", $uid)->delete();
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("為了網站的安全，非網站創建者不可創建超級管理員！");
                            }
                            DB::name("RoleUser")->insert(["role_id" => $role_id, "user_id" => $uid]);
                        }
                        $this->success("保存成功！");
                    } else {
                        $this->error("保存失敗！");
                    }
                }
            } else {
                $this->error("請為此用戶指定角色！");
            }

        }
    }

    /**
     * 管理員個人信息修改
     * @adminMenu(
     *     'name'   => '個人信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理員個人信息修改',
     *     'param'  => ''
     * )
     */
    public function userInfo()
    {
        $id   = cmf_get_current_admin_id();
        $user = Db::name('user')->where("id", $id)->find();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 管理員個人信息修改提交
     * @adminMenu(
     *     'name'   => '管理員個人信息修改提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理員個人信息修改提交',
     *     'param'  => ''
     * )
     */
    public function userInfoPost()
    {
        if ($this->request->isPost()) {

            $data             = $this->request->post();
            $data['birthday'] = strtotime($data['birthday']);
            $data['id']       = cmf_get_current_admin_id();
            $create_result    = Db::name('user')->update($data);;
            if ($create_result !== false) {
                $this->success("保存成功！");
            } else {
                $this->error("保存失敗！");
            }
        }
    }

    /**
     * 管理員刪除
     * @adminMenu(
     *     'name'   => '管理員刪除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理員刪除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id == 1) {
            $this->error("最高管理員不能刪除！");
        }

        if (Db::name('user')->delete($id) !== false) {
            Db::name("RoleUser")->where("user_id", $id)->delete();
            $this->success("刪除成功！");
        } else {
            $this->error("刪除失敗！");
        }
    }

    /**
     * 停用管理員
     * @adminMenu(
     *     'name'   => '停用管理員',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '停用管理員',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '0');
            if ($result !== false) {
                $this->success("管理員停用成功！", url("user/index"));
            } else {
                $this->error('管理員停用失敗！');
            }
        } else {
            $this->error('數據傳入失敗！');
        }
    }

    /**
     * 啟用管理員
     * @adminMenu(
     *     'name'   => '啟用管理員',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '啟用管理員',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '1');
            if ($result !== false) {
                $this->success("管理員啟用成功！", url("user/index"));
            } else {
                $this->error('管理員啟用失敗！');
            }
        } else {
            $this->error('數據傳入失敗！');
        }
    }
}