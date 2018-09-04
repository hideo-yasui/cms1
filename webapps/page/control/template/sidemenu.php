<?php view($data["_directory"]["meta"]."start.php", $data);?>

</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
	<?php view($data["_directory"]["partial"]."navbar.php", $data);?>

	<?php view($data["_directory"]["partial"]."tree_view.php", $data);?>


	<!-- Content Wrapper. Contains page content -->
	<div class="content-wrapper">
		<?php view($data["_directory"]["partial"]."list_nav.php", $data);?>

		<!-- Main content -->
		<section id="main" class="content">
			<div class="container-fluid">
				<div class="row">
					<div class="col-12">
						<div class="card">
							<div class="card-header">
								<div class="card-tools">
								</div>
								<div class="card-title">
									<div class="btn-group btn-group-sm float-right">
										<button type="button" class="btn btn-outline-info" accesskey="pagenext">
											<i class="fa fa-angle-right"></i>
										</button>
										<button type="button" class="btn btn-outline-info" accesskey="pageend">
											<i class="fa fa-angle-double-right"></i>
										</button>
									</div>
									<h6 id="pageInfo" class="float-right m-2 btn-label">
										9999 / 9999
									</h6>
									<div class="btn-group btn-group-sm float-right">
										<button type="button" class="btn btn-outline-info" accesskey="pagestart">
											<i class="fa fa-angle-double-left"></i>
										</button>
										<button type="button" class="btn btn-outline-info" accesskey="pageprev">
											<i class="fa fa-angle-left"></i>
										</button>
									</div>
									<div id="buttonmenu" class="float-left">
										<button type="button" class="btn btn-info btn-sm">
											<i class="fa fa-plus"></i>
											<span class="btn-label">追加</span>
										</button>
										<button type="button" class="btn btn-info btn-sm">
											<!-- i class="ion ion-ios-refresh-empty"></i -->
											<i class="fa fa-sync"></i>
											<span class="btn-label">再表示</span>
										</button>
										<button type="button" class="btn btn-info btn-sm">
											<i class="fa fa-download"></i>
											<span class="btn-label">export</span>
										</button>
										<button type="button" class="btn btn-info btn-sm">
											<i class="fa fa-upload"></i>
											<span class="btn-label">import</span>
										</button>
									</div>
								</div>
							</div>
							<?php view($data['contents'], $data);?>
					</div>
				</div>
			</div><!-- /.container-fluid -->
		</section>
		<!-- /.content -->
		<?php view($data["_directory"]["partial"]."subpage.php", $data);?>
		<?php view($data["_directory"]["partial"]."dialog.php", $data);?>
	</div>
	<!-- /.content-wrapper -->
	<?php view($data["_directory"]["partial"]."footer.php", $data);?>

	<!-- /.control-sidebar -->

</div>
<!-- ./wrapper -->
<?php view($data["_directory"]["meta"]."end.php", $data);?>
