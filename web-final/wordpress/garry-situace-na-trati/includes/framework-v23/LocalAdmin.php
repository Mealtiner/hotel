<?php
/**
 * GARRY Embedded Framework 2.3 – lokální administrace (Přehled/Info/Log).
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md, sekce 5.
 *
 * Vykresluje tři povinné lokální stránky tohoto pluginu, vždy dostupné bez
 * ohledu na to, kolik dalších GARRY pluginů je aktivních. Pokud plugin má
 * vlastní funkční stránku (nastavení apod.), zůstává jejím vlastníkem beze
 * změny – tahle třída ji jen zabalí jako výchozí záložku vedle Přehledu,
 * Infa a Logu; NEMĚNÍ nic na tom, jak plugin svou vlastní stránku vykresluje.
 */

namespace Garry\Embedded\SituaceNaTrati\V23;

if ( ! defined( 'ABSPATH' ) ) exit;

final class LocalAdmin {

	/**
	 * @param array $config {
	 *     @type string        $slug            Slug tohoto pluginu.
	 *     @type string        $name            Zobrazovaný název.
	 *     @type string        $display_name    Název pro nadpis stránky (může se lišit v roli root_menu ownera).
	 *     @type string        $menu_slug       Vlastní menu_slug pro add_query_arg.
	 *     @type string        $capability      WP capability.
	 *     @type callable|null $own_callback    Existující vykreslovací funkce pluginu (beze změny), nebo null.
	 *     @type string        $own_tab_label   Popisek záložky s vlastním obsahem pluginu.
	 *     @type string        $log_option      Option name pro LocalLog.
	 *     @type string        $plugin_dir      Adresář pluginu pro Manifest::load().
	 *     @type string        $plugin_version  Verze pluginu z hlavičky.
	 * }
	 */
	public static function render( array $config ) {
		if ( ! current_user_can( $config['capability'] ) ) {
			return;
		}

		$tab           = isset( $_GET['garry_v23_tab'] ) ? sanitize_key( wp_unslash( $_GET['garry_v23_tab'] ) ) : '';
		$allowed_tabs  = array( '', 'overview', 'info', 'log' );
		if ( ! in_array( $tab, $allowed_tabs, true ) ) {
			$tab = '';
		}

		echo '<div class="wrap garry-v23-wrap">';
		echo '<h1>' . esc_html( $config['display_name'] ) . '</h1>';
		self::render_tabs( $config, $tab );

		switch ( $tab ) {
			case 'overview':
				self::render_overview( $config );
				break;
			case 'info':
				self::render_info( $config );
				break;
			case 'log':
				self::render_log( $config );
				break;
			default:
				if ( ! empty( $config['own_callback'] ) && is_callable( $config['own_callback'] ) ) {
					call_user_func( $config['own_callback'] );
				} else {
					self::render_overview( $config );
				}
		}

		echo '</div>';
	}

	private static function render_tabs( array $config, $current ) {
		$base = admin_url( 'admin.php?page=' . $config['menu_slug'] );
		$tabs = array();
		if ( ! empty( $config['own_callback'] ) ) {
			$tabs[''] = $config['own_tab_label'];
		}
		$tabs['overview'] = 'Přehled';
		$tabs['info']     = 'Info';
		$tabs['log']      = 'Log';

		echo '<h2 class="nav-tab-wrapper garry-v23-tabs">';
		foreach ( $tabs as $key => $label ) {
			$url   = ( '' === $key ) ? $base : add_query_arg( 'garry_v23_tab', $key, $base );
			$class = 'nav-tab' . ( $current === $key ? ' nav-tab-active' : '' );
			printf( '<a href="%s" class="%s">%s</a>', esc_url( $url ), esc_attr( $class ), esc_html( $label ) );
		}
		echo '</h2>';
	}

	private static function render_overview( array $config ) {
		$owned_claims = Diagnostics::owned_claims( $config['slug'] );
		$others       = Diagnostics::other_participants( $config['slug'] );
		$legacy       = Diagnostics::has_legacy_framework();
		?>
		<div class="garry-v23-card">
			<p><strong><?php echo esc_html( $config['name'] ); ?></strong> — verze <?php echo esc_html( $config['plugin_version'] ); ?></p>
			<p>Embedded Framework: verze <?php echo esc_html( Protocol::VERSION ); ?>, protokol <?php echo esc_html( Protocol::VERSION ); ?>.</p>
			<?php if ( ! empty( $owned_claims ) ) : ?>
				<p>V tomto requestu je tento plugin vlastníkem: <code><?php echo esc_html( implode( ', ', $owned_claims ) ); ?></code>.</p>
			<?php else : ?>
				<p>V tomto requestu tento plugin nevlastní žádný sdílený claim (běžné, pokud je aktivní jiný účastník s vyšší prioritou nebo pokud tento plugin žádný claim nedeklaruje).</p>
			<?php endif; ?>

			<?php if ( ! empty( $others ) ) : ?>
				<p>Další aktivní účastníci Frameworku 2.3 na tomto webu:</p>
				<ul>
					<?php foreach ( $others as $p ) : ?>
						<li><?php echo esc_html( $p['name'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p>Žádný další GARRY plugin s Frameworkem 2.3 aktivní není — tento plugin běží samostatně.</p>
			<?php endif; ?>

			<?php if ( $legacy ) : ?>
				<div class="notice notice-warning inline"><p>Na webu byla detekována starší sdílená třída <code>Garry_Promotion_Registry</code> (framework 2.0/2.1) z jiného, dosud nemigrovaného pluginu. Framework 2.3 ji nijak neupravuje ani nevypíná.</p></div>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_info( array $config ) {
		$manifest = Manifest::load( $config['plugin_dir'] );
		?>
		<div class="garry-v23-card">
			<table class="widefat striped" style="max-width:760px">
				<tbody>
					<tr><th style="width:220px">Slug</th><td><code><?php echo esc_html( $config['slug'] ); ?></code></td></tr>
					<tr><th>Verze pluginu</th><td><?php echo esc_html( $config['plugin_version'] ); ?></td></tr>
					<tr><th>Framework protokol</th><td><?php echo esc_html( Protocol::VERSION ); ?></td></tr>
					<tr><th>Capability</th><td><code><?php echo esc_html( $config['capability'] ); ?></code></td></tr>
					<tr><th>Options prefix</th><td><code><?php echo esc_html( isset( $manifest['data']['settings_option_prefix'] ) ? $manifest['data']['settings_option_prefix'] : '—' ); ?></code></td></tr>
					<tr><th>Zpracovává osobní údaje</th><td><?php echo ! empty( $manifest['data']['stores_personal_data'] ) ? 'Ano' : 'Ne'; ?></td></tr>
					<tr><th>Retence logu</th><td><?php echo esc_html( isset( $manifest['data']['log_retention_days'] ) ? $manifest['data']['log_retention_days'] . ' dní' : '90 dní (výchozí)' ); ?></td></tr>
					<tr><th>Odinstalace</th><td><?php echo esc_html( isset( $manifest['data']['uninstall_behavior'] ) ? $manifest['data']['uninstall_behavior'] : '—' ); ?></td></tr>
					<tr><th>Volitelné integrace</th>
						<td>
							<?php
							$optional = isset( $manifest['dependencies']['optional'] ) && is_array( $manifest['dependencies']['optional'] ) ? $manifest['dependencies']['optional'] : array();
							echo $optional ? esc_html( implode( ', ', $optional ) ) : '—';
							?>
						</td>
					</tr>
					<tr><th>Dokumentace</th><td><?php echo esc_html( isset( $manifest['documentation']['local_path'] ) ? $manifest['documentation']['local_path'] : 'README.md' ); ?></td></tr>
				</tbody>
			</table>
			<p class="description">Autor: GARRY Promotion — <a href="https://www.garry.cz" target="_blank" rel="noopener noreferrer">garry.cz</a> · podpora: <a href="mailto:podpora@garry.eu">podpora@garry.eu</a></p>
		</div>

		<?php
		/**
		 * Historie verzi (standing pozadavek: kazda uprava pluginu se loguje
		 * i v adminu, ne jen v README.md). Nepovinne pole 'changelog' v
		 * deskriptoru (viz bootstrap.php) - prazdne, dokud ho plugin
		 * nezacne plnit.
		 */
		$changelog = isset( $config['changelog'] ) && is_array( $config['changelog'] ) ? $config['changelog'] : array();
		if ( ! empty( $changelog ) ) :
		?>
			<div class="garry-v23-card">
				<h2 style="margin-top:0">Historie verzí</h2>
				<table class="widefat striped" style="max-width:760px">
					<thead><tr><th style="width:100px">Verze</th><th style="width:120px">Datum</th><th>Co se změnilo</th></tr></thead>
					<tbody>
						<?php foreach ( $changelog as $entry ) : ?>
							<tr>
								<td><code><?php echo esc_html( $entry['version'] ?? '' ); ?></code></td>
								<td><?php echo esc_html( $entry['date'] ?? '' ); ?></td>
								<td><?php echo esc_html( $entry['notes'] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
	}

	private static function render_log( array $config ) {
		$entries = array_reverse( LocalLog::get_entries( $config['log_option'] ) );
		?>
		<div class="garry-v23-card">
			<p class="description">Lokální technický log tohoto pluginu. Max. 200 položek nebo 90 dní. Nikdy neobsahuje hesla, tokeny, IP adresy, e-maily ani obsah formulářů.</p>
			<table class="widefat striped">
				<thead><tr><th style="width:180px">Čas</th><th style="width:220px">Událost</th><th>Data</th></tr></thead>
				<tbody>
					<?php if ( empty( $entries ) ) : ?>
						<tr><td colspan="3">Zatím žádné záznamy.</td></tr>
					<?php else : foreach ( $entries as $entry ) : ?>
						<tr>
							<td><?php echo esc_html( gmdate( 'Y-m-d H:i', (int) $entry['time'] ) . ' UTC' ); ?></td>
							<td><code><?php echo esc_html( $entry['event'] ); ?></code></td>
							<td><?php echo esc_html( wp_json_encode( $entry['data'] ) ); ?></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
