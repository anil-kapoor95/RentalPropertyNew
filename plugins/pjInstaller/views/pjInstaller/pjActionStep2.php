<?php
$STORAGE = @$_SESSION[$controller->defaultInstaller];
?>


<form action="index.php?controller=pjInstaller&amp;action=pjActionStep3&amp;install=1" method="post" id="frmStep2" class="wizard-big">
    <h2>Requires</h2>

    <fieldset></fieldset>

    <h2>License Key</h2>

    <fieldset>

    	<?php
        $hasErrors = FALSE;
        $err = $controller->_get->toString('err');
        if ($err && isset($_SESSION[$controller->defaultErrors][$err]))
        {
            ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle m-r-xs"></i>
                <strong>Installation error!</strong>
                <?php echo $_SESSION[$controller->defaultErrors][$err]; ?>
            </div>
            <?php
            $hasErrors = TRUE;
            $alert = array('status' => 'ERR', 'text' => strip_tags($_SESSION[$controller->defaultErrors][$err]));
        }
        ?>
       

        <input type="hidden" name="step2" value="1" />

        <div class="m-b-md">
            <p>Enter your licence key. You can find your key under Profile page in your <a href="https://www.phpjabbers.com/accounts/login" target="_blank">PHPJabbers.com account</a>.
	<br /><br />Please, note that it is against our licence policy to install our products without providing valid licence key. You can check our our Licence policy <a href="licence.html" target="_blank">here</a>.</p>
        </div><!-- /.m-b-md -->

        <div class="hr-line-dashed"></div>

        <div class="table-responsive table-responsive-secondary">
            <table class="table table-striped">
                <thead>
                   <tr>
					<th>Licence Key</th>
				</tr>
                </thead>

                <tbody>
                    <tr>
					<td>
						<p>
							<label class="i-title">Key <span class="i-red">*</span></label>
							<input type="text" tabindex="1" name="license_key" class="pj-form-field w300 required form-control" value="<?php echo isset($STORAGE['license_key']) ? htmlspecialchars($STORAGE['license_key']) : NULL; ?>" />
						</p>
					</td>
				</tr>
                </tbody>
            </table>
        </div>

        <div class="hr-line-dashed"></div>

        <p>Need help? <a href="https://www.phpjabbers.com/contact.php" target="_blank">Contact us</a></p>
    </fieldset>

    <h2>MySQL Details</h2>

    <fieldset></fieldset>

    <h2>Install Paths</h2>

    <fieldset></fieldset>

    <h2>Admin Login</h2>

    <fieldset></fieldset>

    <h2>Install Progress</h2>

    <fieldset></fieldset>

    <h2>Finish</h2>

    <fieldset></fieldset>
</form>


