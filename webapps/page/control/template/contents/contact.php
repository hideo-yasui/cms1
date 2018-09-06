<!-- Content Wrapper. Contains page content -->
<div class="" style="width:95%;margin:0 auto;">
	<div class="" style="min-height:768px;">
		<h2 class="main-title">お申込み・お問い合わせ</h2>
		<h6 class="mb-4 ml-4">
			当塾に関するお問い合わせについて、以下のフォームにてお受けいたします。<br>
		</h6>
		<!-- Main content -->
		<section id="edit" class="content" style="">
			<div class="container-fluid">
				<div class="row">
					<!-- left column -->
					<div class="col-md">
						<!-- general form elements -->
						<!-- /.card-header -->
						<div class="card">
							<!-- form                -->
							<!-- .card-header -->
							<div class="card-header">
								<h5 class="card-title content-sub-title">お問い合わせフォーム</h5>
							</div>
							<!-- /.card-header -->
							<form role="form" enctype="multipart/form-data">
								<div class="card-body content-sub-body">
									<div class="card-body">
										<div class="row">
											<div class="col-lg-6">
												<label for="user_name">生徒氏名
													<span class="right badge badge-danger ml-1">必須</span>
												</label>
												<div class="form-group">
													<input type="text" placeholder="例:鈴木　一郎" inputtype="zenkaku" name="user_name" required="true" class="form-control">
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-6">
												<label for="user_kana">フリガナ
													<span class="right badge badge-danger ml-1">必須</span>
												</label>
												<div class="form-group">
													<input type="text" placeholder="例 :スズキ　イチロウ" inputtype="zenkakukana" name="user_kana" required="true" class="form-control">
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-6">
												<label for="user_grade">生徒学年
													<span class="right badge badge-danger ml-1">必須</span>
												</label>
												<div class="form-group">
													<select class="form-control select2 select2-hidden-accessible" alt="grade" accesskey="m_code" type="select" name="user_grade" required="true" style="width:100%;" tabindex="-1" aria-hidden="true"></select>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-6">
												<label for="user_parent_name">保護者氏名
													<span class="right badge badge-danger ml-1">必須</span>
												</label>
												<div class="form-group">
													<input type="text" placeholder="例:鈴木　太郎" inputtype="zenkaku" name="user_parent_name" required="true" class="form-control">
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-6">
												<label for="contact_tel">ご連絡先(ハイフン不要）</label>
												<div class="form-group">
													<input type="text" placeholder="01022223333 " inputtype="number" name="contact_tel" class="form-control">
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-6">
												<label for="contact_tel_time">ご連絡できる時間帯</label>
												<div class="form-group">
													<select class="form-control select2 select2-hidden-accessible" alt="hours" accesskey="m_code" type="select" name="contact_tel_time" style="width:100%;" tabindex="-1" aria-hidden="true"></select>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-6">
												<label for="contact_email">メールアドレス
													<span class="right badge badge-danger ml-1">必須</span>
												</label>
												<div class="form-group">
													<input type="text" placeholder="xxxx@xxxx.xxx.xxx" inputtype="email" name="contact_email" required="true" class="form-control">
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-6">
												<label for="contact_email_confirm">メールアドレス（確認用）
													<span class="right badge badge-danger ml-1">必須</span>
												</label>
												<div class="form-group">
													<input type="text" placeholder="xxxx@xxxx.xxx.xxx" inputtype="email" equal="contact_email" equalerror="入力したメールアドレスが一致しません。" name="contact_email_confirm" required="true" class="form-control">
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-6">
												<label for="contact_type">お問い合わせ項目
													<span class="right badge badge-danger ml-1">必須</span>
												</label>
												<div class="form-group">
													<select class="form-control select2 select2-hidden-accessible" alt="contact_type" accesskey="m_code" type="select" name="contact_type" required="true" style="width:100%;" tabindex="-1" aria-hidden="true"></select>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-6">
												<label for="contact_body">お問い合わせ内容（2000文字まで）
													<span class="right badge badge-danger ml-1">必須</span>
												</label>
												<div class="form-group">
													<textarea class="textarea col-12" rows="4" type="textarea" placeholder="ご質問内容をご入力ください" maxlength="2000" name="contact_body" required="true"></textarea>
												</div>
											</div>
										</div>
									</div>
									<div class="card-body text-center">
										<button type="button" class="btn btn-primary mr-2" accesskey="_post">
											<i class="fa fa-check-circle mr-1"></i>お問い合わせ内容を送信
										</button>
									</div>
								</div>
								<!-- /card-body -->
								<div class="card-footer content-sub-footer"></div>
								<!-- /.card-footer -->
							</form>
							<!-- /form                -->
						</div>
						<!-- /.card -->
					</div>
				</div>
				<!-- /.container-fluid -->
			</div>
		</section>
		<!-- Main content -->
		<section id="edit_conirm" class="content" style="display:none;">
		  <div class="container-fluid">
		    <div class="row">
		      <!-- left column -->
		      <div class="col-md">
		        <!-- general form elements -->
		        <!-- /.card-header -->
		        <div class="card">
		          <!-- form                -->
		          <!-- .card-header -->
		          <div class="card-header">
		            <h5 class="card-title content-sub-title">お問い合わせ内容ご確認</h5>
		          </div>
		          <!-- /.card-header -->
		          <form role="form" enctype="multipart/form-data">
								<table class="table table-striped mb-2">
									<tbody>
										<tr>
											<tr>
												<th class="bg-success">生徒氏名</th>
												<td>
													<span class="text-lg" id="user_name"></span>
													（<span class="text-sm" id="user_kana"></span>）　様
												</td>
											</tr>
											<tr>
												<th class="bg-success">生徒学年</th>
												<td>
													<span class="text-lg" alt="grade" accesskey="m_code" id="user_grade"></span>
												</td>
											</tr>
											<tr>
												<th class="bg-success">保護者氏名</th>
												<td>
													<span class="text-lg" id="user_parent_name"></span>　様
												</td>
											</tr>
											<tr>
												<th class="bg-success">メールアドレス</th>
												<td>
													<span class="" id="contact_email"></span>
												</td>
											</tr>
											<tr>
												<th class="bg-success">ご連絡先</th>
												<td>
													<span class="text-lg" id="contact_tel"></span>
													　　<span class="text-sm" id="contact_tel_time"　alt="hours" accesskey="m_code"></span>
												</td>
											</tr>
											<tr>
												<th class="bg-success">お問い合わせ項目</th>
												<td>
													<span class="text-lg" id="contact_type" alt="contact_type" accesskey="m_code"></span>
												</td>
											</tr>
											<tr>
												<th class="bg-success">お問い合わせ内容</th>
												<td style="min-height:200px;">
													<span class="text-lg" id="contact_body"></span>
												</td>
											</tr>
									</tbody>
								</table>
								<div class="card-body text-center">
									<button type="button" class="btn btn-primary mr-2" accesskey="_send">
										<i class="fa fa-envelope mr-1"></i>この内容で送信する
									</button>
									<button type="button" class="btn btn-outline-secondary mr-2" accesskey="_cancel">
										<i class="fa fa-backspace mr-1"></i>内容の編集に戻る
									</button>
								</div>
		          </form>
		        <!-- /.card -->
		      </div>
		    </div>
		    <!-- /.container-fluid -->
		  </div>
		</section>

		<section id="edit_send" class="content" style="display:none;">
			<div class="container-fluid">
				<div class="row">
					<!-- left column -->
					<div class="col-md">
					<!-- general form elements -->
					<!-- /.card-header -->
					<div class="card">
						<!-- form                -->
						<!-- .card-header -->
						<div class="card-header">
							<h5 class="card-title content-sub-title">お問い合わせを承りました</h3>
						</div>
						<!-- /.card-header -->
						<form role="form" enctype="multipart/form-data">
							<div class="card-body content-sub-body m-2">
									お問い合わせいただきました内容を確認後、担当者より、
									<br>
									ご入力いただいたメールアドレス宛に、改めてご連絡いたします。
									<br>
									<br>
									一週間以上、こちらからの返答がない場合には、お手数ですがお電話にてご確認をお願い致します。
									<table class="table table-striped m-2">
										<tbody>
											<tr>
												<tr>
													<th class="bg-success">お電話でのご連絡</th>
													<td class="text-lg"><b style="color:#FF6600;">090-1234-4678</b><br>
														月曜～土曜 受付時間 9:00～22:00
													</td>
												</tr>
										</tbody>
									</table>
									<div class="card-body text-center">
										<a class="btn mr-2" href="./">
											<i class="fa fa-home mr-1"></i>HOMEに戻る
										</a>
									</div>
							</div>
						</form>
						<!-- /form                -->
					</div>
					<!-- /.card -->
				</div>
			</div><!-- /.container-fluid -->
		</section>
		<!-- /.content -->
	</div>
</div>
<script>
$(function(){
	var data = {
		"user_name" : "田中　恵一",
		"user_kana" : "たなか　けいいち",
		"user_parent_name" : "田中　洋子",
		"contact_email" : "yasumihosi@gmail.com",
		"contact_email_confirm" : "yasumihosi@gmail.com",
		"contact_body" : "子どもの教育\nこのままでいいか相談したい\n宜しくお願い致します。"
	};
	base.pageOpen("edit");
	base.pageSettinged("edit", null);
	$(".btn[accesskey=_post]").on("click", function(){
		console.log("btn._post");
		if(!front.validateFormValue("edit")) return false;
		var editdata = front.getFormValue("edit");
		base.pageSettinged("edit_conirm", editdata);
		base.pageClose("edit");
		base.pageClose("edit_send");
		window.scroll(0,0);
		base.pageOpen("edit_conirm");
		$(".btn[accesskey=_cancel]").on("click", function(){
			console.log("btn._cancel");
			window.scroll(0,0);
			base.pageClose("edit_conirm");
			base.pageClose("edit_send");
			base.pageOpen("edit");
		});
		$(".btn[accesskey=_send]").on("click", function(){
			console.log("btn._send");
			var editdata = front.getFormValue("edit");
			service.postAjax("/query/post_contact", editdata,
				function(result, st, xhr) {
					window.scroll(0,0);
					base.pageClose("edit_conirm");
					base.pageClose("edit");
					base.pageOpen("edit_send");
					service.getAjax(true, "/mail", {});
				},
				function(xhr, st, err) {
					/*error*/
				}
			);
		});
	});
})
</script>
