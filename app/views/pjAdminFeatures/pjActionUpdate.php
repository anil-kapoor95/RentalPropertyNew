<form action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminFeatures&amp;action=pjActionUpdate" method="post" id="frmUpdateFeature" class="form pj-form" autocomplete="off">
	<input type="hidden" name="feature_update" value="1" />
	<input type="hidden" name="id" value="<?php echo (int) $tpl['arr']['id']; ?>" />
	<input type="hidden" name="csrf_token" value="<?php echo pjAppController::getCsrfToken(); ?>" />
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="myModalFeatureLabel"><?php __('infoEditFeatureTitle');?></h4>
	</div>
	<div class="modal-body">
		<?php if ($tpl['is_flag_ready']) : ?>
		<div class="row">
			<div class="col-sm-12 btn-group-languages text-right">
				<div class="multilang"></div>
			</div>
		</div><!-- /.row -->
		<?php endif; ?>
		<div class="row">
			<div class="col-sm-8">
				<div class="form-group">
					<label class="control-label"><?php __('lblFeatureName');?></label>
					<?php
					foreach ($tpl['lp_arr'] as $v)
					{
						?>
						<div class="<?php echo $tpl['is_flag_ready'] ? 'input-group ' : NULL;?>pj-multilang-wrap" data-index="<?php echo $v['id']; ?>" style="display: <?php echo (int) $v['is_default'] === 1 ? NULL : 'none'; ?>">
							<input type="text" class="form-control<?php echo (int) $v['is_default'] === 0 ? NULL : ' required'; ?>" name="i18n[<?php echo $v['id']; ?>][name]" value="<?php echo pjSanitize::html(@$tpl['arr']['i18n'][$v['id']]['name']); ?>" data-msg-required="<?php __('pj_field_required');?>">	
							<?php if ($tpl['is_flag_ready']) : ?>
							<span class="input-group-addon pj-multilang-input"><img src="<?php echo PJ_INSTALL_URL . PJ_FRAMEWORK_LIBS_PATH . 'pj/img/flags/' . $v['file']; ?>" alt="<?php echo pjSanitize::html($v['name']); ?>"></span>
							<?php endif; ?>
						</div>
						<?php 
					}
					?>
				</div>
			</div>
			<div class="col-sm-4">
				<div class="form-group">
					<label class="control-label"><?php __('lblStatus'); ?></label>
					<div class="clearfix">
						<div class="switch onoffswitch-data pull-left">
							<div class="onoffswitch">
								<input class="onoffswitch-checkbox" id="status" name="status" type="checkbox"<?php echo (!isset($tpl['arr']['status']) || $tpl['arr']['status'] === 'T') ? ' checked="checked"' : NULL; ?>>
								<label class="onoffswitch-label" for="status">
								<span class="onoffswitch-inner" data-on="<?php __('filter_ARRAY_active')?>" data-off="<?php __('filter_ARRAY_inactive')?>"></span>
								<span class="onoffswitch-switch"></span>
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label"><?php __('lblFeatureCategory'); ?></label>
			<select name="category_id" id="category_id" class="form-control">
				<?php
				foreach ($tpl['feature_categories'] as $k => $v)
				{
					?><option value="<?php echo $k; ?>"<?php echo (int) @$tpl['arr']['category_id'] === (int) $k ? ' selected="selected"' : NULL; ?>><?php echo stripslashes($v); ?></option><?php
				}
				?>
			</select>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-white" data-dismiss="modal"><?php __('btnCancel'); ?></button>
		<button type="button" class="ladda-button btn btn-primary btnSaveFeature" data-style="zoom-in"><?php __('btnSave'); ?></button>
	</div>
</form>
