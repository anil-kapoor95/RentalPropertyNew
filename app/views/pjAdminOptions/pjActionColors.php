<link rel="stylesheet" href="<?php echo PJ_INSTALL_URL; ?>app/web/css/rpbc-admin.css">
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-sm-12">
        <div class="row">
            <div class="col-sm-10">
                <h2><?php __('infoThemeColorsTitle') ?></h2>
            </div>
        </div><!-- /.row -->
        <p class="m-b-none"><i class="fa fa-info-circle"></i> <?php __('infoThemeColorsDesc') ?></p>
    </div><!-- /.col-md-12 -->
</div>

<div class="row border-bottom bar-top">
    <div class="col-1of3">&nbsp;</div>
    <div class="col-1of3 text-center">&nbsp;</div>
    <div class="col-1of3 text-right">
        <a target="_blank" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjListings&amp;action=pjActionListing&amp;company=<?php echo pjCompanyContext::hashOf($controller->getCompanyId()); ?>" class="btn btn-secondary"><i class="fa fa-external-link m-r-xs"></i> Open in new window</a>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminOptions&amp;action=pjActionInstall" class="btn btn-secondary"><i class="fa fa-wrench m-r-xs"></i> Install your website</a>
    </div>
</div>



<div class="pjtc-layout" id="pjThemeColors" data-csrf="<?php echo pjAppController::getCsrfToken(); ?>" data-save-url="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminOptions&amp;action=pjActionSaveThemeColors" data-listing-url="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjListings&amp;action=pjActionListing&amp;company=<?php echo pjCompanyContext::hashOf($controller->getCompanyId()); ?>">

    <!-- LEFT: controls -->
    <div class="pjtc-controls">
        <div class="pjtc-card">
            <div class="pjtc-surprise">
                <span class="lbl"><i class="fa fa-magic"></i> Surprise me</span>
                <div class="opts">
                    <label style="margin:0;font-weight:400;cursor:pointer"><input type="checkbox" id="pjtcIncludeFont"> include font</label>
                </div>
            </div>
            <button type="button" id="pjtcRandom"><i class="fa fa-random"></i> &nbsp;Generate random colours</button>
        </div>

        <div class="pjtc-card">
            <h3><i class="fa fa-paint-brush"></i> Colour palettes</h3>
            <div class="pjtc-palettes">
                <?php foreach ($tpl['palettes'] as $pid => $p):
                    $pBrand   = $p['brand'];
                    $pUltra   = pjColorHelper::mixWithWhite($pBrand, 92);
                    $pLight   = pjColorHelper::lighten($pBrand, 32);
                    $pDark    = pjColorHelper::darken($pBrand, 18);
                    $pBorder  = pjColorHelper::mixWithWhite($pBrand, 86);
                    $pContrast= pjColorHelper::contrastColor($pBrand);
                ?>
                <button type="button" class="pjpal<?php echo ((string) $tpl['theme_colors']['o_color_palette'] === (string) $pid) ? ' sel' : ''; ?>"
                    data-id="<?php echo $pid; ?>"
                    data-brand="<?php echo $p['brand']; ?>" data-accent="<?php echo $p['accent']; ?>"
                    data-text="<?php echo $p['text']; ?>" data-bg="<?php echo $p['bg']; ?>"
                    data-price="<?php echo $p['price']; ?>" data-pagebg="<?php echo $p['pageBg']; ?>">
                    <span class="pjpal-check"><i class="fa fa-check"></i></span>
                    <span class="pjpal-preview" style="background:<?php echo $p['pageBg']; ?>">
                        <span class="pjpal-mini" style="background:<?php echo $p['bg']; ?>;border-color:<?php echo $pBorder; ?>">
                            <span class="pjpal-img" style="background:linear-gradient(135deg,<?php echo $pUltra; ?>,<?php echo $pLight; ?>)"></span>
                            <span class="pjpal-b">
                                <span class="pjpal-ttl" style="background:<?php echo pjColorHelper::mixWithWhite($p['text'], 15); ?>"></span>
                                <span class="pjpal-chip" style="background:<?php echo $pUltra; ?>;border-color:<?php echo $pBorder; ?>"></span>
                                <span class="pjpal-foot">
                                    <span class="pjpal-price" style="background:<?php echo $p['price']; ?>"></span>
                                    <span class="pjpal-btn" style="background:<?php echo $pBrand; ?>"></span>
                                </span>
                            </span>
                        </span>
                    </span>
                    <span class="pjpal-name">
                        <span class="pjpal-dots">
                            <i style="background:<?php echo $p['brand']; ?>"></i>
                            <i style="background:<?php echo $p['accent']; ?>"></i>
                            <i style="background:<?php echo $p['price']; ?>"></i>
                        </span>
                        <?php echo pjSanitize::html($p['name']); ?>
                    </span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pjtc-card">
            <h3><i class="fa fa-sliders"></i> Custom colours</h3>
            <div class="pjtc-custom">
                <?php
                $fields = array(
                    'brand'  => array('Brand',      $tpl['theme_colors']['o_brand_color']),
                    'accent' => array('Accent',     $tpl['theme_colors']['o_accent_color']),
                    'text'   => array('Text',       $tpl['theme_colors']['o_text_color']),
                    'bg'     => array('Surface', $tpl['theme_colors']['o_background_color']),
                    'price'  => array('Price',      $tpl['theme_colors']['o_price_color']),
                    'pagebg' => array('Page background',  $tpl['theme_colors']['o_page_background_color'])
                );
                foreach ($fields as $k => $f):
                ?>
                <div class="pjtc-field">
                    <span><?php echo $f[0]; ?></span>
                    <div class="pjtc-color">
                        <input type="color" data-role="picker" data-key="<?php echo $k; ?>" value="<?php echo $f[1]; ?>">
                        <input type="text" data-role="hex" data-key="<?php echo $k; ?>" value="<?php echo $f[1]; ?>" maxlength="7">
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="pjtc-field">
                    <span>Font family (optional)</span>
                    <input type="text" id="pjtcFont" class="pjtc-font" placeholder="Inter" value="<?php echo pjSanitize::html($tpl['theme_colors']['o_font_family']); ?>">
                </div>
            </div>
        </div>

        <div class="pjtc-actions">
            <span class="pjtc-saved" id="pjtcSaved"><i class="fa fa-check"></i> Saved</span>
            <input type="hidden" id="pjtcPalette" value="<?php echo (int) $tpl['theme_colors']['o_color_palette']; ?>">
            <button type="button" id="pjtcSave"><i class="fa fa-check"></i> &nbsp;Save colours</button>
        </div>
    </div>

    <!-- RIGHT: live preview -->
    <div class="pjtc-previewcol">
        <div class="pjtc-card">
            <div class="pjtc-pvhead">
                <span class="t"><i class="fa fa-eye"></i> Live preview</span>
                <button type="button" class="pjtc-reload" id="pjtcReload"><i class="fa fa-refresh"></i> Reload</button>
            </div>
            <div class="pjtc-frame">
                <iframe id="pjtcFrame" title="Live preview" src="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjListings&amp;action=pjActionListing&amp;company=<?php echo pjCompanyContext::hashOf($controller->getCompanyId()); ?>"></iframe>
            </div>
        </div>
    </div>
</div>
