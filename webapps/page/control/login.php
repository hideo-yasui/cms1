<html>
<head>
	<title>ログイン | システム管理</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="target-densitydpi=device-dpi, width=768, maximum-scale=1.0, user-scalable=yes">
	<meta charset="utf-8">
	<link rel="icon" href="/images/main/favicon.png" type="image/ico">
	<link rel="stylesheet" media="all" href="/css/lib/jquery-ui.css">
	<link rel="stylesheet" media="all" href="/css/application.css<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>">
	<link rel="stylesheet" media="all" href="/css/treeview.css<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>">
	<link rel="stylesheet" media="all" href="/css/icon.css<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>">
<style>
.mainform{
	width: 580px;
	margin: 120px auto;
	box-shadow: 0px 2px 14px 0px rgba(0, 0, 0, 0.05), 0px 2px 4px 0px rgba(0, 0, 0, 0.08);
	padding-top: 72px;
	position: relative;
}
.btn--half{
	width: 40%;
	height: 60px;
	line-height: 60px;
	font-size: 16px;
	font-size: 1.6rem;
	margin : 0 2% 0 7% ;
}
</style>
<script src="/js/lib/jquery.js"></script>
<script src="/js/lib/jquery-ui.js"></script>
<script src="/js/lib/jquery.jpostal.js"></script>
<script src="/js/lib/datepicker-ja.js"></script>
<script src="/js/lib/timsort.js"></script>
<script src="/js/common/util.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/common/treeView.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/common/listTable.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/base/fileUI.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/base/dom.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/base/service.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/base/front.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/base.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/common.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script>
$(function(){
	$("input[name=login_id]").focus();
	$("form").submit(function(e){
		util.clearLocalData();
	});
});
</script>
	</head>
	<body>
		<div class="wrp">
			<div class="container">
				<div class="hide">
					<div id="message" style="" type="dialog">
					</div>
					<div id="loading" type="dialog">
						<div class="loadtext">
							データを読み込んでいます。<br>お待ちください
						</div>
					</div>
					<div id="subDialog" style="" type="dialog">
					</div>
				</div>
				<main class="mainform">
					<h1 class="" style="padding-left:32px;font-size:44px;">
						<img src="/images/icon/3477.png" width=50 style="vertical-align:sub;"/>
						システム管理
					</h1>
					<div id="page1" class="main--entrance__body">
						<header class="main--entrance__header">
							<h1>ログイン</h1>
						</header>
						<section class="login">
						<form enctype="application/x-www-form-urlencoded" method="post" name="LoginForm" action="/authenticate">
							<input type="hidden" name="token" value="<?php echo $data['token'] ?>">
							<div class="form form--entrance">
								<dl class="form__wrp">
									<dd class="form__dd">
										<div class="form--text">
											<span class="icon--account"></span>
											<input type="text" name="login_id" class="form--text__field" placeholder="ユーザーID" minlength="3" maxlength="32" inputtype="hankaku" required="true">
										</div>
									</dd>
								</dl>
								<dl class="form__wrp">
									<dd class="form__dd">
										<div class="form--text">
											<span class="icon--lock"></span>
											<input type="password" name="password" class="form--text__field" placeholder="パスワード" accesskey="enter" minlength="8" maxlength="32" inputtype="hankaku" required="true">
										</div>
										<div class="form__error" >
											<span class="form__required">
										<?php if (isset($data['message'])){
				 							echo $data['message'];
										}?></span>
										</div>
									</dd>
								</dl>
								<div class="">
									<button type="submit" class="btn btn--large">
										<span class="icon--login"></span>ログイン
									</button>
								</div>
							</div>
						</form>
						</section>
					</div>
				</main>
			</div>
			<footer class="footer">
				<a href="https://www.cykinso.co.jp/" target="_blank"><?php echo $data["config"]["COPYRIGHT"]; ?></a>
			</footer>
		</div>
	</body>
</html>
