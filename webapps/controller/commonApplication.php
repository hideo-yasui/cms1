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
		$system   = $this->system_info["SYSTEM_CODE"];
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
				'/get/:query_code'  => array('controller' => 'comService', 'action' => 'query','cache'=>'-'),
				'/getedit/:query_code'  => array('controller' => 'comService', 'action' => 'query', 'method' => 'edit'),
				'/getedit/:query_code/:ID'  => array('controller' => 'comService', 'action' => 'query', 'method' => 'edit'),
				'/query/:query_code'   =>  array('controller' => 'comService', 'action' => 'query'),
				'/getgroupcode'   =>  array('controller' => 'comService', 'action' => 'query', 'query_code' => 'get_group_code_enc','cache'=>'-', 'system'=>'control'),
				'/download/:file'   =>  array('controller' => 'comService', 'action' => 'download','cache'=>'-'),
				'/getfile/:file'   =>  array('controller' => 'comService', 'action' => 'getfile','cache'=>'-'),
				'/export/:query_code'   =>  array('controller' => 'comService', 'action' => 'export', 'method' => 'export','cache'=>'-'),
				'/save/:method/:query_code'   =>  array('controller' => 'comService', 'action' => 'query','cache'=>'-'),
				'/delete/:query_code'   =>  array('controller' => 'comService', 'action' => 'query', 'method' => 'del','cache'=>'-'),
				'/import/:query_code'   =>  array('controller' => 'comService', 'action' => 'import'),
				'/upload'   =>  array('controller' => 'comService', 'action' => 'upload'),
				'/getpage/:page_code'   =>  array('controller' => 'comService', 'action' => 'query',  'query_code' => 'get_page', 'system'=>'control', 'cache'=>'-'),
				'/gettree/:tree_code'   =>  array('controller' => 'comService', 'action' => 'query',  'query_code' => 'get_treemenu', 'system'=>'control', 'cache'=>'-'),

				'/forbidden'   =>  array('controller' => 'comView', 'action' => 'requireUrl', 'path' => 'page/control/forbidden.php'),
				'/sessionTimeout'   =>  array('controller' => 'comView', 'action' => 'redirectUrl', 'path' => '/login?error=1'),
				//'/authenticate'   =>  array('controller' => 'login', 'action' => 'show'),
				'/authenticate' => array('controller' => 'comView', 'action' => 'login'),
				'/auth' => array('controller' => 'comService', 'action' => 'auth'),
				'/auth/clear' => array('controller' => 'comService', 'action' => 'auth_clear'),
				'/logout'   =>  array('controller' => 'comView', 'action' => 'logout'),

				// Default Routing
				'/v1/:controller/:action'   =>  array( "subdir" => "v1"),
				'/:controller/:action'   =>  array("subdir" => ""),
				'/:SYSTEM_CODE/:contents'   =>  array('controller' => 'comView', 'action' => 'view'),
				'/:contents'   =>  array('controller' => 'comView', 'action' => 'view'),
		);
	}

	protected function configure()
	{
	}
}
