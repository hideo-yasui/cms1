<?php view($data["_directory"]["meta"]."start.php", $data);?>
<style>
.floating-menu {
	display: none;
	 position: fixed;
	 bottom: 0px;
	 right: 0px;
	 opacity:0.8;
	 padding:2%;
}
h2.main-title {
	position: relative;
	color: #158b2b;
	font-size: 24px;
	padding: 10px 0;
	text-align: center;
	margin: 1.5em 0;
}

h2.main-title:after {
	content: "";
	position: absolute;
	top: -8px;
	left: 50%;
	width: 150px;
	height: 58px;
	border-radius: 50%;
	border: 5px solid #a6ddb0;
	border-left-color: transparent;
	border-right-color: transparent;
	-moz-transform: translateX(-50%);
	-webkit-transform: translateX(-50%);
	-ms-transform: translateX(-50%);
	transform: translateX(-50%);
}
h2.sub-title{
	color:#CC0000;
	position: relative;
	padding: 0.25em 0;
}
h2.sub-title:after {
	content: "";
	display: block;
	height: 4px;
	background: -moz-linear-gradient(to right, rgb(230, 90, 90), transparent);
	background: -webkit-linear-gradient(to right, rgb(230, 90, 90), transparent);
	background: linear-gradient(to right, rgb(230, 90, 90), transparent);
}
</style>
</head>
<body class="hold-transition ">
<div class="wrapper">
	<?php view($data["_directory"]["partial"]."navbar2.php", $data);?>

	<?php view($data['contents'], $data);?>
	<?php view($data["_directory"]["partial"]."dialog.php", $data);?>
	<!-- /.content-wrapper -->
	<footer class="bg-white text-center p-4 text-sm">
		<div class="row">
			<div class="col-12 bg-info p-2">
				<a class="mr-4" href="./about" ><i class="mr-1 fa fa-chevron-circle-right"></i>教育理念・学習方針</a>
				<a class="mr-4" href="./course" ><i class="mr-1 fa fa-chevron-circle-right"></i>コース・授業料</a>
				<a class="mr-4" href="./flow" ><i class="mr-1 fa fa-chevron-circle-right"></i>入塾までの流れ</a>
				<a class="mr-4" href="./contact" ><i class="mr-1 fa fa-chevron-circle-right"></i>お申込み・お問い合わせ</a>
				<a class="mr-4" href="#access" ><i class="mr-1 fa fa-chevron-circle-right"></i>アクセス</a>
				<a class="mr-4" href="#profile" ><i class="mr-1 fa fa-chevron-circle-right"></i>講師について</a>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
				放課後プログラミング塾.inc
			</div>
		</div>
	</footer>

	<div class="floating-menu">
		<a class="btn bg-secondary btn-scroll-top float-right text-center text-lg" style="width:80px;">
			<i class="fa fa-chevron-circle-up"></i><br>TOP
		</a>
	</div>

	<!-- /.control-sidebar -->
<script>
	$(function() {
		 var _scroll = function() {
				if( $(window).scrollTop() > 100 ) $(".floating-menu").fadeIn();
				else $(".floating-menu").fadeOut();
		 }
		 $(".btn-scroll-top").click(function(){
			 $('body, html').animate({ scrollTop: 0 }, 300);
		 });
		 $(window).scroll(_scroll);
		 _scroll();
	});
</script>
</div>
<!-- ./wrapper -->
<?php view($data["_directory"]["meta"]."end.php", $data);?>
