<div class="wrap">
	<h1>Sferanet Settings</h1>

	<form method="POST" action="options.php">
		<?php settings_fields( 'sferanet-settings-group' ); ?>
		<?php do_settings_sections( 'sferanet-settings-group' ); ?>

		<?php submit_button(); ?>

	</form>
</div>
