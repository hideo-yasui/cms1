<!-- login_box.php start -->
<?php view($data["_directory"]["meta"]."start.php", $data);?>
</head>
<body class="hold-transition login-page">
<div class="login-box">
	<div class="login-logo">
	<a href="./"><b><?php echo $data["_system"]["NAME"]; ?></b></a>
	</div>
	<!-- /.login-logo -->
	<div class="card">
	<?php view($data['contents'], $data);?>
  </div>
</div>
<!-- /.login-box -->
<?php view($data["_directory"]["meta"]."end.php", $data);?>
<!-- login_box.php end -->
