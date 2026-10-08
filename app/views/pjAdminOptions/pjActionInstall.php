<link rel="stylesheet" href="<?php echo PJ_INSTALL_URL; ?>app/web/css/rpbc-admin.css">
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-sm-12">
        <div class="row">
            <div class="col-sm-10">
                <h2><?php __('lblInstallInfoTitle') ?></h2>
            </div>
        </div><!-- /.row -->

        <p class="m-b-none"><i class="fa fa-info-circle"></i> <?php __('lblInstallInfoDesc') ?></p>
    </div><!-- /.col-md-12 -->
</div>

<div class="row wrapper wrapper-content animated fadeInRight">
    <div class="col-lg-12">
    	<div class="ibox float-e-margins">
			<div class="ibox-content">
				<form action="<?php echo PJ_INSTALL_URL; ?>preview.php" method="get" class="form-horizontal" target="_blank">
					<input type="hidden" name="company" value="<?php echo pjCompanyContext::hashOf($controller->getCompanyId()); ?>" />					
					<div class="m-b-lg">
						<h2 class="no-margins"><?php __('lblInstallConfig');?></h2>
					</div>
	
					<div class="row">
						<div class="col-lg-8">
							<div class="form-group">
								<label class="col-lg-3 col-md-4 control-label"><?php __('lblInstallConfigCalendar');?></label>
	
								<div class="col-lg-5 col-md-8">
									<select id="install_calendar" name="cid" class="form-control">
										<?php if (!$controller->isOwner() && count($tpl['calendars']) > 0) { ?>
											<option value="all"><?php __('lblInstallConfigAllCalendars'); ?></option>
										<?php } ?>
										<?php
										foreach ($tpl['calendars'] as $calendar)
										{
											?><option value="<?php echo $calendar['id']; ?>"<?php echo !isset($_GET['calendar_id']) || $_GET['calendar_id'] != $calendar['id'] ? NULL : ' selected="selected"'; ?>><?php echo pjSanitize::html($calendar['name']); ?></option><?php
										}
										?>
									</select>
								</div>
							</div>
							
							<div class="form-group" style="display: <?php echo count($tpl['locale_arr']) > 1 ? 'block' : 'none';?>">
								<label class="col-lg-3 col-md-4 control-label"><?php __('lblInstallConfigLocale');?></label>
	
								<div class="col-lg-5 col-md-8">
									<select id="install_locale" name="locale" class="form-control">
										<option value=""><?php __('lblInstallConfigLang'); ?></option>
										<?php
										foreach ($tpl['locale_arr'] as $locale)
										{
											?><option value="<?php echo $locale['id']; ?>"><?php echo pjSanitize::html($locale['title']); ?></option><?php
										}
										?>
									</select>
								</div>
							</div>	
							
							<div class="form-group" style="display: none">
								<label class="col-lg-3 col-md-4 control-label"><?php __('lblInstallConfigMonths');?></label>
	
								<div class="col-lg-5 col-md-8">
									<select id="install_months" name="view" disabled="disabled" class="form-control">
										<option value="1">1</option>
										<option value="3">3</option>
										<option value="6">6</option>
										<option value="12">12</option>
									</select>
								</div>
							</div>	
								
							<div class="form-group">
								<label class="col-lg-3 col-md-4 control-label">&nbsp;</label>
	
								<div class="col-lg-5 col-md-8">
									<input type="submit" value="<?php __('btnPreview', false, true); ?>" class="btn btn-primary btn-lg" />
								</div>
							</div>
						</div>
					</div>
					
					<div class="hr-line-dashed"></div>
		
					<div class="m-b-lg">
						<h2 class="no-margins"><?php __('infoInstallCodeTitle');?></h2>
					</div>
		
					<p class="alert alert-info alert-with-icon m-t-xs"><i class="fa fa-info-circle"></i> <?php __('infoInstallCodeBody') ?></p>
		
					<div class="row">
						<div class="col-lg-12">
							<div class="form-group">
								<div class="col-lg-12 col-md-12">
									<textarea class="form-control textarea_install" rows="4">&lt;link href="<?php echo PJ_INSTALL_URL.PJ_FRAMEWORK_LIBS_PATH . 'pj/css/'; ?>pj.bootstrap.min.css" type="text/css" rel="stylesheet" /&gt;
&lt;link href="<?php echo PJ_INSTALL_URL; ?>index.php?controller=pjFront&action=pjActionLoadAvailabilityCss&company=<?php echo pjCompanyContext::hashOf($controller->getCompanyId()); ?>" type="text/css" rel="stylesheet" /&gt;
&lt;script type="text/javascript" src="<?php echo PJ_INSTALL_URL; ?>index.php?controller=pjFront&action=pjActionLoadAvailability&company=<?php echo pjCompanyContext::hashOf($controller->getCompanyId()); ?>"&gt;&lt;/script&gt;</textarea>
								</div>
							</div>
						</div>
					</div>
				</form>
				<div style="display: none" id="boxStandard">&lt;link href="<?php echo PJ_INSTALL_URL.PJ_FRAMEWORK_LIBS_PATH . 'pj/css/'; ?>pj.bootstrap.min.css" type="text/css" rel="stylesheet" /&gt;
&lt;link href="<?php echo PJ_INSTALL_URL; ?>index.php?controller=pjFront&action=pjActionLoadCss&cid={CID}" type="text/css" rel="stylesheet" /&gt;
&lt;script type="text/javascript" src="<?php echo PJ_INSTALL_URL; ?>index.php?controller=pjFront&action=pjActionLoad&cid={CID}&view={VIEW}{LOCALE}"&gt;&lt;/script&gt;
</div>

<div style="display: none" id="boxAvailability">&lt;link href="<?php echo PJ_INSTALL_URL.PJ_FRAMEWORK_LIBS_PATH . 'pj/css/'; ?>pj.bootstrap.min.css" type="text/css" rel="stylesheet" /&gt;
&lt;link type="text/css" rel="stylesheet" href="<?php echo PJ_INSTALL_URL; ?>index.php?controller=pjFront&action=pjActionLoadAvailabilityCss&company=<?php echo pjCompanyContext::hashOf($controller->getCompanyId()); ?>" /&gt;
&lt;script type="text/javascript" src="<?php echo PJ_INSTALL_URL; ?>index.php?controller=pjFront&action=pjActionLoadAvailability&company=<?php echo pjCompanyContext::hashOf($controller->getCompanyId()); ?>{LOCALE}"&gt;&lt;/script&gt;</div>
			</div>
		</div>

</div><!-- /.col-lg-12 -->
</div>
<?php
$__co       = '&company=' . pjCompanyContext::hashOf($controller->getCompanyId());
$__themeCss = PJ_INSTALL_URL . 'index.php?controller=pjFront&action=pjActionThemeCss' . $__co;
$__listCss  = PJ_INSTALL_URL . 'index.php?controller=pjListings&action=pjActionListingCss';
$__listJs   = PJ_INSTALL_URL . 'index.php?controller=pjListings&action=pjActionListingJs' . $__co;
$__listUrl  = PJ_INSTALL_URL . 'index.php?controller=pjListings&action=pjActionListing' . $__co;
$__embed    = '<link type="text/css" rel="stylesheet" href="'.$__themeCss.'" />'."\n".'<link type="text/css" rel="stylesheet" href="'.$__listCss.'" />'."\n".'<div id="rpbcProperties"></div>'."\n".'<script type="text/javascript" src="'.$__listJs.'"></script>';
$__scriptOnly = '<div id="rpbcProperties"></div>'."\n".'<script type="text/javascript" src="'.$__listJs.'"></script>';
?>
<div class="wrapper wrapper-content"><div class="row"><div class="col-lg-12">
	<div class="ibox float-e-margins">
		<div class="ibox-title"><h5><?php __('lblListingInstall'); ?></h5></div>
		<div class="ibox-content">
			<div class="alert alert-info"><?php __('lblListingInstallNote'); ?></div>
			<div class="pjemb-wrap">
				<div class="pjemb-card">
					<h3><?php __('lblInstallCodeFull'); ?></h3>
					<p class="sub"><?php __('lblInstallCodeFullNote'); ?></p>
					<div class="pjemb-codewrap"><button type="button" class="pjemb-copy" data-target="lcode1"><?php __('lblCopy'); ?></button><pre class="pjemb-code" id="lcode1"><?php echo pjSanitize::html($__embed); ?></pre></div>
				</div>
				<div class="pjemb-card">
					<h3><?php __('lblShorterVersion'); ?></h3>
					<p class="sub"><?php __('lblShorterVersionNote'); ?></p>
					<div class="pjemb-codewrap"><button type="button" class="pjemb-copy" data-target="lcode2"><?php __('lblCopy'); ?></button><pre class="pjemb-code" id="lcode2"><?php echo pjSanitize::html($__scriptOnly); ?></pre></div>
				</div>
				<div class="pjemb-card">
					<h3><?php __('lblDirectLink'); ?></h3>
					<p class="sub"><?php __('lblDirectLinkNote'); ?></p>
					<div class="pjemb-actions"><a class="pjemb-btn primary" href="<?php echo $__listUrl; ?>" target="_blank"><i class="fa fa-external-link"></i> <?php __('lblOpenPropertiesPage'); ?></a></div>
				</div>
			</div>
		</div>
	</div>
</div></div></div>
<script>
(function(){var b=document.querySelectorAll('.pjemb-copy');for(var i=0;i<b.length;i++){b[i].addEventListener('click',function(){var el=document.getElementById(this.getAttribute('data-target')),btn=this,txt=el.innerText||el.textContent;var d=function(){btn.textContent='Copied';setTimeout(function(){btn.textContent='Copy';},1500);};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(txt).then(d,function(){});}else{var r=document.createRange();r.selectNode(el);var s=window.getSelection();s.removeAllRanges();s.addRange(r);try{document.execCommand('copy');d();}catch(e){}s.removeAllRanges();}});}})();
</script>
