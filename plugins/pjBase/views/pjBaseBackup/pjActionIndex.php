<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-sm-12">
        <div class="row">
            <div class="col-sm-10">
                <h2><?php __('plugin_base_infobox_backup_title'); ?></h2>
            </div>
        </div><!-- /.row -->

        <p class="m-b-none"><i class="fa fa-info-circle"></i> <?php __('plugin_base_infobox_backup_desc'); ?></p>
    </div><!-- /.col-md-12 -->
</div>

<div class="wrapper wrapper-content animated fadeInRight">
	<?php
	$error_code = $controller->_get->toString('err');
	if (!empty($error_code))
    {
    	$titles = __('plugin_base_error_titles', true);
    	$bodies = __('plugin_base_error_bodies', true);
    	switch (true)
    	{
    		case in_array($error_code, array('PBU01')):
    			?>
    			<div class="alert alert-success">
    				<i class="fa fa-check m-r-xs"></i>
    				<strong><?php echo @$titles[$error_code]; ?></strong>
    				<?php echo @$bodies[$error_code]?>
    			</div>
    			<?php 
    			break;
            case in_array($error_code, array('PBU05', 'PBU06', 'PBU03', 'PBU04', 'PBU07')):
    			?>
    			<div class="alert alert-danger">
    				<i class="fa fa-exclamation-triangle m-r-xs"></i>
    				<strong><?php echo @$titles[$error_code]; ?></strong>
    				<?php echo @$bodies[$error_code]?>
    			</div>
    			<?php
    			break;
    	}
    }
    ?>
    <div class="row">
    	<div class="col-lg-9">
            <div class="ibox float-e-margins">
                <div class="ibox-content">
    				<div id="grid"></div>
                </div>
            </div><!-- /.ibox float-e-margins -->
    	</div><!-- /.col-lg-8 -->

        <div class="col-lg-3">
            <div class="panel no-borders">
                <div class="panel-heading bg-completed">
                    <p class="lead m-n"><?php __('plugin_base_backup_enable_auto_backup') ?></p>
                </div><!-- /.panel-heading -->

                <div class="panel-body">
                    <form action="" method="post" id="frmBackup" autocomplete="off">
                        <div class="form-group">
                            <label class="control-label"><?php __('plugin_base_backup_enable_auto_backup') ?>:</label>

                            <div class="m-t-xs">
                            	 <div class="row">
    								<div class="col-lg-4 col-md-4 col-sm-6 col-xs-6">
		                            	<div class="switch onoffswitch-data">
			                                <div class="onoffswitch">
			                                    <?php
			                                    // in a company: the company's own automatic-backup switch
			                                    $autoOn = $tpl['option_arr']['o_auto_backup'] == 'Yes';
			                                    if (class_exists('pjCompanyContext') && ($bkCid = (int) pjCompanyContext::activeId($controller->getRoleId(), $controller->getUserId())) > 0)
			                                    {
			                                        $bkJob = pjBaseCronJobModel::factory()->where('t1.controller', 'pjBaseBackup')->where('t1.action', 'pjActionAutoBackup')->limit(1)->findAll()->getDataIndex(0);
			                                        $bkRows = !empty($bkJob) ? pjCompanyContext::companyCronRows($bkCid) : array();
			                                        $autoOn = !empty($bkJob) && !empty($bkRows[$bkJob['id']]['is_active']);
			                                    }
			                                    ?>
			                                    <input type="checkbox" class="onoffswitch-checkbox" id="o_auto_backup"<?php echo $autoOn ? ' checked' : NULL;?>>
			                                    <label class="onoffswitch-label" for="o_auto_backup">
			                                        <span class="onoffswitch-inner" data-on="<?php __('plugin_base_yesno_ARRAY_T', false, true); ?>" data-off="<?php __('plugin_base_yesno_ARRAY_F', false, true); ?>"></span>
			                                        <span class="onoffswitch-switch"></span>
			                                    </label>
			                                </div>
			                            </div>
			                        </div>
			                     </div>
                            </div><!-- /.m-t-xs -->

                            <p class="alert alert-warning m-t-sm alert-with-icon"> <i class="fa fa-warning"></i> <?php __('plugin_base_backup_enable_auto_backup_text') ?></p>
                        </div>
    				</form>
                </div><!-- /.panel-body -->
            </div><!-- /.panel panel-primary -->

            <div class="panel no-borders">
                <div class="panel-heading bg-completed">
                    <p class="lead m-n"><?php __('plugin_base_create_backup_title') ?></p>
                </div><!-- /.panel-heading -->

                <div class="panel-body">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjBaseBackup&amp;action=pjActionBackup" method="post"  id="frmCreateBackup" autocomplete="off">
                        <input type="hidden" name="backup" value="1" />
                        <?php if (class_exists('pjCompanyContext')):
                            $bkActive = pjCompanyContext::activeCompany($controller->getRoleId(), $controller->getUserId());
                            $bkAdmin = pjCompanyContext::isAdmin($controller->getRoleId(), $controller->getUserId()); ?>
                        <div class="form-group">
                            <label class="control-label"><?php __('lblBackupScope'); ?>:</label>
                            <div class="radio m-t-xs m-b-none">
                                <label><input type="radio" name="backup_scope" value="company" checked> <?php __('lblBackupScopeCompany'); ?>: <strong><?php echo $bkActive ? pjSanitize::html($bkActive['name']) : '-'; ?></strong></label>
                            </div>
                            <?php if ($bkAdmin): ?>
                            <div class="radio m-t-none">
                                <label><input type="radio" name="backup_scope" value="system"> <?php __('lblBackupScopeSystem'); ?></label>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="control-label"><?php __('plugin_base_backup_database') ?>:</label>

                            <div class="m-t-xs">
                            	<div class="row">
    								<div class="col-lg-4 col-md-4 col-sm-6 col-xs-6">
		                            	<div class="switch onoffswitch-data">
			                                <div class="onoffswitch">
			                                    <input type="checkbox" class="onoffswitch-checkbox" name="backup_database" id="backup_database" checked>
			                                    <label class="onoffswitch-label" for="backup_database">
			                                        <span class="onoffswitch-inner" data-on="<?php __('plugin_base_yesno_ARRAY_T', false, true); ?>" data-off="<?php __('plugin_base_yesno_ARRAY_F', false, true); ?>"></span>
			                                        <span class="onoffswitch-switch"></span>
			                                    </label>
			                                </div>
			                            </div>
			                        </div>
			                    </div>
                            </div><!-- /.m-t-xs -->
                        </div>

                        <div class="form-group">
                            <label class="control-label"><?php __('plugin_base_backup_files') ?>:</label>

                            <div class="m-t-xs">
                            	<div class="row">
    								<div class="col-lg-4 col-md-4 col-sm-6 col-xs-6">
		                            	<div class="switch onoffswitch-data">
			                                <div class="onoffswitch">
			                                    <input type="checkbox" class="onoffswitch-checkbox" name="backup_files" id="backup_files" checked>
			                                    <label class="onoffswitch-label" for="backup_files">
			                                        <span class="onoffswitch-inner" data-on="<?php __('plugin_base_yesno_ARRAY_T', false, true); ?>" data-off="<?php __('plugin_base_yesno_ARRAY_F', false, true); ?>"></span>
			                                        <span class="onoffswitch-switch"></span>
			                                    </label>
			                                </div>
			                            </div>
			                        </div>
			                    </div>
                            </div><!-- /.m-t-xs -->
                        </div>

                        <div class="m-t-lg">
                            <button class="ladda-button btn btn-primary btn-lg btn-phpjabbers-loader" data-style="zoom-in">
                                <span class="ladda-label"><?php __('plugin_base_create_backup'); ?></span>
                                <?php include $controller->getConstant('pjBase', 'PLUGIN_VIEWS_PATH') . 'pjLayouts/elements/button-animation.php'; ?>
                            </button>
                        </div>
                    </form>
                </div><!-- /.panel-body -->
            </div><!-- /.panel panel-primary -->
        </div><!-- /.col-lg-4 -->
    </div><!-- /.row -->
</div><!-- /.wrapper wrapper-content animated fadeInRight -->

<script type="text/javascript">
var myLabel = myLabel || {};
myLabel.backup_made = <?php x__encode('plugin_base_backup_backup_made'); ?>;
myLabel.data_type = <?php x__encode('plugin_base_backup_data_type'); ?>;
myLabel.file_size = <?php x__encode('plugin_base_backup_file_size'); ?>;
myLabel.file_name = <?php x__encode('plugin_base_backup_file_name'); ?>;
myLabel.delete_confirmation = <?php x__encode('plugin_base_backup_delete_confirmation'); ?>;
myLabel.delete_selected = <?php x__encode('plugin_base_backup_delete_selected'); ?>;
</script>