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

use think\facade\Validate;
use cmf\controller\HomeBaseController;
use app\user\model\UserModel;

class LoginController extends HomeBaseController
{

    /**
     * 登錄
     */
    public function index()
    {
        $redirect = $this->request->param("redirect");
        if (empty($redirect)) {
            $redirect = $this->request->server('HTTP_REFERER');
        } else {
            if (strpos($redirect, '/') === 0 || strpos($redirect, 'http') === 0) {
            } else {
                $redirect = base64_decode($redirect);
            }
        }
        if(!empty($redirect)){
            session('login_http_referer', $redirect);
        }
        if (cmf_is_user_login()) { //已經登錄時直接跳到首頁
            return redirect($this->request->root() . '/');
        } else {
            return $this->fetch(":login");
        }
    }

    /**
     * 登錄驗證提交
     */
    public function doLogin()
    {
        if ($this->request->isPost()) {
            $validate = new \think\Validate([
                'captcha'  => 'require',
                'username' => 'require',
                'password' => 'require|min:6|max:32',
            ]);
            $validate->message([
                'username.require' => '用戶名不能為空',
                'password.require' => '密碼不能為空',
                'password.max'     => '密碼不能超過32個字符',
                'password.min'     => '密碼不能小於6個字符',
                'captcha.require'  => '驗證碼不能為空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            if (!cmf_captcha_check($data['captcha'])) {
                $this->error(lang('CAPTCHA_NOT_RIGHT'));
            }

            $userModel         = new UserModel();
            $user['user_pass'] = $data['password'];
            if (Validate::is($data['username'], 'email')) {
                $user['user_email'] = $data['username'];
                $log                = $userModel->doEmail($user);
            } else if (cmf_check_mobile($data['username'])) {
                $user['mobile'] = $data['username'];
                $log            = $userModel->doMobile($user);
            } else {
                $user['user_login'] = $data['username'];
                $log                = $userModel->doName($user);
            }
            $session_login_http_referer = session('login_http_referer');
            $redirect                   = empty($session_login_http_referer) ? $this->request->root() : $session_login_http_referer;
            switch ($log) {
                case 0:
                    cmf_user_action('login');
                    $this->success(lang('LOGIN_SUCCESS'), $redirect);
                    break;
                case 1:
                    $this->error(lang('PASSWORD_NOT_RIGHT'));
                    break;
                case 2:
                    $this->error('賬戶不存在');
                    break;
                case 3:
                    $this->error('賬號被禁止訪問系統');
                    break;
                default :
                    $this->error('未受理的請求');
            }
        } else {
            $this->error("請求錯誤");
        }
    }

    /**
     * 找回密碼
     */
    public function findPassword()
    {
        return $this->fetch('/find_password');
    }

    /**
     * 用戶密碼重置
     */
    public function passwordReset()
    {

        if ($this->request->isPost()) {
            $validate = new \think\Validate([
                'captcha'           => 'require',
                'verification_code' => 'require',
                'password'          => 'require|min:6|max:32',
            ]);
            $validate->message([
                'verification_code.require' => '驗證碼不能為空',
                'password.require'          => '密碼不能為空',
                'password.max'              => '密碼不能超過32個字符',
                'password.min'              => '密碼不能小於6個字符',
                'captcha.require'           => '驗證碼不能為空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $captchaId = empty($data['_captcha_id']) ? '' : $data['_captcha_id'];
            if (!cmf_captcha_check($data['captcha'], $captchaId)) {
                $this->error('驗證碼錯誤');
            }

            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }

            $userModel = new UserModel();
            if (Validate::is($data['username'], 'email')) {

                $log = $userModel->emailPasswordReset($data['username'], $data['password']);

            } else if (cmf_check_mobile($data['username'])) {
                $user['mobile'] = $data['username'];
                $log            = $userModel->mobilePasswordReset($data['username'], $data['password']);
            } else {
                $log = 2;
            }
            switch ($log) {
                case 0:
                    $this->success('密碼重置成功', cmf_url('user/Profile/center'));
                    break;
                case 1:
                    $this->error("您的賬戶尚未註冊");
                    break;
                case 2:
                    $this->error("您輸入的賬號格式錯誤");
                    break;
                default :
                    $this->error('未受理的請求');
            }

        } else {
            $this->error("請求錯誤");
        }
    }


}