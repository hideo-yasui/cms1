$(function(){
	//タブ部分のカレントタブの表示設定
	//履歴移動時のイベント＝ページ表示初期処理
	window.onpopstate=function(e){
		pageInit();
	};
	window.onresize = function(){
		setChart(_cacheData["pageId"],_cacheData["dataSource"], _cacheData["data"]);
	}
	$(".section--result__body").hide();
	$("#result.section--result__body").show();

	//タブとボタンのURL設定
	$("header .nav--tab__item a[for], .result__footer_btn a[for]").each(function(){
		var pageId = $(this).attr("for");
		var _kitIdStr = service.getQueryParam("kit");
		$(this).attr("href", "/result?p="+pageId+"&kit="+_kitIdStr);
	});

	//タブのクリックとボタンのクリックイベント
	$("header .nav--tab__item a[for], .result__footer_btn a[for]").on("click", function(e){
		e.preventDefault();
		var pageId = $(this).attr("for");
		var _kitIdStr = service.getQueryParam("kit");
		history.pushState(null,null, "/result?p="+pageId+"&kit="+_kitIdStr);
		pageInit();
	});

	//モバイル端末用のselect変更イベント（タブの替わり）
	$(".nav--tab__select select").on("change", function(){
		var pageId = $(this).val();
		var _kitIdStr = service.getQueryParam("kit");
		history.pushState(null,null, "/result?p="+pageId+"&kit="+_kitIdStr);
		pageInit();
	});

	pageInit();
});
//ページ表示初期処理
function pageInit(){
	base.setQueryParam();
	var kitIdStr = service.getQueryParam("kit");
	var pageId = service.getQueryParam("p");
	var _hash =  service.getQueryParam("_hash");
	if(util.isEmpty(pageId)) pageId = "result";
	//各サブページの表示切替
	$(".section--result__body").hide();
	$("#"+pageId+".section--result__body").show();
	//タブの状態の切り替え
	$("header .nav--tab__item").removeClass("is--active");
	$("header .nav--tab__item a[for="+pageId+"]").parent().addClass("is--active");
	//モバイル端末用のselectに値設定（タブの替わり）
	$(".nav--tab__select select").val(pageId);
	if(location.href.indexOf("#") < 0 ){
		//切り替えた場合は、画面をスクロールトップにする(URLに#ハッシュがない場合）
		$('body, html').scrollTop(0);
	}
	if(!util.isEmpty(_hash)) {
		$("body, html").animate({scrollTop:$('*[name='+_hash+']').offset().top});
	}
	if(!util.isEmpty(kitIdStr)) showResult(kitIdStr);
	_cacheData["pageId"] = pageId;
}
