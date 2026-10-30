<?php
$ai = $tpl['ai'];
$err = $controller->_get->toString('err');
$saved_keys = array_filter(explode(',', (string) $controller->_get->toString('keys')));
$errs = array_filter(explode(',', (string) $controller->_get->toString('errs')));
$links = array(
	'gemini' => 'https://aistudio.google.com/apikey',
	'openai' => 'https://platform.openai.com/api-keys',
	'anthropic' => 'https://console.anthropic.com/settings/keys',
);
$name = function ($s) use ($ai) { return isset($ai['services'][$s]) ? $ai['services'][$s]['name'] : $s; };
?>
<div class="row wrapper border-bottom white-bg page-heading">
	<div class="col-sm-12">
		<h2><?php __('ai_title'); ?></h2>
		<p class="m-b-none"><i class="fa fa-info-circle"></i> <?php __('ai_desc'); ?></p>
	</div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
	<?php if ($err === 'AIS01' || $err === 'AIS02'): ?>
		<?php if ($err === 'AIS01'): ?>
			<div class="alert alert-success"><i class="fa fa-check m-r-xs"></i> <?php __('ai_saved'); ?>
			<?php foreach ($saved_keys as $s): if (isset($ai['services'][$s])): ?>
				<br><?php echo pjSanitize::html(sprintf(__('ai_saved_key', true), $name($s), $ai['services'][$s]['key_hint'])); ?>
			<?php endif; endforeach; ?>
			</div>
		<?php else: ?>
			<div class="alert alert-danger"><i class="fa fa-exclamation-triangle m-r-xs"></i>
			<?php foreach ($errs as $e):
				if (strpos($e, 'db_') === 0 || $e === 'service') {
					$what = $e === 'service' ? __('ai_service', true) : $name(preg_replace('/^db_(key|model)_/', '', $e));
					echo pjSanitize::html(sprintf(__('ai_err_db', true), $what)), '<br>';
				} elseif (strpos($e, 'model_') === 0) {
					echo pjSanitize::html($name(substr($e, 6)) . ': ' . __('ai_err_model', true)), '<br>';
				} elseif (strpos($e, 'key_') === 0) {
					echo pjSanitize::html($name(substr($e, 4)) . ': ' . __('ai_err_key', true)), '<br>';
				}
			endforeach; ?>
			<?php foreach ($saved_keys as $s): if (isset($ai['services'][$s])): ?>
				<?php echo pjSanitize::html(sprintf(__('ai_saved_key', true), $name($s), $ai['services'][$s]['key_hint'])); ?><br>
			<?php endif; endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminOptions&amp;action=pjActionAi" class="ibox float-e-margins" id="frmAiSettings" autocomplete="off"
		data-url="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminOptions&amp;action=pjActionAi"
		data-txt-loading="<?php echo pjSanitize::html(__('ai_loading', true)); ?>"
		data-txt-testing="<?php echo pjSanitize::html(__('ai_testing', true)); ?>"
		data-txt-models-found="<?php echo pjSanitize::html(__('ai_models_found', true)); ?>"
		data-txt-error="<?php echo pjSanitize::html(__('ai_request_failed', true)); ?>"
		data-txt-custom="<?php echo pjSanitize::html(__('ai_custom_model', true)); ?>"
		data-txt-custom-tag="<?php echo pjSanitize::html(__('ai_custom_tag', true)); ?>"
		data-txt-choose="<?php echo pjSanitize::html(__('ai_choose_model', true)); ?>">
		<input type="hidden" name="ai_save" value="1">
		<input type="hidden" name="csrf_token" value="<?php echo pjAppController::getCsrfToken(); ?>">
		<div class="ibox-content">
			<div class="row">
				<div class="col-md-4 form-group">
					<label for="ai_provider"><?php __('ai_service'); ?></label>
					<select class="form-control" name="ai_provider" id="ai_provider">
						<?php foreach ($ai['services'] as $s => $info): ?>
							<option value="<?php echo $s; ?>"<?php echo $ai['provider'] === $s ? ' selected' : ''; ?>><?php echo pjSanitize::html($info['name']); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="text-muted small m-t-xs"><?php __('ai_service_hint'); ?></p>
				</div>
			</div>

			<style>.ai-key-masked{-webkit-text-security:disc;text-security:disc}</style>
			<?php foreach ($ai['services'] as $s => $info):
				// Models shown: last list loaded from the service + custom ones + the current model
				$models = $info['loaded'];
				foreach (array_merge($info['custom'], array($info['model'])) as $m) { if ($m !== '' && !in_array($m, $models, true)) { $models[] = $m; } }
			?>
			<div class="ai-service-panel<?php echo $ai['provider'] === $s ? '' : ' hidden'; ?>" data-service="<?php echo $s; ?>">
				<div class="hr-line-dashed"></div>
				<h4><?php echo pjSanitize::html($info['name']); ?></h4>
				<div class="row">
					<div class="col-md-6 form-group">
						<label><?php __('ai_api_key'); ?></label>
						<div class="input-group">
							<input class="form-control ai-key ai-key-masked" type="text" name="key_<?php echo $s; ?>" value="<?php echo pjSanitize::html($info['key']); ?>" autocomplete="off" spellcheck="false" data-lpignore="true" data-1p-ignore
								placeholder="<?php echo pjSanitize::html(__('ai_key_placeholder', true)); ?>">
							<span class="input-group-btn">
								<button type="button" class="btn btn-default ai-key-toggle" title="Show / hide the key" aria-label="Show the key" aria-pressed="false"><i class="fa fa-eye"></i></button>
							</span>
						</div>
						<p class="text-muted small m-t-xs">
							<?php if ($info['has_key']): ?>
								<span class="text-success"><i class="fa fa-check"></i> <?php echo pjSanitize::html(sprintf(__('ai_key_saved_masked', true), $info['key_hint'])); ?></span>
							<?php else: ?>
								<span class="text-warning"><i class="fa fa-exclamation-circle"></i> <?php __('ai_key_none'); ?></span>
							<?php endif; ?>
							&nbsp;<a href="<?php echo $links[$s]; ?>" target="_blank" rel="noopener"><?php __('ai_get_key'); ?> <i class="fa fa-external-link"></i></a>
						</p>
						<?php if ($info['has_key']): ?>
							<label class="small"><input type="checkbox" name="remove_key_<?php echo $s; ?>" value="1"> <?php __('ai_key_remove'); ?></label>
						<?php endif; ?>
					</div>
					<div class="col-md-6 form-group">
						<label><?php __('ai_model'); ?></label>
						<div class="input-group">
							<select class="form-control ai-model-select" name="model_<?php echo $s; ?>">
								<?php if (empty($models)): ?><option value=""><?php __('ai_choose_model'); ?></option><?php endif; ?>
								<?php foreach ($models as $m): ?>
									<option value="<?php echo pjSanitize::html($m); ?>"<?php echo $m === $info['model'] ? ' selected' : ''; ?>><?php echo pjSanitize::html($m); ?><?php echo in_array($m, $info['custom'], true) ? ' ' . pjSanitize::html(__('ai_custom_tag', true)) : ''; ?></option>
								<?php endforeach; ?>
								<option value="__custom__"><?php __('ai_custom_model'); ?></option>
							</select>
							<span class="input-group-btn"><button type="button" class="btn btn-default ai-load-models"><i class="fa fa-refresh"></i> <?php __('ai_btn_load'); ?></button></span>
						</div>
						<input class="form-control ai-custom-model m-t-xs hidden" type="text" name="custom_model_<?php echo $s; ?>" maxlength="100" autocomplete="off" spellcheck="false" placeholder="<?php echo pjSanitize::html(__('ai_custom_placeholder', true)); ?>">
						<p class="text-muted small m-t-xs"><?php echo empty($info['loaded']) ? __('ai_models_none', true) : pjSanitize::html(sprintf(__('ai_models_listed', true), count($info['loaded']))); ?> <?php __('ai_model_hint'); ?></p>
						<?php if (!empty($info['custom'])): ?>
							<p class="small m-b-none"><?php __('ai_custom_list'); ?>
							<?php foreach ($info['custom'] as $m): ?>
								<label class="m-r-sm"><input type="checkbox" name="remove_custom_<?php echo $s; ?>[]" value="<?php echo pjSanitize::html($m); ?>"> <?php echo pjSanitize::html(sprintf(__('ai_remove_custom', true), $m)); ?></label>
							<?php endforeach; ?>
							</p>
						<?php endif; ?>
					</div>
				</div>
				<div class="form-group">
					<button type="button" class="btn btn-primary btn-outline ai-test"><i class="fa fa-plug"></i> <?php __('ai_btn_test'); ?></button>
					<span class="ai-result m-l-sm"></span>
				</div>
			</div>
			<?php endforeach; ?>

			<div class="hr-line-dashed"></div>
			<button class="btn btn-primary btn-lg" type="submit"><?php __('ai_btn_save'); ?></button>
		</div>
	</form>
</div>
