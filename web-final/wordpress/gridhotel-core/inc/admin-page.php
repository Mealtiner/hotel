<?php
/**
 * GARRY – GRID Core: vlastní admin stránka "Nastavení webu" (own_callback
 * pro Framework 2.4, viz gridhotel-core.php). Beze změny obsahu oproti
 * dřívější verzi z inc/garry.php – jen přesunuto sem, protože inc/garry.php
 * (vestavěná Garry_Promotion_Registry, framework 2.1.0) odpadl při přechodu
 * na Framework 2.4.
 *
 * Rozcestník – vlastní pole se needitují zde, jsou na ACF options stránce
 * grid-options pod menu "GRID Nastavení" (registruje motiv, ne tenhle
 * plugin). Tady je jen vstupní bod + rychlé odkazy.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function gridcore_garry_web_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$opts     = admin_url( 'admin.php?page=grid-options' );
	$has_opts = function_exists( 'acf_add_options_page' );
	$cpts = array(
		'grid_room'       => 'Pokoje',
		'grid_experience' => 'Zážitky',
		'grid_event'      => 'Akce sezóny',
		'grid_gastro'     => 'Gastro',
		'grid_testimonial'=> 'Reference',
	);
	?>
	<div class="wrap">
	  <h1><span class="dashicons dashicons-admin-settings" style="font-size:26px;width:26px;height:26px;margin-right:6px;color:#C20E1A"></span> Nastavení webu — GRID Hotel</h1>
	  <p style="max-width:70ch">Toto je rozcestník k <strong>nastavení webu</strong> GRID Hotel. Obecná nastavení (šířka, kontakt, hero, widget, formuláře, právní odkazy) žijí na ACF stránce <strong>„GRID Nastavení"</strong>. Datové typy (Pokoje, Zážitky, Akce, Gastro, Reference) zůstávají spravované ve svém menu <strong>GRID Nastavení</strong>.</p>

	  <div style="display:flex;gap:22px;flex-wrap:wrap;margin-top:22px">
	    <div style="flex:1;min-width:320px;max-width:520px;border:1px solid #dcdcde;border-radius:10px;padding:22px;background:#fff">
	      <h2 style="margin-top:0">Obecná nastavení webu</h2>
	      <p>Šířka webu, kontaktní údaje, hero, telemetrie/widget, propojení formulářů a právní odkazy.</p>
	      <?php if ( $has_opts ) : ?>
	        <a class="button button-primary button-hero" href="<?php echo esc_url( $opts ); ?>">Otevřít Nastavení webu →</a>
	      <?php else : ?>
	        <p class="notice notice-warning inline" style="padding:8px 12px">Vyžaduje <strong>ACF PRO</strong> (options stránka „GRID Nastavení").</p>
	      <?php endif; ?>
	    </div>

	    <div style="flex:1;min-width:320px;max-width:520px;border:1px solid #dcdcde;border-radius:10px;padding:22px;background:#fff">
	      <h2 style="margin-top:0">Obsah (zůstává v „GRID Nastavení")</h2>
	      <p>Datové typy se needitují zde — otevřou se ve svém obvyklém menu.</p>
	      <ul style="margin:0;list-style:none;padding:0">
	        <?php foreach ( $cpts as $pt => $lbl ) : ?>
	          <li style="margin:6px 0"><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $pt ) ); ?>"><span class="dashicons dashicons-arrow-right-alt2"></span> <?php echo esc_html( $lbl ); ?></a></li>
	        <?php endforeach; ?>
	        <li style="margin:6px 0"><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=grid_room_cat&post_type=grid_room' ) ); ?>"><span class="dashicons dashicons-arrow-right-alt2"></span> Kategorie pokojů</a></li>
	      </ul>
	    </div>

	    <div style="flex:1;min-width:320px;max-width:520px;border:1px solid #dcdcde;border-radius:10px;padding:22px;background:#fff">
	      <h2 style="margin-top:0">Technická administrace webu</h2>
	      <p>Moduly, přístupová práva, napojení formulářů a diagnostika — technická nastavení webu, ne obsah pro personál hotelu, proto zůstávají tady v GARRY Nastavení.</p>
	      <ul style="margin:0;list-style:none;padding:0">
	        <li style="margin:6px 0"><a href="<?php echo esc_url( admin_url( 'admin.php?page=gridcore-modules' ) ); ?>"><span class="dashicons dashicons-arrow-right-alt2"></span> Moduly</a></li>
	        <li style="margin:6px 0"><a href="<?php echo esc_url( admin_url( 'admin.php?page=gridcore-access' ) ); ?>"><span class="dashicons dashicons-arrow-right-alt2"></span> Přístupy</a></li>
	        <li style="margin:6px 0"><a href="<?php echo esc_url( admin_url( 'admin.php?page=gridcore-forms' ) ); ?>"><span class="dashicons dashicons-arrow-right-alt2"></span> Formuláře a integrace</a></li>
	        <li style="margin:6px 0"><a href="<?php echo esc_url( admin_url( 'admin.php?page=gridcore-diagnostics' ) ); ?>"><span class="dashicons dashicons-arrow-right-alt2"></span> Diagnostika</a></li>
	      </ul>
	    </div>
	  </div>

	  <p style="margin-top:22px;color:#666;font-size:12px">GARRY – GRID Core v<?php echo esc_html( defined( 'GRIDCORE_VER' ) ? GRIDCORE_VER : '' ); ?> — součást ekosystému GARRY Promotion.</p>
	</div>
	<?php
}
