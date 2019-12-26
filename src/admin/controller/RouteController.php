<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老貓 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\RouteModel;
use cmf\controller\AdminBaseController;
use think\Db;

class RouteController extends AdminBaseController
{

    /**
     * 路由規則列表
     * @adminMenu(
     *     'name'   => 'URL美化',
     *     'parent' => 'admin/Setting/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => 'URL規則管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        global $CMF_GV_routes;
        $routeModel = new RouteModel();
        $routes     = Db::name('route')->order("list_order asc")->select();
        $routeModel->getRoutes(true);
        unset($CMF_GV_routes);
        $this->assign("routes", $routes);
        return $this->fetch();
    }

    /**
     * 添加路由規則
     * @adminMenu(
     *     'name'   => '添加路由規則',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加路由規則',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 添加路由規則提交
     * @adminMenu(
     *     'name'   => '添加路由規則提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加路由規則提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        $data       = $this->request->param();
        $routeModel = new RouteModel();
        $result     = $this->validate($data, 'Route');
        if ($result !== true) {
            $this->error($result);
        }
        $routeModel->allowField(true)->save($data);

        $this->success("添加成功！", url("Route/index", ['id' => $routeModel->id]));
    }

    /**
     * 路由規則編輯
     * @adminMenu(
     *     'name'   => '路由規則編輯',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '路由規則編輯',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $id    = $this->request->param("id", 0, 'intval');
        $route = Db::name('route')->where('id', $id)->find();
        $this->assign($route);
        return $this->fetch();
    }

    /**
     * 路由規則編輯提交
     * @adminMenu(
     *     'name'   => '路由規則編輯提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '路由規則編輯提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $data       = $this->request->param();
        $routeModel = new RouteModel();
        $result     = $this->validate($data, 'Route');
        if ($result !== true) {
            $this->error($result);
        }
        $routeModel->allowField(true)->isUpdate(true)->save($data);

        $this->success("保存成功！", url("Route/index"));
    }

    /**
     * 路由規則刪除
     * @adminMenu(
     *     'name'   => '路由規則刪除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '路由規則刪除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        RouteModel::destroy($id);

        $this->success("刪除成功！");
    }

    /**
     * 路由規則禁用
     * @adminMenu(
     *     'name'   => '路由規則禁用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '路由規則禁用',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id             = $this->request->param("id", 0, 'intval');
        $data           = [];
        $data['status'] = 0;
        $data['id']     = $id;
        $routeModel     = new RouteModel();

        $routeModel->isUpdate(true)->save($data);
        $this->success("禁用成功！");
    }

    /**
     * 路由規則啟用
     * @adminMenu(
     *     'name'   => '路由規則啟用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '路由規則啟用',
     *     'param'  => ''
     * )
     */
    public function open()
    {
        $id             = $this->request->param("id", 0, 'intval');
        $data           = [];
        $data['status'] = 1;
        $data['id']     = $id;
        $routeModel     = new RouteModel();

        $routeModel->isUpdate(true)->save($data);
        $this->success("啟用成功！");
    }

    /**
     * 路由規則排序
     * @adminMenu(
     *     'name'   => '路由規則排序',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '路由規則排序',
     *     'param'  => ''
     * )
     */
    public function listOrder()
    {
        $routeModel = new RouteModel();
        parent::listOrders($routeModel);
        $this->success("排序更新成功！");
    }

    /**
     * 選擇 URL
     * @adminMenu(
     *     'name'   => '選擇URL',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '選擇URL',
     *     'param'  => ''
     * )
     */
    public function select()
    {
        $routeModel = new RouteModel();
        $urls       = $routeModel->getAppUrls();

        $this->assign('urls', $urls);
        return $this->fetch();
    }

    function _suggest_url($action, $url)
    {
        $actionArr = explode('/', $action);

        $params = array_keys($url['vars']);

        $urlDepr1Params = [];

        $urlDepr2Params = [];

        if (!empty($params)) {

            foreach ($params as $param) {
                if (empty($url['vars'][$param]['require'])) {
                    array_push($urlDepr1Params, "[:$param]");
                } else {
                    array_push($urlDepr1Params, ":$param");
                }

                array_push($urlDepr2Params, htmlspecialchars('<') . $param . htmlspecialchars('>'));
            }

        }

        if ($actionArr[2] == 'index') {
            $actionArr[1] = cmf_parse_name($actionArr[1]);
            return empty($params) ? $actionArr[1] . '$' : ($actionArr[1] . '/' . implode('/', $urlDepr1Params) /*. '或' . $actionArr[1] . '-' . implode('-', $urlDepr2Params)*/);
        } else {
            $actionArr[2] = cmf_parse_name($actionArr[2]);
            return empty($params) ? $actionArr[2] . '$' : ($actionArr[2] . '/' . implode('/', $urlDepr1Params) /*. '或' . $actionArr[2] . '-' . implode('-', $urlDepr2Params)*/);
        }

    }

    function _url_vars($url)
    {
        if (!empty($url['vars'])) {
            return implode(',', array_keys($url['vars']));
        }

        return '';
    }

}