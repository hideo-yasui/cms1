<?php view($data["_directory"]["meta"]."start.php", $data);?>

</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
	<?php view($data["_directory"]["partial"]."navbar.php", $data);?>

	<?php view($data["_directory"]["partial"]."tree_view.php", $data);?>

	<!-- Content Wrapper. Contains page content -->
	<div class="content-wrapper">
		<?php view($data['contents'], $data);?>
	</div>
	<!-- /.content-wrapper -->
	<?php view($data["_directory"]["partial"]."footer.php", $data);?>

	<!-- /.control-sidebar -->

</div>
<!-- ./wrapper -->
<?php view($data["_directory"]["meta"]."end.php", $data);?>
