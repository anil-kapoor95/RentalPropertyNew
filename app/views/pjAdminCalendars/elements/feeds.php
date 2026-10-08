<style>

	.property_ical_url_class{ width: 100%; }
	.add_sync_ical_urls, .remove_sync_ical_urls{ cursor: pointer; }

	.pj-ical-note{
		background: #f3f8f4;
		border: 1px solid #dcebe0;
		border-left: 4px solid #2e7d32;
		padding: 12px 16px;
		border-radius: 4px;
		margin: 6px 0 18px;
		color: #4a5a4d;
		font-size: 13px;
		line-height: 1.55;
	}
	.pj-ical-note strong{ color: #2e7d32; }
	.pj-ical-note .pj-ical-note-title{ display:block; font-size:14px; margin-bottom:4px; color:#2e7d32; font-weight:600; }
	.pj-ical-block{ margin-bottom: 10px; }
	.pj-ical-block-heading{ font-size:16px; font-weight:600; color:#333; margin: 0 0 4px; }
</style>

<div id="feeds" class="tab-pane<?php echo $active_tab == 'feeds' ? ' active' : NULL;?>">

    <div class="panel-body">

        <div class="panel-body-inner">

            <div class="row">

                <div class="col-lg-12">

                    <div class="ibox float-e-margins settings-box">

                        <!-- ============ EXPORT: this property's outgoing iCal link ============ -->
                        <div class="form-group">

                            <label class="control-label" for="property_ical_url"><?php __('propertyICalFeed')?></label>

                            <div class="pj-ical-note">
                                <span class="pj-ical-note-title"><?php __('icalExportNoteTitle')?></span>
                                <?php __('icalExportNoteText')?>
                            </div>

                            <?php $ical_url = PJ_INSTALL_URL . $tpl['arr']['ical_url']; ?>
                            <div class="input-group property_ical_url_class">
                                <input class="form-control form-control-lg property_ical_url" type="text" id="property_ical_url" value="<?php echo $ical_url;?>" readonly onclick="this.select();" />
                            </div>

                        </div>

                        <div class="hr-line-dashed"></div>

                        <!-- legacy single-feed wrapper kept hidden so existing JS hooks stay valid -->
                        <div id="pjFeedWrapper" style="display:none;"></div>

                        <!-- ============ IMPORT: feeds pulled INTO this system ============ -->
                        <div class="pj-ical-block">
                            <h3 class="pj-ical-block-heading"><?php __('infoAddICalFeeds')?></h3>
                        </div>

                        <div class="pj-ical-note">
                            <span class="pj-ical-note-title"><?php __('icalImportNoteTitle')?></span>
                            <?php __('icalImportNoteText')?>
                        </div>

                        <div class="ibox-content" id="pjFeedIcalUrlWrapper">

                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCalendars&amp;action=pjActionUpdateSyncUrls" method="post" id="frmFeedsSyncUrls" class="form pj-form">

                                <input type="hidden" class="calendar_id" name="calendar_id" value="<?php echo $tpl['arr']['id'];?>">

                                <div class="ical_parent_div">
                                <?php if(count($tpl['sync_ical_urls']) > 0){
                                    foreach ($tpl['sync_ical_urls'] as $key => $ical) { ?>

                                        <div class="form-group">
                                            <label class="control-label" for="url"><?php __('lblIcalURL');?></label>
                                            <div class="input-group">
                                                <input class="form-control form-control-lg url sync_ical_url" type="text" name="ical_url[]" value="<?php echo $ical['ical_urls'];?>">
                                                <span class="input-group-addon <?php if($key == 0) echo 'add_sync_ical_urls'; else echo 'remove_sync_ical_urls'; ?>">
                                                <i class="<?php if($key == 0) echo 'fa fa-plus'; else echo 'fa fa-minus'; ?>"></i>
                                                </span>
                                            </div>
                                        </div>
                                <?php }
                                }else { ?>

                                        <div class="form-group">
                                            <label class="control-label" for="url"><?php __('lblIcalURL');?></label>
                                            <div class="input-group">
                                                <input class="form-control form-control-lg url sync_ical_url" type="text" name="ical_url[0]" value="" data-msg-required="<?php __('pj_field_required');?>" data-msg-url="<?php __('pj_field_url');?>" data-msg-remote="<?php __('pj_feed_error_msg')?>">
                                                <span class="input-group-addon add_sync_ical_urls">
                                                <i class="fa fa-plus"></i>
                                                </span>
                                            </div>
                                        </div>
                                <?php } ?>
                                </div>

                                <div class="hr-line-dashed"></div>

                                <div class="clearfix">
                                    <button id="pjRpbcSaveIcal" type="button" class="ladda-button btn btn-primary btn-lg btn-phpjabbers-loader pull-left" data-style="zoom-in" style="margin-right: 15px;">
                                        <span class="ladda-label"><?php __('btnSave'); ?></span>
                                        <?php include $controller->getConstant('pjBase', 'PLUGIN_VIEWS_PATH') . 'pjLayouts/elements/button-animation.php'; ?>
                                    </button>
                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
<script type="text/javascript">
var myLabel = myLabel || {};
myLabel.lblIcalURL = <?php x__encode('lblIcalURL'); ?>;
</script>