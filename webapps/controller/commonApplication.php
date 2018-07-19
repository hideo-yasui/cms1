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
				'/get/:query_code'  => array('controller' => 'comService', 'action' => 'query','cache'=>'use'),
				'/getedit/:query_code'  => array('controller' => 'comService', 'action' => 'query', 'method' => 'edit'),
				'/getedit/:query_code/:ID'  => array('controller' => 'comService', 'action' => 'query', 'method' => 'edit'),
				'/query/:query_code'   =>  array('controller' => 'comService', 'action' => 'query'),
				'/getgroupcode'   =>  array('controller' => 'comService', 'action' => 'query', 'query_code' => 'get_group_code_enc','cache'=>'use'),
				'/download/:file'   =>  array('controller' => 'comService', 'action' => 'download','cache'=>'use'),
				'/getfile/:file'   =>  array('controller' => 'comService', 'action' => 'getfile','cache'=>'use'),
				'/export/:query_code'   =>  array('controller' => 'comService', 'action' => 'export', 'method' => 'export','cache'=>'use'),
				'/save/:method/:query_code'   =>  array('controller' => 'comService', 'action' => 'query','cache'=>'clear'),
				'/delete/:query_code'   =>  array('controller' => 'comService', 'action' => 'query', 'method' => 'del','cache'=>'clear'),
				'/import/:query_code'   =>  array('controller' => 'comService', 'action' => 'import'),
				'/upload'   =>  array('controller' => 'comService', 'action' => 'upload'),
				'/getpage/:page_code'   =>  array('controller' => 'comService', 'action' => 'query',  'query_code' => 'get_page', 'SYSTEM_CODE' => $system, 'cache'=>'use'),
				'/getpage/:SYSTEM_CODE/:page_code'   =>  array('controller' => 'comService', 'action' => 'query',  'query_code' => 'get_page', 'cache'=>'use'),
				'/gettree/:tree_code'   =>  array('controller' => 'comService', 'action' => 'query',  'query_code' => 'get_treemenu', 'cache'=>'use'),

				'/message'   =>  array('controller' => 'comView', 'action' => 'requireUrl', 'path' => 'form/message.html'),
				'/confirm'   =>  array('controller' => 'comView', 'action' => 'requireUrl', 'path' => 'form/confirm.html'),
				'/forbidden'   =>  array('controller' => 'comView', 'action' => 'requireUrl', 'path' => 'page/forbidden.html'),
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
