
<div class="wrap">
	<form method="post" action="options.php">
	    <h2>OogTV uitzendingen instellingen</h2>
		<?php settings_fields( 'oog-uitzendingen' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row"><label for="oog-uitzendingen-clientid">Google Api ClientID</label></th>
				<td><input type="text" id="oog-uitzendingen-clientid" name="oog-uitzendingen-clientid" value="<?= get_option('oog-uitzendingen-clientid')?>"></td>
			</tr>

            <tr>
                <th scope="row"><label for="oog-uitzendingen-secret">Google Api Secret</label></th>
                <td><input type="text" id="oog-uitzendingen-secret" name="oog-uitzendingen-secret" value="<?= get_option('oog-uitzendingen-secret')?>"></td>
            </tr>

            <tr>
                <th scope="row"><label for="oog-uitzendingen-projectid">Google Project ID</label></th>
                <td><input type="text" id="oog-uitzendingen-projectid" name="oog-uitzendingen-projectid" value="<?= get_option('oog-uitzendingen-projectid')?>"></td>
            </tr>

            <tr>
                <th scope="row"><label for="oog-uitzendingen-origins">Google Api Javascript origins (1 per regel)</label></th>
                <td><textarea id="oog-uitzendingen-origins" name="oog-uitzendingen-origins"><?= get_option('oog-uitzendingen-origins')?></textarea></td>
            </tr>

        </table>


		<?php submit_button(); ?>
	</form>

	<?php
	$client = \oog\uitzendingen\admin\Admin::GetGoogleClient();
	$tokenData = null;
	try {
		$tokenData = $client->verifyIdToken(get_option('oog-uitzending-id_token'));

	} catch (LogicException $e) {
		// Id token probably null;
	}

	if($tokenData) {
		$postUrl = esc_url(admin_url('admin-post.php'));
		$actionDisconnect = \oog\uitzendingen\Uitzending::ACTION_DISCONNECT;
		$actionGetCategories = \oog\uitzendingen\Uitzending::ACTION_GET_CATEGORIES;
		$nonce = wp_nonce_field(\oog\uitzendingen\Uitzending::NONCE, '_wp_nonce', true, false);
		echo <<<OOG
		<h2>Google account</h2>
		<p>Ingelogd met het volgende account:</p>
		<table class="form-table">
			<tr>
				<th scope="row">E-mail</th>
				<td>{$tokenData['email']}</td>
			</tr>
		</table>
				
				
		<table class="form-table">
		<tr>
			<th scope="row"><label for="oog-uitzendingen-load-categories">Categorieën laden / updaten</label></th>
			<td>
				<form method="post" action="{$postUrl}">
					{$nonce}
					<input type="hidden" name="action" value="{$actionGetCategories}">
					<input type="submit" id="oog-uitzendingen-load-categories" class="button" value="Laden">
				</form>
			</td>
		</tr>
			<tr>
				<th scope="row"><label for="oog-uitzendingen-deregister">Account ontkoppelen</label></th>
				<td>
					<form method="post" action="{$postUrl}">
						{$nonce}
						<input type="hidden" name="action" value="{$actionDisconnect}">
						<input type="submit" id="oog-uitzendingen-deregister" class="button button-danger danger" value="Ontkoppelen">
					</form>
				</td>
			</tr>
		</table>
OOG;

	} else {
	?>

		<h2>Google account koppelen</h2>
		<p><?= __('Nadat de secret & clientId zijn opgeslagen moet het account gekoppeld worden. Klik op de button om te koppelen');?></p>

		<table class="form-table">

			<tr>
				<th scope="row"><label for="oog-uitzendingen-register">Account koppelen</label></th>
				<td>
					<a href="<?= WP_PLUGIN_URL . '/ooguitzendingen/handlers/connect_google_account.php'?>" class="button">Koppelen</a>
				</td>
			</tr>
		</table>

	<?php
		}
	?>


</div>