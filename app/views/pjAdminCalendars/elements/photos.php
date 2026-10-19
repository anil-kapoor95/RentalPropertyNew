<div id="photos" class="tab-pane<?php echo $active_tab == 'photos' ? ' active' : NULL;?>">
	<div class="panel-body form-horizontal">
		<div class="panel-body-inner">

			<div class="alert alert-success"><?php __('lblPhotosInfo'); ?></div>

			<form action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCalendars&amp;action=pjActionUpdate" method="post" id="frmCalendarPhotos" class="form pj-form" enctype="multipart/form-data">
				<input type="hidden" name="property_update" value="1" />
				<input type="hidden" name="csrf_token" value="<?php echo pjAppController::getCsrfToken(); ?>" />
				<input type="hidden" name="tab" value="photos" />
				<input type="hidden" name="tab_id" value="13" />
				<input type="hidden" name="delete_photo_id" id="delete_photo_id" value="" />

				<div class="form-group">
					<label class="col-sm-3 control-label" for="photos_files"><?php __('lblUploadPhotos'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<input type="file" name="photos[]" id="photos_files" class="form-control" accept="image/*" multiple="multiple" />
					</div>
				</div>

				<div class="hr-line-dashed"></div>

				<div class="form-group">
					<div class="col-sm-12">
						<?php if (empty($tpl['photo_arr'])) : ?>
						<p class="text-muted"><?php __('lblNoPhotosYet'); ?></p>
						<?php else : ?>
						<div class="row">
							<?php foreach ($tpl['photo_arr'] as $photo) : ?>
							<div class="col-lg-2 col-md-3 col-sm-4 col-xs-6" style="margin-bottom: 20px;">
								<div class="thumbnail" style="margin-bottom: 5px;">
									<a href="<?php echo PJ_INSTALL_URL . $photo['large_path']; ?>" target="_blank">
										<img src="<?php echo PJ_INSTALL_URL . $photo['small_path']; ?>" alt="<?php echo pjSanitize::html($photo['file_name']); ?>" style="width: 100%; height: auto;" />
									</a>
								</div>
								<button type="button" class="btn btn-danger btn-xs btn-block btnDeleteCalendarPhoto" data-id="<?php echo (int) $photo['id']; ?>">
									<i class="fa fa-trash"></i> <?php __('lblDeletePhoto'); ?>
								</button>
							</div>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="hr-line-dashed"></div>

				<div class="clearfix">
					<button type="submit" class="ladda-button btn btn-primary btn-lg btn-phpjabbers-loader pull-left" data-style="zoom-in" style="margin-right: 15px;">
						<span class="ladda-label"><?php __('btnUploadPhotos'); ?></span>
						<?php include $controller->getConstant('pjBase', 'PLUGIN_VIEWS_PATH') . 'pjLayouts/elements/button-animation.php'; ?>
					</button>
					<a type="button" class="btn btn-white btn-lg pull-right" href="<?php echo PJ_INSTALL_URL; ?>index.php?controller=pjAdminCalendars&action=pjActionIndex"><?php __('btnCancel'); ?></a>
				</div>

			</form>

		</div>
	</div>

	<script type="text/javascript">
	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('.btnDeleteCalendarPhoto') : null;
		if (!btn) { return; }
		e.preventDefault();
		pjPopup.confirm("<?php __('lblPhotoDeleteConfirm', false, true); ?>", function () {
			document.getElementById('delete_photo_id').value = btn.getAttribute('data-id');
			document.getElementById('frmCalendarPhotos').submit();
		});
	});
	</script>

</div>
