<div id="seo" class="tab-pane<?php echo $active_tab == 'seo' ? ' active' : NULL;?>">
	<div class="panel-body form-horizontal">
		<div class="panel-body-inner">

			<div class="alert alert-success"><?php __('lblSeoInfo'); ?></div>

			<form action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCalendars&amp;action=pjActionUpdate" method="post" id="frmCalendarSeo" class="form pj-form">
				<input type="hidden" name="property_update" value="1" />
				<input type="hidden" name="csrf_token" value="<?php echo pjAppController::getCsrfToken(); ?>" />
				<input type="hidden" name="tab" value="seo" />
				<input type="hidden" name="tab_id" value="15" />

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php __('lblMetaTitle'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<div class="row">
							<div class="col-sm-10">
								<?php
								foreach ($tpl['lp_arr'] as $v)
								{
									?>
									<div class="<?php echo $tpl['is_flag_ready'] ? 'input-group ' : NULL;?>pj-multilang-wrap" data-index="<?php echo $v['id']; ?>" style="display: <?php echo (int) $v['is_default'] === 1 ? NULL : 'none'; ?>">
										<input type="text" class="form-control" name="i18n[<?php echo $v['id']; ?>][meta_title]" value="<?php echo pjSanitize::html(@$tpl['arr']['i18n'][$v['id']]['meta_title']); ?>">
										<?php if ($tpl['is_flag_ready']) : ?>
										<span class="input-group-addon pj-multilang-input"><img src="<?php echo PJ_INSTALL_URL . PJ_FRAMEWORK_LIBS_PATH . 'pj/img/flags/' . $v['file']; ?>" alt="<?php echo pjSanitize::html($v['name']); ?>"></span>
										<?php endif; ?>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php __('lblMetaKeywords'); ?>:</label>
					<div class="col-lg-9 col-sm-9">
						<?php
						foreach ($tpl['lp_arr'] as $v)
						{
							?>
							<div class="pj-multilang-wrap" data-index="<?php echo $v['id']; ?>" style="display: <?php echo (int) $v['is_default'] === 1 ? NULL : 'none'; ?>">
								<textarea name="i18n[<?php echo $v['id']; ?>][meta_keywords]" rows="3" class="form-control"><?php echo pjSanitize::html(@$tpl['arr']['i18n'][$v['id']]['meta_keywords']); ?></textarea>
								<?php if ($tpl['is_flag_ready']) : ?>
								<span class="pj-multilang-input"><img src="<?php echo PJ_INSTALL_URL . PJ_FRAMEWORK_LIBS_PATH . 'pj/img/flags/' . $v['file']; ?>" alt="<?php echo pjSanitize::html($v['name']); ?>"></span>
								<?php endif; ?>
							</div>
							<?php
						}
						?>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php __('lblMetaDesc'); ?>:</label>
					<div class="col-lg-9 col-sm-9">
						<?php
						foreach ($tpl['lp_arr'] as $v)
						{
							?>
							<div class="pj-multilang-wrap" data-index="<?php echo $v['id']; ?>" style="display: <?php echo (int) $v['is_default'] === 1 ? NULL : 'none'; ?>">
								<textarea name="i18n[<?php echo $v['id']; ?>][meta_description]" rows="4" class="form-control"><?php echo pjSanitize::html(@$tpl['arr']['i18n'][$v['id']]['meta_description']); ?></textarea>
								<?php if ($tpl['is_flag_ready']) : ?>
								<span class="pj-multilang-input"><img src="<?php echo PJ_INSTALL_URL . PJ_FRAMEWORK_LIBS_PATH . 'pj/img/flags/' . $v['file']; ?>" alt="<?php echo pjSanitize::html($v['name']); ?>"></span>
								<?php endif; ?>
							</div>
							<?php
						}
						?>
					</div>
				</div>

				<div class="hr-line-dashed"></div>

				<div class="clearfix">
					<button type="submit" class="ladda-button btn btn-primary btn-lg btn-phpjabbers-loader pull-left" data-style="zoom-in" style="margin-right: 15px;">
						<span class="ladda-label"><?php __('btnSave'); ?></span>
						<?php include $controller->getConstant('pjBase', 'PLUGIN_VIEWS_PATH') . 'pjLayouts/elements/button-animation.php'; ?>
					</button>
					<a type="button" class="btn btn-white btn-lg pull-right" href="<?php echo PJ_INSTALL_URL; ?>index.php?controller=pjAdminCalendars&action=pjActionIndex"><?php __('btnCancel'); ?></a>
				</div>

			</form>

		</div>
	</div>
</div>
