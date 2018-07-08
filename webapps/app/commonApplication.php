<?php

// ===========================================================
/**
 * commonApplication
 * アプリケーション共通設定・処理をまとめる
 * @return void
 */
class commonApplication extends Application
{

    protected function registerRoutes()
    {
        $system   = $GLOBALS['gEnvList']['system'];
        /*
        * controller  :
        *                 service : commonService
        *                 view : commonView
        *                 password : recoverPasswordChange or recoverPasswordMail
        *                 require : require_once()
        *                 redirect : header("location : url" )
        *                 other (login, register,questionnaire : require_once() + showAddonPage
        */
        return array(
                '/' => array('controller' => 'comView', 'action' => 'view', 'contents' => 'index'),
                '/index' => array('controller' => 'comView', 'action' => 'redirectUrl', 'path' => '/'),
                '/view/:contents' => array('controller' => 'view', 'action' => ''),
                '/search/:query_code'  => array('controller' => 'comService', 'action' => 'search'),
                '/get/:query_code'  => array('controller' => 'comService', 'action' => 'query'),
                '/getedit/:query_code'  => array('controller' => 'comService', 'action' => 'query', 'method' => '_edit'),
                '/query/:query_code'   =>  array('controller' => 'comService', 'action' => 'query'),
                '/getgroupcode'   =>  array('controller' => 'comService', 'action' => 'query', 'query_code' => 'get_group_code_enc'),
                '/download/:file'   =>  array('controller' => 'comService', 'action' => 'download'),
                '/getfile/:file'   =>  array('controller' => 'comService', 'action' => 'getfile'),
                '/export/:query_code'   =>  array('controller' => 'comService', 'action' => 'export', 'method' => '_export'),
                '/save/:method/:query_code'   =>  array('controller' => 'comService', 'action' => 'query'),
                '/delete/:query_code'   =>  array('controller' => 'comService', 'action' => 'query', 'method' => '_del'),
                '/import/:query_code'   =>  array('controller' => 'comService', 'action' => 'import'),
                '/upload'   =>  array('controller' => 'comService', 'action' => 'upload'),
                '/getpage/:page_code'   =>  array('controller' => 'comService', 'action' => 'query',  'query_code' => 'get_page', 'SYSTEM_CODE' => $system),
                '/getpage/:SYSTEM_CODE/:page_code'   =>  array('controller' => 'comService', 'action' => 'query',  'query_code' => 'get_page'),
                '/gettree/:tree_code'   =>  array('controller' => 'comService', 'action' => 'query',  'query_code' => 'get_treemenu'),
                '/getorganization/:tree_code'   =>  array('controller' => 'comService', 'action' => 'query',  'query_code' => 'get_organization_tree'),

                '/message'   =>  array('controller' => 'comView', 'action' => 'requireUrl', 'path' => 'form/message.html'),
                '/confirm'   =>  array('controller' => 'comView', 'action' => 'requireUrl', 'path' => 'form/confirm.html'),
                '/forbidden'   =>  array('controller' => 'comView', 'action' => 'requireUrl', 'path' => 'page/forbidden.html'),
                '/sessionTimeout'   =>  array('controller' => 'comView', 'action' => 'redirectUrl', 'path' => '/login?error=1'),
                '/authenticate'   =>  array('controller' => 'login', 'action' => 'show'),
                '/logout'   =>  array('controller' => 'logout', 'action' => 'show'),

                //api testtool
                '/v1/testtool/scenario/:scenario/event' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_event"),
                '/v1/testtool/test/:scenario' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_test"),
                '/v1/testtool/test/:test_id/status' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_test_status"),
                '/v1/testtool/test/:test_id/detail' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_test_detail"),
                '/v1/testtool/test/:test_id/detail/:event_id' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_test_detail"),
                '/v1/testtool/test/:test_id/:event_id/capture/diff' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_test_capture_diff"),
                '/v1/testtool/test/:test_id/:event_id/capture' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_test_capture"),
                '/v1/testtool/test/:test_id/:event_id/error' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_test_error"),
                '/v1/testtool/scenario/:scenario' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario"),
                '/v1/testtool/scenario/group/:scenario_group' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_group"),
                '/v1/testtool/test/group/:scenario_group' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_group_test"),
                '/v1/testtool/scenario/:scenario/start' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_start"),
                '/v1/testtool/scenario/group/:scenario_group/start' =>  array("subdir" => "v1", "controller" => "testtool", "action" => "scenario_group_start"),


                // Default Routing
                '/v1/:controller/:action'   =>  array( "subdir" => "v1"),
                '/:controller/:action'   =>  array("subdir" => "v1"),
                '/:contents'   =>  array('controller' => 'comView', 'action' => 'view'),
        );
    }

    protected function configure()
    {
        // $this->db_manager->connect('master', array(
        //     'dsn'      => 'mysql:dbname=mini_blog;host=localhost;charset=utf8',
        //     'user'     => 'root',
        //     'password' => '',
        // ));
    }
}
