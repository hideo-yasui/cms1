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
	top: -30%;
	left: 50%;
	width: 40%;
	height: 160%;
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
	font-size:1.5rem;
}
h2.sub-title:after {
	content: "";
	display: block;
	height: 4px;
	background: -moz-linear-gradient(to right, rgb(230, 90, 90), transparent);
	background: -webkit-linear-gradient(to right, rgb(230, 90, 90), transparent);
	background: linear-gradient(to right, rgb(230, 90, 90), transparent);
}

nav.nav-footer{
   width:100%;
   padding:0px;
}
nav.nav-footer ul{
   width:100%;
   padding:0 2% 0 4%;
}
nav.nav-footer ul li{
   float:left;
   list-style-type:none;
   box-sizing: border-box;
	 border:0px;
	 margin-right:3%;
}
nav.nav-footer ul li a{
   display:block;
	 padding-top:.3rem;
	 padding-bottom:.3rem;
	 /*
   padding:13px 0px 13px 30px;
	 */
}
@media (max-width: 768px) {
	nav.nav-footer ul{
	   width:100%;
	}
	nav.nav-footer ul li{
	   width:50%;
		 border-right:1px solid rgba(0, 0, 0, 0.1);
		 border-bottom:1px solid rgba(0, 0, 0, 0.1);
		 margin:0;
	}
	nav.nav-footer ul li:nth-child(even){
	   border-right:0px;
	}
}
</style>
</head>
<body class="hold-transition ">
<div class="wrapper">
	<?php view($data["_directory"]["partial"]."navbar2.php", $data);?>
	<?php view($data['contents'], $data);?>

</div>
<footer class="bg-info text-center text-sm">
	<div class="row">
		<nav class="nav-footer col-12">
			<ul class="">
				<li><a class="" href="./about" ><i class="mr-1 fa fa-chevron-circle-right"></i>Self-Studyとは？</a></li>
				<li><a class="" href="./course" ><i class="mr-1 fa fa-chevron-circle-right"></i>学習内容・授業料</a></li>
				<li><a class="" href="./flow" ><i class="mr-1 fa fa-chevron-circle-right"></i>入塾までの流れ</a></li>
				<li><a class="" href="./contact" ><i class="mr-1 fa fa-chevron-circle-right"></i>お申込み・お問い合わせ</a></li>
				<li><a class="" href="./faq" ><i class="mr-1 fa fa-chevron-circle-right"></i>よくあるご質問</a></li>
				<li><a class="" href="./#access" ><i class="mr-1 fa fa-chevron-circle-right"></i>アクセス</a></li>
				<li><a class="" href="./#profile"><i class="mr-1 fa fa-chevron-circle-right"></i>講師について</a></li>
			</ul>
		</nav>
		<div class="col-12 bg-info p-2">
			放課後プログラミング塾.inc
		</div>
	</div>
</footer>

<div class="floating-menu">
	<a class="btn bg-secondary btn-scroll-top float-right text-center text-lg" style="width:80px;">
		<i class="fa fa-chevron-circle-up"></i><br>TOP
	</a>
</div>

<?php view($data["_directory"]["partial"]."dialog.php", $data);?>
<!-- ./wrapper -->
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

<?php view($data["_directory"]["meta"]."end.php", $data);?>
