<?php
/**
 * Banner on System Options pages: tells which company the settings belong to.
 */
if (class_exists('pjCompanyContext'))
{
	$pjcsc = $controller->_get->toString('controller');
	$pjcsa = $controller->_get->toString('action');
	$pjcsPages = array('pjBaseOptions', 'pjBaseSms', 'pjBaseLocale', 'pjBaseCountries', 'pjBaseCron', 'pjBaseBackup');
	if (in_array($pjcsc, $pjcsPages, true) && $pjcsa !== 'pjActionProfile')
	{
		$pjcsCompany = pjCompanyContext::activeCompany($controller->getRoleId(), $controller->getUserId());
		$pjcsName = $pjcsCompany ? pjSanitize::html($pjcsCompany['name']) : '-';
		if ($pjcsc === 'pjBaseCron')
		{
			$pjcsText = __('lblScopeCron', true);
			$pjcsIcon = 'fa-globe';
		} else {
			$pjcsText = sprintf('%s <strong>%s</strong>', __('lblScopeCompany', true), $pjcsName);
			if ($pjcsc === 'pjBaseLocale')
			{
				$pjcsText .= ' &middot; ' . __('lblScopeLanguages', true);
			} elseif ($pjcsc === 'pjBaseOptions' && $pjcsa === 'pjActionCaptchaSpam') {
				$pjcsText .= ' &middot; ' . __('lblScopeLoginCaptcha', true);
			}
			$pjcsIcon = 'fa-building';
		}
		?>
		<div class="pjcs-scope"><i class="fa <?php echo $pjcsIcon; ?>"></i> <span><?php echo $pjcsText; ?></span></div>
		<?php
	}
}
