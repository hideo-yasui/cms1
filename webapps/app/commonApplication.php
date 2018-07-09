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
                //'/authenticate'   =>  array('controller' => 'login', 'action' => 'show'),
                '/authenticate' => array('controller' => 'comView', 'action' => 'login'),
                '/logout'   =>  array('controller' => 'comView', 'action' => 'logout'),

                // Default Routing
                '/v1/:controller/:action'   =>  array( "subdir" => "v1"),
                '/:controller/:action'   =>  array("subdir" => "v1"),
                '/:SYSTEM_CODE/:contents'   =>  array('controller' => 'comView', 'action' => 'view'),
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
