<div class="wrapper wrapper-content">
	<div class="row">
		<div class="col-lg-10 col-lg-offset-1">

			<div class="ibox float-e-margins">
				<div class="ibox-content pj-setup">

					<div class="pj-setup-head">
						<h1><?php __('setupWelcomeTitle'); ?></h1>
						<p><?php __('setupWelcomeSub'); ?></p>
					</div>

					<?php if (!$tpl['hasDemoData']): ?>
					<div class="pj-setup-cards">
						<div class="pj-setup-card">
							<div class="pj-setup-icon"><i class="fa fa-home"></i></div>
							<h2><?php __('setupDemoTitle'); ?></h2>
							<p><?php __('setupDemoDesc'); ?></p>
							<button type="button" class="btn btn-primary btn-block" id="pjLoadDemo" data-busy="<?php echo pjSanitize::html(__('setupLoadingDemo', true)); ?>" data-ok="<?php echo pjSanitize::html(__('setupLoadedOk', true)); ?>"><i class="fa fa-download"></i> <?php __('setupBtnLoad'); ?></button>
						</div>
						<div class="pj-setup-card">
							<div class="pj-setup-icon pj-setup-icon--muted"><i class="fa fa-file-o"></i></div>
							<h2><?php __('setupEmptyTitle'); ?></h2>
							<p><?php __('setupEmptyDesc'); ?></p>
							<button type="button" class="btn btn-white btn-block" id="pjSkipDemo"><?php __('setupBtnSkip'); ?></button>
						</div>
					</div>
					<?php else: ?>
					<div class="pj-setup-manage">
						<div class="alert alert-info"><?php __('setupDemoActiveDesc'); ?></div>
						<button type="button" class="btn btn-danger btn-lg" id="pjRemoveDemo" data-confirm="<?php echo pjSanitize::html(__('setupRemoveConfirm', true)); ?>" data-busy="<?php echo pjSanitize::html(__('setupRemovingDemo', true)); ?>" data-ok="<?php echo pjSanitize::html(__('setupRemovedOk', true)); ?>"><i class="fa fa-trash"></i> <?php __('setupBtnRemove'); ?></button>
						<a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdmin&amp;action=pjActionIndex" class="btn btn-white btn-lg"><?php __('setupBtnGoDashboard'); ?></a>
					</div>
					<?php endif; ?>

					<div class="pj-setup-status" id="pjSetupStatus" style="display:none;"></div>

				</div>
			</div>

		</div>
	</div>
</div>

<style>
.pj-setup{padding:34px 30px}
.pj-setup-head{text-align:center;margin-bottom:28px}
.pj-setup-head h1{font-size:1.7rem;font-weight:800;color:#1f2430;margin:0 0 8px}
.pj-setup-head p{color:#6b7280;font-size:1rem;margin:0}
.pj-setup-cards{display:flex;gap:24px;flex-wrap:wrap;justify-content:center}
.pj-setup-card{flex:1 1 300px;max-width:380px;border:1px solid #e9ecf1;border-radius:14px;padding:28px 24px;text-align:center;background:#fff;transition:box-shadow .2s,transform .2s}
.pj-setup-card:hover{box-shadow:0 12px 30px -14px rgba(16,24,40,.25);transform:translateY(-2px)}
.pj-setup-icon{width:64px;height:64px;border-radius:50%;background:#fff3ea;color:#F97618;display:flex;align-items:center;justify-content:center;font-size:1.7rem;margin:0 auto 16px}
.pj-setup-icon--muted{background:#f1f3f6;color:#6b7280}
.pj-setup-card h2{font-size:1.15rem;font-weight:700;color:#1f2430;margin:0 0 8px}
.pj-setup-card p{color:#6b7280;font-size:.92rem;line-height:1.5;margin:0 0 20px;min-height:64px}
.pj-setup-manage{text-align:center;padding:10px 0}
.pj-setup-manage .btn{margin:0 6px}
.pj-setup-status{margin-top:24px;text-align:center;font-size:1rem;font-weight:600}
.pj-setup-status.ok{color:#16a34a}
.pj-setup-status.err{color:#da5350}
.pj-setup-status.busy{color:#F97618}
</style>
