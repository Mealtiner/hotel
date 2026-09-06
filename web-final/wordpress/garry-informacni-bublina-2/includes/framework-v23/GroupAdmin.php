<?php
/**
 * GARRY Embedded Framework 2.4 – společný Přehled a Info.
 */

namespace Garry\Embedded\Bublina2\V23;

if ( ! defined( 'ABSPATH' ) ) exit;

final class GroupAdmin {
	private static function participants() {
		$registry = isset( $GLOBALS[ Protocol::REGISTRY_KEY ] ) && is_array( $GLOBALS[ Protocol::REGISTRY_KEY ] ) ? $GLOBALS[ Protocol::REGISTRY_KEY ] : array();
		$items = isset( $registry['participants'] ) && is_array( $registry['participants'] ) ? $registry['participants'] : array();
		uasort( $items, function ( $a, $b ) {
			$pa = isset( $a['framework_priority'] ) ? (int) $a['framework_priority'] : 9999;
			$pb = isset( $b['framework_priority'] ) ? (int) $b['framework_priority'] : 9999;
			if ( $pa === $pb ) return strcmp( (string) $a['name'], (string) $b['name'] );
			return $pa - $pb;
		} );
		return $items;
	}

	/**
	 * Odstraní redundantní "GARRY – " (nebo "GARRY -") prefix z názvu
	 * pluginu pro zobrazení v seznamech uvnitř sdíleného GARRY shellu –
	 * slovo GARRY je už v hlavičce menu/stránky, opakovat ho u každé
	 * položky jen zbytečně prodlužuje nadpis.
	 */
	public static function display_name( $name ) {
		return preg_replace( '/^GARRY\s*[\x{2013}\x{2014}-]\s*/u', '', (string) $name );
	}

	public static function render_overview( array $config ) {
		if ( ! current_user_can( $config['capability'] ) ) return;
		$participants = self::participants();
		?>
		<div class="wrap garry-v24-wrap">
			<div class="garry-v24-heading">
				<img src="<?php echo esc_url( $config['logo_url'] ); ?>" alt="GARRY Promotion">
				<div><h1>GARRY nastavení</h1><p>Společné administrační místo pro zakázkové mikropluginy vytvořené agenturou GARRY Promotion.</p></div>
			</div>
			<h2>Aktivní GARRY mikropluginy na tomto webu</h2>
			<div class="garry-v24-grid">
				<?php foreach ( $participants as $plugin ) :
					$url = admin_url( 'admin.php?page=' . $plugin['local_menu_slug'] );
					$icon = isset( $plugin['icon'] ) ? sanitize_html_class( $plugin['icon'] ) : 'dashicons-admin-generic';
				?>
					<a class="garry-v24-plugin-card" href="<?php echo esc_url( $url ); ?>">
						<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
						<span><?php echo esc_html( self::display_name( $plugin['name'] ) ); ?></span>
						<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
					</a>
				<?php endforeach; ?>
			</div>

			<?php
			/**
			 * "Implementační dokumentace" (verze 2.4.1) – volitelné pole 'doc'
			 * v deskriptoru pluginu (HTML, wp_kses_post na výstupu). Obnovuje
			 * funkci ze staré Garry_Promotion_Registry (framework 2.1.0), která
			 * při migraci na Framework 2.4 nedopatřením vypadla – vlastníci webu
			 * si tu potřebují dohledat shortcody/widgety jednotlivých pluginů.
			 * Zobrazí se jen pluginy, které 'doc' skutečně deklarují.
			 */
			$doc_plugins = array_filter( $participants, function ( $p ) { return ! empty( $p['doc'] ); } );
			if ( ! empty( $doc_plugins ) ) :
			?>
				<h2>Implementační dokumentace (shortcody a widgety)</h2>
				<p class="description">Technické informace pro správce webu — kde se jednotlivé prvky vykreslují a jakými shortcody je lze vložit do obsahu.</p>
				<?php foreach ( $doc_plugins as $plugin ) : ?>
					<details class="garry-v24-doc">
						<summary><?php echo esc_html( self::display_name( $plugin['name'] ) ); ?></summary>
						<div><?php echo wp_kses_post( $plugin['doc'] ); ?></div>
					</details>
				<?php endforeach; ?>
			<?php endif; ?>

			<p class="garry-v24-footer">GARRY framework verze <?php echo esc_html( $config['framework_version'] ); ?>. GARRY Promotion / Michal Truhlář.</p>
		</div>
		<?php
	}

	public static function render_info( array $config ) {
		if ( ! current_user_can( $config['capability'] ) ) return;
		$participants = self::participants();
		?>
		<div class="wrap garry-v24-wrap">
			<div class="garry-v24-brand"><img src="<?php echo esc_url( $config['logo_url'] ); ?>" alt="GARRY Promotion"></div>
			<div class="garry-v24-info-grid">
				<section class="garry-v24-card"><h2>Kdo jsme</h2>
					<p><strong>GARRY Promotion</strong> je fluidní marketingová agentura, která propojuje offline marketing, datový online marketing a moderní AI marketing.</p>
					<p>Pracujeme koncepčně tak, aby každý nástroj v komunikaci klienta navazoval na zbytek.</p>
				</section>
				<section class="garry-v24-card"><h2>Co pro vás děláme</h2>
					<ul class="garry-v24-list">
						<li><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span><span>Tvorba <strong>webů na míru</strong> – WordPress, Divi a vlastní šablony</span></li>
						<li><span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span><span>Vývoj <strong>pluginů a webových aplikací</strong> přesně podle zadání</span></li>
						<li><span class="dashicons dashicons-car" aria-hidden="true"></span><span><strong>Polepy vozidel</strong>, fasád, výloh a velkoformátový tisk</span></li>
						<li><span class="dashicons dashicons-megaphone" aria-hidden="true"></span><span>Výroba <strong>offline reklamy a tiskovin</strong> – od vizitky po billboard</span></li>
						<li><span class="dashicons dashicons-chart-line" aria-hidden="true"></span><span><strong>Online marketing</strong>: SEO, PPC, sociální sítě, mailing</span></li>
						<li><span class="dashicons dashicons-update-alt" aria-hidden="true"></span><span><strong>Automatizace procesů</strong> a integrace mezi systémy</span></li>
						<li><span class="dashicons dashicons-database" aria-hidden="true"></span><span>Nastavení <strong>CRM, ERP a WMS</strong> systémů</span></li>
						<li><span class="dashicons dashicons-superhero" aria-hidden="true"></span><span><strong>AI marketing a vibecoding</strong> – nasazení AI nástrojů přímo do provozu firmy</span></li>
					</ul>
				</section>
				<section class="garry-v24-card"><h2>Kontakt</h2>
					<ul class="garry-v24-contact">
						<li>
							<span class="dashicons dashicons-admin-site" aria-hidden="true"></span>
							<span><span class="garry-v24-contact-label">Web agentury</span><a href="https://www.garry.cz" target="_blank" rel="noopener noreferrer">garry.cz</a></span>
						</li>
						<li>
							<span class="dashicons dashicons-businessman" aria-hidden="true"></span>
							<span><span class="garry-v24-contact-label">Realizace projektů</span><strong>Michal Truhlář</strong> · <a href="mailto:michal@garry.eu">michal@garry.eu</a></span>
						</li>
						<li>
							<span class="dashicons dashicons-sos" aria-hidden="true"></span>
							<span><span class="garry-v24-contact-label">Technická podpora</span><a href="mailto:podpora@garry.eu">podpora@garry.eu</a></span>
						</li>
					</ul>
				</section>
			</div>

			<?php if ( ! empty( $participants ) ) : ?>
				<div class="garry-v24-installed">
					<h2>Aktivní GARRY mikropluginy na tomto webu</h2>
					<ul>
						<?php foreach ( $participants as $plugin ) :
							$icon = isset( $plugin['icon'] ) ? sanitize_html_class( $plugin['icon'] ) : 'dashicons-admin-generic';
						?>
							<li>
								<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $plugin['local_menu_slug'] ) ); ?>"><?php echo esc_html( self::display_name( $plugin['name'] ) ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<p class="garry-v24-footer">GARRY framework verze <?php echo esc_html( $config['framework_version'] ); ?>. GARRY Promotion / Michal Truhlář.</p>
		</div>
		<?php
	}
}
