<?php view($data["_directory"]["meta"]."start.php", $data);?>

<script>
$(function(){
	$(function(){
		//setUserInfo();
		//listPageSetting(base.pageCode);
	});

	function listPageSetting(pagecode){
		if(!pagecode) return;
		service.getAjax(true, "/getpage/"+pagecode, {},
			function(result, st, xhr) {
				var param = result["data"][0];
				base.setTitle(param["NAME"]);
				var p = $("<p></p>");
				p.html(param["OPTION_STRING"]);
				var option = p.html();
				if(option && option!="" && option.indexOf(":")>=0) {
					option = JSON.parse("{"+option+"}");
					$(".treeview").each(function(){
						base.treeInit(this,option["tree_type"], option["tree_code"], option["onload"]);
					});
					$(".nav--header__menu ul li").hide();
					for (var i=0;i<option["nav_header"].length;i++){
						if(util.isEmpty(option["nav_header"][i])) continue;
						$(".nav--header__menu ul li#"+option["nav_header"][i]).show();
					}
				}
			},
			function(xhr, st, err) {
				//alert("listPageSetting\n"+err.message+"\n"+xhr.responseText);
			}
		);
	}
});

</script>

</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
	<?php view($data["_directory"]["partial"]."navbar.php", $data);?>

	<?php view($data["_directory"]["partial"]."tree_view.php", $data);?>

	<!-- Content Wrapper. Contains page content -->
	<div class="content-wrapper">
		<?php view($data["_directory"]["partial"]."list_nav.php", $data);?>
		<?php view($data['contents'], $data);?>
	</div>
	<!-- /.content-wrapper -->
	<?php view($data["_directory"]["partial"]."footer.php", $data);?>

	<!-- /.control-sidebar -->

</div>
<!-- ./wrapper -->
<?php view($data["_directory"]["meta"]."end.php", $data);?>
