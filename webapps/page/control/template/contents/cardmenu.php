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

					<div id="listTable" class="card-footer" alt="CardTable">
						<ul class="mailbox-attachments clearfix">
						</ul>
					</div>
					<!-- /.card-footer -->


				</div>
				<!-- /.card -->
			</div>
		</div>
	</div><!-- /.container-fluid -->
</section>
<!-- /.content -->
<?php view($data["_directory"]["partial"]."subpage.php", $data);?>
<?php view($data["_directory"]["partial"]."dialog.php", $data);?>
