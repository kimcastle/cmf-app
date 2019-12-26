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

use app\user\model\UserModel;
use cmf\controller\HomeBaseController;

class IndexController extends HomeBaseController
{

    /**
     * 前臺用戶首頁(公開)
     */
    public function index()
    {
        $id        = $this->request->param("id", 0, "intval");
        $userModel = new UserModel();
        $user      = $userModel->where('id', $id)->find();
        if (empty($user)) {
            $this->error("查無此人！");
        }
        $this->assign($user->toArray());
        $this->assign('user',$user);
        return $this->fetch(":index");
    }

    /**
     * 前臺ajax 判斷用戶登錄狀態接口
     */
    function isLogin()
    {
        if (cmf_is_user_login()) {
            $this->success("用戶已登錄",null,['user'=>cmf_get_current_user()]);
        } else {
            $this->error("此用戶未登錄!");
        }
    }

    /**
     * 退出登錄
     */
    public function logout()
    {
        session("user", null);//只有前臺用戶退出
        return redirect($this->request->root() . "/");
    }

}
