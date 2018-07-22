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
					<!-- /.card-header -->
					<div id="listTable" class="card-body">
						<table class="table table-bordered table-striped">
						</table>
					</div>


					<div class="card-footer">
						<ul class="mailbox-attachments clearfix">
							<li class="active">
								<div class="panel-badge">
									<span class="right badge badge-danger float-right mx-1 text-lg">99</span>
								</div>
								<h5><i class="fa fa-tag mr-1"></i>3.6牛乳</h5>
								<div class="mailbox-attachment-icon has-img">
									<img src="/img/reizo/other/900.png" alt="Attachment">
									<h4 class="icon-label w-100 h-100">
										<div class="text-success text-xl"><i class="fa fa-check-circle"></i></div>
									</h4>
								</div>
								<div class="mailbox-attachment-info">
									<div class="progress my-1">
										<div class="progress-bar bg-primary" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%">
											<span class="sr-only"></span>
										</div>
									</div>
									<span class="mailbox-attachment-size">
										<i class="fa fa-calendar-times mr-1"></i>2018/09/01
										<button href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-edit"></i></button>
										<button href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-trash-alt"></i></button>
										<button href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-utensils"></i></button>
									</span>
								</div>
							</li>
							<li>
								<div class="panel-badge">
									<span class="right badge badge-danger float-right mx-1 text-lg">99</span>
								</div>
								<span class="mailbox-attachment-icon has-img"><img src="/img/reizo/seafood/201.png" alt="Attachment"></span>
								<div class="mailbox-attachment-info">
									<div><a href="#" class="mailbox-attachment-name"><i class="fa fa-tag mr-1"></i>マグロ刺身</a></div>
									<div class="progress mb-1">
										<div class="progress-bar bg-danger" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100" style="width: 10%">
											<span class="sr-only"></span>
										</div>
									</div>
									<span class="mailbox-attachment-size">
										<i class="fa fa-calendar-times mr-1"></i>2018/09/01</a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-trash-alt"></i></a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-utensils"></i></a>
									</span>
								</div>
							</li>
							<li>
								<div class="panel-badge">
									<span class="right badge badge-danger float-right mx-1 text-lg">99</span>
								</div>
								<span class="mailbox-attachment-icon has-img"><img src="/img/reizo/meat/100.png" alt="Attachment"></span>
								<div class="mailbox-attachment-info">
									<div><a href="#" class="mailbox-attachment-name"><i class="fa fa-tag mr-1"></i>豚挽き肉</a></div>
									<div class="progress mb-1">
										<div class="progress-bar bg-warning" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%">
											<span class="sr-only"></span>
										</div>
									</div>
									<span class="mailbox-attachment-size">
										<i class="fa fa-calendar-times mr-1"></i>2018/09/01</a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-trash-alt"></i></a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-utensils"></i></a>
									</span>
								</div>
							</li>
							<li>
								<div class="panel-badge">
									<span class="right badge badge-danger float-right mx-1 text-lg">99</span>
								</div>
								<span class="mailbox-attachment-icon has-img"><img src="/img/reizo/meat/101.png" alt="Attachment"></span>
								<div class="mailbox-attachment-info">
									<div><a href="#" class="mailbox-attachment-name"><i class="fa fa-tag mr-1"></i>豚ロース</a></div>
									<div class="progress mb-1">
										<div class="progress-bar bg-warning" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%">
											<span class="sr-only"></span>
										</div>
									</div>
									<span class="mailbox-attachment-size">
										<i class="fa fa-calendar-times mr-1"></i>2018/09/01</a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-trash-alt"></i></a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-utensils"></i></a>
									</span>
								</div>
							</li>
							<li>
								<div class="panel-badge">
									<span class="right badge badge-danger float-right mx-1 text-lg">99</span>
								</div>
								<span class="mailbox-attachment-icon has-img"><img src="/img/reizo/seafood/200.png" alt="Attachment"></span>
								<div class="mailbox-attachment-info">
									<div><a href="#" class="mailbox-attachment-name"><i class="fa fa-tag mr-1"></i>赤魚干物</a></div>
									<div class="progress mb-1">
										<div class="progress-bar bg-warning" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%">
											<span class="sr-only"></span>
										</div>
									</div>
									<span class="mailbox-attachment-size">
										<i class="fa fa-calendar-times mr-1"></i>2018/09/01</a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-trash-alt"></i></a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-utensils"></i></a>
									</span>
								</div>
							</li>
							<li>
								<div class="panel-badge">
									<span class="right badge badge-danger float-right mx-1 text-lg">99</span>
								</div>
								<span class="mailbox-attachment-icon has-img"><img src="/img/reizo/fruit/302.png" alt="Attachment"></span>
								<div class="mailbox-attachment-info">
									<div><a href="#" class="mailbox-attachment-name"><i class="fa fa-tag mr-1"></i>ぶどう</a></div>
									<div class="progress mb-1">
										<div class="progress-bar bg-warning" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%">
											<span class="sr-only"></span>
										</div>
									</div>
									<span class="mailbox-attachment-size">
										<i class="fa fa-calendar-times mr-1"></i>2018/09/01</a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-trash-alt"></i></a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-utensils"></i></a>
									</span>
								</div>
							</li>
							<li>
								<div class="panel-badge">
									<span class="right badge badge-danger float-right mx-1 text-lg">99</span>
								</div>
								<span class="mailbox-attachment-icon has-img"><img src="/img/reizo/fruit/301.png" alt="Attachment"></span>
								<div class="mailbox-attachment-info">
									<div><a href="#" class="mailbox-attachment-name"><i class="fa fa-tag mr-1"></i>いちご（パック）</a></div>
									<div class="progress mb-1">
										<div class="progress-bar bg-warning" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%">
											<span class="sr-only"></span>
										</div>
									</div>
									<span class="mailbox-attachment-size">
										<i class="fa fa-calendar-times mr-1"></i>2018/09/01</a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-trash-alt"></i></a>
										<a href="#" class="btn btn-default btn-sm float-right"><i class="fa fa-utensils"></i></a>
									</span>
								</div>
							</li>
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
