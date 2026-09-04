<?php
/**
 * GARRY Security – administrační stránka: tři dashboard karty, tabulka
 * findings, tlačítko „Spustit scan nyní" (Fáze 1) a „Doporučené pluginy"
 * s bezpečnou instalací/aktivací + tlačítko na zálohu přes UpdraftPlus
 * (Fáze 2).
 *
 * Vykresluje se jako vlastní záložka „Zabezpečení" v lokální administraci
 * GARRY Default (GARRY Embedded Framework 2.3, viz garry-default.php a
 * docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md) – zápis do menu obstarává
 * FrameworkBridge, tahle třída jen dodává obsah a zpracovává akce.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Garry_Security_Admin' ) ) {

	class Garry_Security_Admin {

		/**
		 * Musí se shodovat s local_menu_slug deklarovaným ve 2.3 bootstrap()
		 * volání v garry-default.php (SLUG = 'garry-default') – FrameworkBridge
		 * registruje admin stránku pod tímto slugem, Zabezpečení je na ní
		 * výchozí (own_callback) záložka.
		 */
		const PAGE_SLUG = 'garry-default';

		public static function register() {
			add_action( 'admin_post_garry_security_run_scan', array( __CLASS__, 'handle_run_scan' ) );
			add_action( 'admin_head', array( __CLASS__, 'print_styles' ) );
		}

		public static function handle_run_scan() {
			if ( ! current_user_can( GARRY_SECURITY_CAP_RUN ) || ! check_admin_referer( 'garry_security_run_scan' ) ) {
				wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
			}

			Garry_Security_Scanner::run_scan( 'manual' );

			wp_safe_redirect( add_query_arg(
				array( 'page' => self::PAGE_SLUG, 'scanned' => 1 ),
				admin_url( 'admin.php' )
			) );
			exit;
		}

		public static function render_page() {
			if ( ! current_user_can( GARRY_SECURITY_CAP_VIEW ) ) {
				return;
			}

			$counts    = Garry_Security_Scanner::get_summary_counts();
			$findings  = Garry_Security_Scanner::get_findings();
			$last_scan = Garry_Security_Scanner::get_last_scan();
			$catalog   = garry_security_catalog();

			$critical = isset( $counts['critical'] ) ? $counts['critical'] : 0;
			$warning  = isset( $counts['warning'] ) ? $counts['warning'] : 0;
			$unknown  = isset( $counts['unknown'] ) ? $counts['unknown'] : 0;
			$conflict = isset( $counts['conflict'] ) ? $counts['conflict'] : 0;

			$updates_message = 'Zatím neznámo – spusťte scan.';
			foreach ( $findings as $finding ) {
				if ( 'UPD-001' === $finding->control_id ) {
					$updates_message = $finding->message;
					break;
				}
			}

			$view = ( isset( $_GET['security_view'] ) && 'technical' === $_GET['security_view'] ) ? 'technical' : 'main';
			?>
			<div class="wrap garry-security-wrap">
				<h1>Zabezpečení</h1>

				<?php if ( isset( $_GET['scanned'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Scan proběhl.</p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['backup_requested'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Záloha byla vyžádána. Dokončení ověřte v UpdraftPlus → Zálohy a obnovení.</p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['installed'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Plugin byl nainstalován. Nyní ho můžete aktivovat.</p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['activated'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Plugin byl aktivován.</p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['garry_error'] ) ) : ?>
					<div class="notice notice-error is-dismissible"><p><?php echo esc_html( rawurldecode( wp_unslash( $_GET['garry_error'] ) ) ); ?></p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['fix_applied'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Oprava <code><?php echo esc_html( sanitize_key( wp_unslash( $_GET['fix_applied'] ) ) ); ?></code> byla zapnuta a stav byl hned ověřen novým scanem (viz tabulka Kontroly níže).</p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['fix_reverted'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Oprava <code><?php echo esc_html( sanitize_key( wp_unslash( $_GET['fix_reverted'] ) ) ); ?></code> byla vypnuta a stav byl hned ověřen novým scanem.</p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['robots_llms_saved'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Nastavení robots.txt a llms.txt bylo uloženo.</p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['exception_set'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Schopnost <code><?php echo esc_html( sanitize_key( wp_unslash( $_GET['exception_set'] ) ) ); ?></code> byla označena jako nevyžadovaná pro tento web.</p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['exception_cleared'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Výjimka u schopnosti <code><?php echo esc_html( sanitize_key( wp_unslash( $_GET['exception_cleared'] ) ) ); ?></code> byla zrušena.</p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['restore_test_recorded'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Test obnovy byl zaznamenán.</p></div>
				<?php endif; ?>
				<?php if ( isset( $_GET['recovery_access_saved'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p>Kontakt pro obnovu po incidentu byl uložen.</p></div>
				<?php endif; ?>

				<h2 class="nav-tab-wrapper">
					<a href="<?php echo esc_url( remove_query_arg( 'security_view' ) ); ?>" class="nav-tab<?php echo 'main' === $view ? ' nav-tab-active' : ''; ?>">Přehled</a>
					<a href="<?php echo esc_url( add_query_arg( 'security_view', 'technical' ) ); ?>" class="nav-tab<?php echo 'technical' === $view ? ' nav-tab-active' : ''; ?>">Technické detaily</a>
				</h2>

				<?php if ( 'technical' === $view ) : ?>
					<?php self::render_ecosystem_summary_panel( $findings, $last_scan ); ?>
					<?php self::render_grouped_controls( $findings, $catalog ); ?>
					<?php self::render_environment_card(); ?>
					<?php self::render_recovery_access_card(); ?>
					<?php self::render_robots_llms(); ?>
					</div>
					<?php
					return;
				endif;
				?>

				<?php self::render_action_now( $findings ); ?>

				<div class="garry-security-cards garry-security-cards-5">
					<div class="garry-security-card">
						<h2>Přístup</h2>
						<?php self::render_access_summary( $findings ); ?>
					</div>

					<div class="garry-security-card">
						<h2>Ochrana</h2>
						<?php self::render_protection_summary(); ?>
					</div>

					<div class="garry-security-card">
						<h2>Aktualizace</h2>
						<p><?php echo esc_html( $updates_message ); ?></p>
					</div>

					<div class="garry-security-card">
						<h2>Zálohy</h2>
						<?php self::render_backup_status(); ?>
					</div>

					<div class="garry-security-card">
						<h2>Poslední bezpečnostní události</h2>
						<?php self::render_recent_events(); ?>
					</div>
				</div>

				<div class="garry-security-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="garry-security-scan-form">
						<input type="hidden" name="action" value="garry_security_run_scan">
						<?php wp_nonce_field( 'garry_security_run_scan' ); ?>
						<?php submit_button( 'Spustit scan nyní', 'secondary', 'submit', false, current_user_can( GARRY_SECURITY_CAP_RUN ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
					</form>

					<?php if ( garry_security_provider_is_active( 'updraftplus' ) ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="garry-security-scan-form">
							<input type="hidden" name="action" value="garry_security_trigger_backup">
							<?php wp_nonce_field( 'garry_security_trigger_backup' ); ?>
							<?php submit_button( 'Vytvořit bezpečnostní zálohu přes UpdraftPlus', 'secondary', 'submit', false, current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
						</form>
					<?php endif; ?>
				</div>

				<?php self::render_safe_remediation( $findings ); ?>

				<?php self::render_capability_matrix(); ?>

				<?php self::render_recommended_plugins(); ?>

				<?php self::render_findings_section( $findings, $catalog, $last_scan ); ?>
			</div>
			<?php
		}

		/**
		 * Sekce "Kontroly" – podle návrhu struktury bod 5 "Seznam kontrol":
		 * defaultně jen otevřené kritické/warning nálezy a nálezy změněné
		 * posledním scanem + souhrn počtů, s odkazem na plný výpis. Filtr
		 * podle stavu a kategorie je čistě serverový (GET parametry), žádné JS.
		 */
		private static function render_findings_section( array $findings, array $catalog, $last_scan ) {
			$counts = array();
			foreach ( $findings as $finding ) {
				$label = garry_security_state_label( $finding->state, $finding->severity );
				$counts[ $label ] = isset( $counts[ $label ] ) ? $counts[ $label ] + 1 : 1;
			}
			$summary_parts = array();
			foreach ( array( 'Kritické', 'Konflikt', 'Vyžaduje akci', 'Zkontrolovat', 'V pořádku', 'Neplatí' ) as $label ) {
				if ( ! empty( $counts[ $label ] ) ) {
					$summary_parts[] = $counts[ $label ] . ' ' . mb_strtolower( $label, 'UTF-8' );
				}
			}

			$show_all    = isset( $_GET['show_all'] ) && '1' === $_GET['show_all'];
			$f_state     = isset( $_GET['f_state'] ) ? sanitize_key( wp_unslash( $_GET['f_state'] ) ) : '';
			$f_category  = isset( $_GET['f_category'] ) ? sanitize_text_field( wp_unslash( $_GET['f_category'] ) ) : '';
			$recent_cutoff = $last_scan ? $last_scan->started_at : null;

			$categories = array();
			foreach ( $catalog as $entry ) {
				if ( ! empty( $entry['category'] ) ) {
					$categories[ $entry['category'] ] = true;
				}
			}
			ksort( $categories );

			$visible = array_filter( $findings, function ( $finding ) use ( $show_all, $recent_cutoff, $f_state, $f_category, $catalog ) {
				if ( '' !== $f_state && $finding->state !== $f_state ) {
					return false;
				}
				if ( '' !== $f_category ) {
					$cat = isset( $catalog[ $finding->control_id ]['category'] ) ? $catalog[ $finding->control_id ]['category'] : '';
					if ( $cat !== $f_category ) {
						return false;
					}
				}
				if ( $show_all || '' !== $f_state || '' !== $f_category ) {
					return true;
				}
				$is_open   = ! in_array( $finding->state, array( 'pass', 'not_applicable', 'delegated' ), true );
				$is_recent = $recent_cutoff && $finding->changed_at >= $recent_cutoff;
				return $is_open || $is_recent;
			} );
			?>
			<h2 class="title">Kontroly</h2>
			<p class="description">
				<?php echo esc_html( empty( $summary_parts ) ? 'Zatím žádné výsledky. Spusťte první scan.' : implode( ', ', $summary_parts ) . '.' ); ?>
				<?php if ( ! $show_all && '' === $f_state && '' === $f_category ) : ?>
					Zobrazeny jen otevřené a nově změněné nálezy.
					<a href="<?php echo esc_url( add_query_arg( 'show_all', '1' ) ); ?>">Zobrazit všechny kontroly (<?php echo (int) count( $findings ); ?>)</a>
				<?php else : ?>
					<a href="<?php echo esc_url( remove_query_arg( array( 'show_all', 'f_state', 'f_category' ) ) ); ?>">Zpět na výchozí zobrazení</a>
				<?php endif; ?>
			</p>

			<form method="get" class="garry-security-findings-filter">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
				<select name="f_state">
					<option value="">Všechny stavy</option>
					<?php foreach ( array( 'critical' => 'Kritické', 'conflict' => 'Konflikt', 'warning' => 'Vyžaduje akci / Zkontrolovat', 'unknown' => 'Zkontrolovat', 'pass' => 'V pořádku', 'not_applicable' => 'Neplatí', 'delegated' => 'Ověřeno' ) as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $f_state, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="f_category">
					<option value="">Všechny kategorie</option>
					<?php foreach ( array_keys( $categories ) as $cat ) : ?>
						<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $f_category, $cat ); ?>><?php echo esc_html( $cat ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( 'Filtrovat', 'secondary', 'submit', false ); ?>
			</form>

			<table class="widefat striped garry-security-findings">
				<thead>
					<tr>
						<th>ID</th>
						<th>Kontrola</th>
						<th>Stav</th>
						<th>Závažnost</th>
						<th>Zpráva</th>
						<th>Naposledy viděno</th>
						<th>Náprava</th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $visible ) ) : ?>
					<tr><td colspan="7"><?php echo empty( $findings ) ? 'Zatím žádné výsledky. Spusťte první scan.' : 'Žádné nálezy neodpovídají zvolenému filtru.'; ?></td></tr>
				<?php else : ?>
					<?php foreach ( $visible as $finding ) : ?>
						<?php $title = isset( $catalog[ $finding->control_id ]['title'] ) ? $catalog[ $finding->control_id ]['title'] : $finding->control_id; ?>
						<tr>
							<td><code><?php echo esc_html( $finding->control_id ); ?></code></td>
							<td><?php echo esc_html( $title ); ?></td>
							<td><span class="garry-security-pill is-<?php echo esc_attr( $finding->state ); ?>"><?php echo esc_html( self::state_label( $finding ) ); ?></span></td>
							<td><?php echo esc_html( $finding->severity ); ?></td>
							<td><?php echo esc_html( $finding->message ); ?></td>
							<td><?php echo esc_html( $finding->last_seen ); ?></td>
							<td><?php self::render_finding_remediation( $finding ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
			<?php
		}

		/**
		 * "Akce nyní" – maximálně 3 nejnaléhavější položky (critical/
		 * conflict, pak warning s vysokou závažností), s odkazem přímo na
		 * příslušnou akci. Nahrazuje dlouhý výpis PASS/WARNING/CRITICAL
		 * jako první věc, kterou administrátor vidí.
		 */
		private static function render_action_now( array $findings ) {
			$catalog    = garry_security_catalog();
			$candidates = array();
			foreach ( $findings as $finding ) {
				if ( in_array( $finding->state, array( 'critical', 'conflict' ), true ) ) {
					$candidates[] = array( 'finding' => $finding, 'rank' => 0 );
				} elseif ( 'warning' === $finding->state && in_array( $finding->severity, array( 'high', 'critical' ), true ) ) {
					$candidates[] = array( 'finding' => $finding, 'rank' => 1 );
				} elseif ( in_array( $finding->state, array( 'warning', 'unknown' ), true ) ) {
					/**
					 * Rank 2 – "Zkontrolovat" úroveň (zpětná vazba: i nález
					 * jako ENV-007 s chybějícím zpevněním .htaccess sem patří,
					 * ne jen do dlouhé tabulky Kontroly níže). Doplní se jen
					 * do zbylých volných míst za naléhavějšími položkami.
					 */
					$candidates[] = array( 'finding' => $finding, 'rank' => 2 );
				}
			}
			usort( $candidates, function ( $a, $b ) { return $a['rank'] <=> $b['rank']; } );
			$top = array_slice( $candidates, 0, 3 );
			?>
			<div class="garry-security-action-now">
				<h2 class="title">Akce nyní</h2>
				<?php if ( empty( $top ) ) : ?>
					<p class="description">Žádná naléhavá položka. Podrobný přehled je v tabulce Kontroly níže.</p>
				<?php else : ?>
					<ul class="garry-security-action-now-list">
						<?php foreach ( $top as $item ) :
							$finding = $item['finding'];
							$title   = isset( $catalog[ $finding->control_id ]['title'] ) ? $catalog[ $finding->control_id ]['title'] : $finding->control_id;
						?>
							<li class="garry-security-action-now-item is-<?php echo esc_attr( $finding->state ); ?>">
								<div>
									<strong><?php echo esc_html( $title ); ?></strong>
									<p class="description"><?php echo esc_html( $finding->message ); ?></p>
								</div>
								<div class="garry-security-action-now-cta"><?php self::render_finding_remediation( $finding ); ?></div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<?php
		}

		/** Karta "Přístup" – počet administrátorů + ověřený stav Turnstile. */
		private static function render_access_summary( array $findings ) {
			$by_id = array();
			foreach ( $findings as $f ) {
				$by_id[ $f->control_id ] = $f;
			}
			if ( isset( $by_id['USR-001'] ) ) {
				echo '<p>' . esc_html( $by_id['USR-001']->message ) . '</p>';
			} else {
				echo '<p class="description">Zatím neznámo – spusťte scan.</p>';
			}
			if ( isset( $by_id['USR-003'] ) && 'pass' !== $by_id['USR-003']->state ) {
				echo '<p><span class="garry-security-pill is-critical">shoda API/DB</span> ' . esc_html( $by_id['USR-003']->message ) . '</p>';
			}
			if ( isset( $by_id['PROV-TURNSTILE-CONFIG'] ) && 'not_applicable' !== $by_id['PROV-TURNSTILE-CONFIG']->state ) {
				$state = $by_id['PROV-TURNSTILE-CONFIG']->state;
				echo '<p>Turnstile na loginu: <span class="garry-security-pill is-' . esc_attr( $state ) . '">' . ( 'pass' === $state ? 'ověřeno' : esc_html( garry_security_state_label( $state, $by_id['PROV-TURNSTILE-CONFIG']->severity ) ) ) . '</span></p>';
			}
			if ( isset( $by_id['ACC-001'] ) ) {
				echo '<p class="description">' . esc_html( $by_id['ACC-001']->message ) . '</p>';
			}
			if ( isset( $by_id['ACC-002'] ) && 'pass' !== $by_id['ACC-002']->state ) {
				echo '<p><span class="garry-security-pill is-warning">neaktivní účty</span> ' . esc_html( $by_id['ACC-002']->message ) . '</p>';
			}
		}

		/** Karta "Ochrana" – shrnutí pokrytí schopností z kapacitní matice. */
		private static function render_protection_summary() {
			$matrix    = garry_security_capability_matrix();
			$verified  = $unverified = $conflict = $missing = $exempted = 0;
			foreach ( $matrix as $row ) {
				if ( 'verified_ok' === $row['state'] ) { $verified++; }
				elseif ( in_array( $row['state'], array( 'verified_attention', 'unverified' ), true ) ) { $unverified++; }
				elseif ( 'conflict' === $row['state'] ) { $conflict++; }
				elseif ( 'exempted' === $row['state'] ) { $exempted++; }
				else { $missing++; }
			}
			printf(
				'<p>V pořádku: <strong>%1$d</strong> / Zajištěno, čeká na ověření: <strong>%2$d</strong> / Konflikt: <strong>%3$d</strong> / Chybí: <strong>%4$d</strong> / Nevyžadováno: <strong>%5$d</strong></p>',
				$verified, $unverified, $conflict, $missing, $exempted
			);
			echo '<p class="description">Podrobnosti v sekci „Pokrytí bezpečnostních schopností" níže.</p>';
		}

		/**
		 * Karta "Poslední bezpečnostní události" – posledních 5 řádků z
		 * events, lidsky popsaných (zpětná vazba: interní ID jako WEB-006
		 * patří do detailu, ne do karty). Technické ID zůstává v title
		 * atributu pro toho, kdo ho hledá v tabulce Kontroly.
		 */
		private static function render_recent_events() {
			if ( ! class_exists( 'Garry_Security_Scanner' ) || ! method_exists( 'Garry_Security_Scanner', 'get_recent_events' ) ) {
				return;
			}
			$events = Garry_Security_Scanner::get_recent_events( 5 );
			if ( empty( $events ) ) {
				echo '<p class="description">Zatím žádné události.</p>';
				return;
			}
			$catalog     = garry_security_catalog();
			$type_labels = array(
				'created'  => 'Nový nález',
				'changed'  => 'Změna stavu',
				'resolved' => 'Vyřešeno',
			);
			$state_labels = array(
				'critical' => 'kritické', 'conflict' => 'konflikt', 'warning' => 'vyžaduje pozornost',
				'unknown' => 'ke kontrole', 'pass' => 'v pořádku', 'not_applicable' => 'neplatí', 'delegated' => 'ověřeno',
			);
			echo '<ul class="garry-security-events-list">';
			foreach ( $events as $event ) {
				$type_label  = isset( $type_labels[ $event->event_type ] ) ? $type_labels[ $event->event_type ] : $event->event_type;
				$title       = isset( $catalog[ $event->control_id ]['title'] ) ? $catalog[ $event->control_id ]['title'] : $event->control_id;
				$state_label = $event->new_state && isset( $state_labels[ $event->new_state ] ) ? $state_labels[ $event->new_state ] : '';
				printf(
					'<li title="%1$s">%2$s: %3$s%4$s <span class="description">%5$s</span></li>',
					esc_attr( $event->control_id ),
					esc_html( $type_label ),
					esc_html( $title ),
					$state_label ? ' → ' . esc_html( $state_label ) : '',
					esc_html( $event->event_time )
				);
			}
			echo '</ul>';
		}

		/**
		 * Srozumitelnější popisek stavu pro zobrazení (zpětná vazba:
		 * "DELEGATED přejmenovat na srozumitelnější Ověřeno přes Wordfence").
		 * `delegated` znamená "tuhle kontrolu za GARRY pokrývá jiný aktivní
		 * plugin" – ukáže se který, pokud ho GARRY umí bezpečně určit
		 * (z evidence.owner, nebo z PROV-<SLUG> control_id). Ostatní stavy
		 * beze změny.
		 */
		private static function state_label( $finding ) {
			if ( 'delegated' !== $finding->state ) {
				return garry_security_state_label( $finding->state, $finding->severity );
			}
			$registry = garry_security_provider_registry();
			$evidence = json_decode( $finding->evidence, true );

			if ( is_array( $evidence ) && ! empty( $evidence['owner'] ) && isset( $registry[ $evidence['owner'] ]['label'] ) ) {
				return 'Ověřeno přes ' . $registry[ $evidence['owner'] ]['label'];
			}
			if ( 0 === strpos( $finding->control_id, 'PROV-' ) ) {
				$slug = strtolower( str_replace( '_', '-', substr( $finding->control_id, 5 ) ) );
				if ( isset( $registry[ $slug ]['label'] ) ) {
					return 'Ověřeno přes ' . $registry[ $slug ]['label'];
				}
			}
			return 'Delegováno';
		}

		/**
		 * Sloupec "Náprava" v tabulce Kontroly – odkáže na příslušnou akci
		 * jinde na stránce, aby administrátor nemusel sám hledat, čím se
		 * konkrétní nález řeší (uživatelská zpětná vazba: "u ní bylo řečeno,
		 * který plugin z doporučených toto řeší a tlačítko na instalaci").
		 */
		private static function render_finding_remediation( $finding ) {
			if ( in_array( $finding->state, array( 'pass', 'not_applicable', 'delegated' ), true ) ) {
				echo '<span class="description">—</span>';
				return;
			}

			if ( isset( Garry_Security_Hardening_Fixes::catalog()[ $finding->control_id ] ) ) {
				echo '<a href="#garry-fix-' . esc_attr( strtolower( $finding->control_id ) ) . '">Doporučené kroky ↓</a>';
				return;
			}

			if ( 'UPD-001' === $finding->control_id ) {
				echo '<a class="button button-small" href="' . esc_url( admin_url( 'plugins.php?plugin_status=upgrade' ) ) . '">Zobrazit aktualizace</a>';
				return;
			}

			if ( 'ENV-007' === $finding->control_id ) {
				$url = add_query_arg( array( 'page' => self::PAGE_SLUG, 'security_view' => 'technical' ), admin_url( 'admin.php' ) ) . '#garry-htaccess-card';
				echo '<a class="button button-small" href="' . esc_url( $url ) . '">Zobrazit .htaccess</a>';
				return;
			}

			$provider_map = array( 'GARRY-006' => 'simple-cloudflare-turnstile' );
			if ( isset( $provider_map[ $finding->control_id ] ) ) {
				$slug  = $provider_map[ $finding->control_id ];
				$info  = garry_security_provider_registry()[ $slug ];
				$state = garry_security_provider_ladder_state( $slug );

				if ( 'recommended' === $state && current_user_can( 'install_plugins' ) && current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) ) {
					?>
					<span class="description"><?php echo esc_html( $info['label'] ); ?> řeší tenhle nález.</span>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:6px">
						<input type="hidden" name="action" value="garry_security_install_plugin">
						<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
						<?php wp_nonce_field( 'garry_security_install_plugin' ); ?>
						<?php submit_button( 'Instalovat ' . $info['label'], 'secondary small', 'submit', false ); ?>
					</form>
					<?php
				} elseif ( 'installed' === $state && current_user_can( 'activate_plugins' ) && current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) ) {
					?>
					<span class="description"><?php echo esc_html( $info['label'] ); ?> je nainstalovaný.</span>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:6px">
						<input type="hidden" name="action" value="garry_security_activate_plugin">
						<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
						<?php wp_nonce_field( 'garry_security_activate_plugin' ); ?>
						<?php submit_button( 'Aktivovat ' . $info['label'], 'secondary small', 'submit', false ); ?>
					</form>
					<?php
				} else {
					echo '<span class="description">' . esc_html( $info['label'] ) . ' aktivní – ověřte propojení na tenhle formulář.</span>';
				}
				return;
			}

			echo '<span class="description">—</span>';
		}

		/**
		 * Souhrnný panel "GARRY ekosystém" (návrh struktury, sekce "GARRY
		 * ekosystém") – metriky napříč aktivními GARRY pluginy + tabulka
		 * modulů, nad detailními kontrolami GARRY-001..013 níže.
		 */
		private static function render_ecosystem_summary_panel( array $findings, $last_scan ) {
			$data    = garry_security_collect_ecosystem_modules();
			$summary = $data['summary'];
			$modules = $data['modules'];

			$conflicts = 0;
			foreach ( $findings as $finding ) {
				if ( 'GARRY-001' === $finding->control_id && 'pass' !== $finding->state ) {
					$conflicts = 1;
					break;
				}
			}
			?>
			<h2 class="title">GARRY ekosystém</h2>
			<table class="widefat striped garry-security-ecosystem-summary">
				<tbody>
					<tr><th style="width:260px">Aktivní GARRY moduly</th><td><?php echo (int) $summary['active_count']; ?></td></tr>
					<tr><th>Kompatibilní moduly</th><td><?php echo (int) $summary['compatible_count'] . ' / ' . (int) $summary['active_count']; ?></td></tr>
					<tr><th>Konflikty frameworku</th><td><?php echo (int) $conflicts; ?></td></tr>
					<tr><th>Manifesty odpovídají instalaci</th><td><?php echo (int) $summary['manifest_ok_count'] . ' / ' . (int) $summary['active_count']; ?></td></tr>
					<tr><th>Čekající aktualizace GARRY</th><td><?php echo (int) $summary['pending_updates']; ?></td></tr>
					<tr><th>Zastaralé GARRY moduly (dle katalogu verzí)</th><td><?php echo (int) $summary['outdated_count']; ?> / <?php echo (int) $summary['active_count']; ?></td></tr>
					<tr><th>Poslední kontrola ekosystému</th><td><?php echo $last_scan ? esc_html( $last_scan->finished_at . ' UTC' ) : 'Zatím neproběhl scan.'; ?></td></tr>
				</tbody>
			</table>

			<table class="widefat striped garry-security-ecosystem-modules">
				<thead>
					<tr>
						<th>Modul</th>
						<th>Verze</th>
						<th>Framework / požadavek</th>
						<th>Stav</th>
						<th>Poslední změna souborů</th>
						<th>Akce</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $modules ) ) : ?>
						<tr><td colspan="6">Žádné aktivní GARRY pluginy nebyly nalezeny.</td></tr>
					<?php else : ?>
						<?php foreach ( $modules as $module ) : ?>
							<tr>
								<td><?php echo esc_html( $module['name'] ); ?><?php echo $module['pending_update'] ? ' <span class="garry-security-pill is-warning">aktualizace</span>' : ''; ?></td>
								<td>
									<?php echo esc_html( $module['version'] ); ?>
									<?php if ( $module['is_outdated'] ) : ?>
										<br><span class="garry-security-pill is-warning" title="Nejnovější známá verze podle katalogu GARRY Security">novější: <?php echo esc_html( $module['latest_known'] ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $module['framework'] ); ?> / <?php echo esc_html( $module['framework_min'] ); ?></td>
								<td>
									<?php
									$state_map = array( 'compatible' => 'pass', 'incompatible' => 'warning', 'unknown' => 'unknown' );
									$text_map  = array( 'compatible' => 'kompatibilní', 'incompatible' => 'nekompatibilní', 'unknown' => 'neznámý' );
									?>
									<span class="garry-security-pill is-<?php echo esc_attr( $state_map[ $module['compat_state'] ] ); ?>"><?php echo esc_html( $text_map[ $module['compat_state'] ] ); ?></span>
								</td>
								<td><?php echo $module['last_change'] ? esc_html( gmdate( 'Y-m-d', $module['last_change'] ) ) : '—'; ?></td>
								<td><?php echo $module['admin_url'] ? '<a href="' . esc_url( $module['admin_url'] ) . '">Detail</a>' : '—'; ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<p class="description">„Poslední změna souborů" je čas poslední úpravy manifestu na disku, ne runtime aktivita – GARRY telemetrii používání jiných pluginů nesbírá. Sloupec „novější" vychází z ručně vedeného katalogu nejnovějších verzí (viz includes/security/security-version-catalog.php) – GARRY pluginy nemají skutečný update server, takže WordPress sám o zastaralé verzi neví.</p>
			<?php
		}

		/**
		 * Detailní kontroly GARRY-001..013 seskupené podle návrhu struktury
		 * do pěti rozbalovacích bloků (Framework a manifest / Admin assety
		 * a CSP / Veřejné vstupy / Data a retence / Výkon a Multisite) místo
		 * dvou plochých tabulek – snadněji se v tom hledá konkrétní nález.
		 */
		private static function render_grouped_controls( array $findings, array $catalog ) {
			$groups = array(
				'Framework a manifest' => array( 'GARRY-001', 'GARRY-002', 'GARRY-003', 'GARRY-012', 'GARRY-013' ),
				'Admin assety a CSP'   => array( 'GARRY-004', 'GARRY-005', 'GARRY-007' ),
				'Veřejné vstupy'       => array( 'GARRY-006', 'GARRY-009' ),
				'Data a retence'       => array( 'GARRY-010' ),
				'Výkon a Multisite'    => array( 'GARRY-008', 'GARRY-011' ),
			);
			$by_id = array();
			foreach ( $findings as $finding ) {
				$by_id[ $finding->control_id ] = $finding;
			}
			?>
			<h2 class="title">Rozšířený audit ekosystému GARRY pluginů</h2>
			<p class="description">Kontroly nad lokálními manifesty aktivních GARRY pluginů (GARRY-001..013, viz docs/GARRY-WP-SECURITY-AUDIT-REMEDIATION-ROADMAP.md, Fáze C). V první iteraci jen měří a vysvětluje stav, samy nic neopravují.</p>
			<?php foreach ( $groups as $group_name => $ids ) : ?>
				<details class="garry-security-control-group">
					<summary><?php echo esc_html( $group_name ); ?></summary>
					<table class="widefat striped garry-security-ecosystem">
						<tbody>
						<?php foreach ( $ids as $id ) :
							$finding = isset( $by_id[ $id ] ) ? $by_id[ $id ] : null;
							$title   = isset( $catalog[ $id ]['title'] ) ? $catalog[ $id ]['title'] : $id;
						?>
							<tr>
								<td style="width:110px"><code><?php echo esc_html( $id ); ?></code></td>
								<td><?php echo esc_html( $title ); ?></td>
								<td style="width:150px">
									<?php if ( $finding ) : ?>
										<span class="garry-security-pill is-<?php echo esc_attr( $finding->state ); ?>"><?php echo esc_html( self::state_label( $finding ) ); ?></span>
									<?php else : ?>
										<span class="garry-security-pill is-unknown">Zkontrolovat</span>
									<?php endif; ?>
								</td>
								<td><?php echo $finding ? esc_html( $finding->message ) : 'Zatím neproběhl scan.'; ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</details>
			<?php endforeach; ?>
			<?php
		}

		/**
		 * Doporučené kroky (Fáze D, bod 6). Dvě různé cesty podle toho, jestli
		 * je oprava bezpečně proveditelná přes GARRY-vlastněný mu-plugin
		 * (viz Garry_Security_Hardening_Fixes – tlačítko Zapnout/Vypnout,
		 * které opravu skutečně provede a hned ověří novým scanem), nebo ne
		 * (CORE-005/WP_DEBUG – jen wp-config.php, viz docblock v
		 * class-garry-security-hardening-fixes.php proč to mu-pluginem nejde
		 * spolehlivě opravit) – tam zůstává jen kopírovatelný snippet a
		 * GARRY do wp-config.php sám nikdy nezapisuje.
		 */
		private static function wp_config_only_catalog() {
			return array(
				'CORE-005' => array(
					'title'   => 'Vypnout WP_DEBUG na produkci',
					'what'    => 'WP_DEBUG je hlavní přepínač WordPressu pro ladicí režim – zapíná podrobné chybové výpisy, upozornění na zastaralé funkce a další ladicí informace přímo v běhu webu.',
					'why'     => 'Na produkčním webu WP_DEBUG prozrazuje technické detaily (verze komponent, cesty, strukturu kódu) komukoli, kdo narazí na chybu – i neúmyslně. Je to první věc, kterou útočník při průzkumu webu hledá.',
					'snippet' => "define( 'WP_DEBUG', false );",
				),
			);
		}

		private static function render_safe_remediation( array $findings ) {
			$fixable    = Garry_Security_Hardening_Fixes::catalog();
			$config_only = self::wp_config_only_catalog();
			$by_id      = array();
			foreach ( $findings as $finding ) {
				if ( ( isset( $fixable[ $finding->control_id ] ) || isset( $config_only[ $finding->control_id ] ) )
					&& ! in_array( $finding->state, array( 'pass', 'not_applicable' ), true ) ) {
					$by_id[ $finding->control_id ] = $finding;
				}
			}
			if ( empty( $by_id ) ) {
				return;
			}
			?>
			<h2 class="title">Doporučené kroky</h2>
			<?php foreach ( $by_id as $id => $finding ) :
				$anchor_id = 'garry-fix-' . strtolower( $id );
				if ( isset( $fixable[ $id ] ) ) :
					$step    = $fixable[ $id ];
					$applied = Garry_Security_Hardening_Fixes::is_applied( $id );
					?>
					<div class="garry-security-card garry-security-fix-card" id="<?php echo esc_attr( $anchor_id ); ?>">
						<p>
							<strong><?php echo esc_html( $step['title'] ); ?></strong> (<code><?php echo esc_html( $id ); ?></code>)
							<span class="garry-security-pill is-<?php echo $applied ? 'pass' : esc_attr( $finding->state ); ?>"><?php echo $applied ? 'zapnuto' : 'vypnuto'; ?></span>
						</p>
						<p class="description"><strong>Co to dělá:</strong> <?php echo esc_html( $step['what'] ); ?></p>
						<p class="description"><strong>Proč na tom záleží:</strong> <?php echo esc_html( $step['why'] ); ?></p>
						<details>
							<summary>Zobrazit přesný kód opravy</summary>
							<code style="display:block;padding:8px 10px;margin-top:6px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:3px"><?php echo esc_html( $step['mu_snippet'] ); ?></code>
							<p class="description">GARRY tenhle řádek vloží do vlastního souboru <code>wp-content/mu-plugins/garry-security-hardening.php</code> – ne do <code>wp-config.php</code>. Soubor spravuje jen GARRY, lze ho kdykoli bezpečně smazat přes FTP a zbytek webu zůstane funkční.</p>
						</details>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px">
							<input type="hidden" name="control_id" value="<?php echo esc_attr( $id ); ?>">
							<?php if ( $applied ) : ?>
								<input type="hidden" name="action" value="garry_security_revert_fix">
								<?php wp_nonce_field( 'garry_security_revert_fix' ); ?>
								<?php submit_button( 'Vypnout opravu', 'secondary', 'submit', false, current_user_can( GARRY_SECURITY_CAP_APPLY_SAFE ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
							<?php else : ?>
								<input type="hidden" name="action" value="garry_security_apply_fix">
								<?php wp_nonce_field( 'garry_security_apply_fix' ); ?>
								<?php submit_button( 'Zapnout opravu', 'primary', 'submit', false, current_user_can( GARRY_SECURITY_CAP_APPLY_SAFE ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
							<?php endif; ?>
						</form>
					</div>
				<?php else :
					$step = $config_only[ $id ];
					?>
					<div class="garry-security-card garry-security-fix-card" id="<?php echo esc_attr( $anchor_id ); ?>">
						<p><strong><?php echo esc_html( $step['title'] ); ?></strong> (<code><?php echo esc_html( $id ); ?></code>)</p>
						<p class="description"><strong>Co to dělá:</strong> <?php echo esc_html( $step['what'] ); ?></p>
						<p class="description"><strong>Proč na tom záleží:</strong> <?php echo esc_html( $step['why'] ); ?></p>
						<p class="description">GARRY tohle nezapisuje samo (jde jedině přes <code>wp-config.php</code>, viz vysvětlení výše u ostatních oprav, proč GARRY do něj nikdy nezapisuje). Vložte řádek ručně nad <code>/* That's all, stop editing! */</code> a znovu spusťte scan:</p>
						<code style="display:block;padding:8px 10px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:3px"><?php echo esc_html( $step['snippet'] ); ?></code>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php
		}

		/**
		 * Karta "Zálohy" – kromě vyžádání a poslední historie (Fáze D) teď i
		 * best-effort konfigurace (vzdálené úložiště, retence) a hlavně
		 * ruční záznam testu obnovy (P0 "Obnovitelnost zálohy" – bez něj je
		 * "záloha existuje" jiné tvrzení než "obnova je ověřená").
		 */
		private static function render_backup_status() {
			$last         = Garry_Security_Backup_Gate::get_last_request();
			$hint         = Garry_Security_Backup_Gate::get_own_history_hint();
			$config       = Garry_Security_Backup_Gate::get_backup_configuration_hint();
			$restore_test = Garry_Security_Backup_Gate::get_restore_test();

			if ( ! $last && ! $hint ) {
				echo '<p class="description">Zatím nebyla přes GARRY vyžádána žádná záloha.</p>';
			}

			if ( $last ) {
				printf(
					'<p class="description">Poslední vyžádání zálohy přes GARRY: %s.</p>',
					esc_html( gmdate( 'Y-m-d H:i', (int) $last['requested_at'] ) . ' UTC' )
				);
			}

			if ( $hint ) {
				printf(
					'<p class="description">Poslední záznam ve vlastní historii UpdraftPlus: %1$s (celkem %2$d uložených sad). Best-effort odhad z veřejně dostupné option – dokončení a obsah zálohy vždy ověřte přímo v UpdraftPlus → Zálohy a obnovení.</p>',
					esc_html( gmdate( 'Y-m-d H:i', $hint['last_backup_set_time'] ) . ' UTC' ),
					(int) $hint['sets_recorded']
				);
			} elseif ( $last ) {
				echo '<p class="description">GARRY zatím v UpdraftPlus žádný záznam historie nenašel – stav ověřte přímo v UpdraftPlus.</p>';
			}

			if ( $config ) {
				printf(
					'<p>Typ: soubory + databáze (UpdraftPlus výchozí) · Úložiště: <strong>%1$s</strong>%2$s</p>',
					! empty( $config['remote_services'] ) ? esc_html( implode( ', ', $config['remote_services'] ) ) : 'jen lokální',
					null !== $config['retain_files'] ? ' · Retence: <strong>' . (int) $config['retain_files'] . '×</strong>' : ''
				);
			}

			if ( $restore_test ) {
				$days = (int) floor( ( time() - $restore_test['recorded_at'] ) / DAY_IN_SECONDS );
				printf(
					'<p>Poslední test obnovy: <span class="garry-security-pill is-%1$s">před %2$d dny</span></p>',
					$days > 180 ? 'warning' : 'pass',
					$days
				);
			} else {
				echo '<p><span class="garry-security-pill is-warning">Test obnovy nezaznamenán</span></p>';
			}

			if ( current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) ) {
				?>
				<details>
					<summary>Zaznamenat test obnovy</summary>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:6px">
						<input type="hidden" name="action" value="garry_security_record_restore_test">
						<?php wp_nonce_field( 'garry_security_record_restore_test' ); ?>
						<p><label>Poznámka (nepovinné)<br><input type="text" name="note" class="regular-text" placeholder="např. obnoveno na staging, ověřeno ručně"></label></p>
						<?php submit_button( 'Uložit záznam', 'secondary small', 'submit', false ); ?>
					</form>
					<p class="description">GARRY sám žádnou obnovu neprovádí ani neověřuje – jen eviduje, že jste ji provedli a kdy.</p>
				</details>
				<?php
			}
		}

		/**
		 * Barva/štítek pilulky "Doporučení" v kapacitní matici pro daný stav
		 * řádku (verified_ok/verified_attention/unverified/conflict/exempted/missing).
		 */
		private static function capability_pill_class( $state ) {
			switch ( $state ) {
				case 'verified_ok':        return 'pass';
				case 'verified_attention': return 'warning';
				case 'unverified':         return 'delegated';
				case 'conflict':           return 'conflict';
				case 'exempted':           return 'not_applicable';
				default:                   return 'warning';
			}
		}

		/**
		 * Pokrytí bezpečnostních schopností – jeden řádek na schopnost, ne na
		 * plugin, rozdělené do tří bloků (Ochrana a přístup / Obnovitelnost /
		 * Provoz a dohled) podle návrhu struktury. Sloupce Konfigurace a
		 * Ověřeno jsou záměrně oddělené: "nainstalovaný plugin" a "skutečně
		 * ověřená účinnost" jsou dvě různá tvrzení, ne jedno.
		 */
		private static function render_capability_matrix() {
			$matrix = garry_security_capability_matrix();
			$groups = array();
			foreach ( $matrix as $cap => $row ) {
				$groups[ $row['group'] ][ $cap ] = $row;
			}
			$order = array( 'Ochrana a přístup', 'Obnovitelnost', 'Provoz a dohled' );
			?>
			<h2 class="title">Pokrytí bezpečnostních schopností</h2>
			<p class="description">Jeden řádek na schopnost, ne na plugin. „V pořádku" znamená, že GARRY má skutečný, čerstvý důkaz účinnosti – ne jen že je plugin aktivní. Bez tohohle důkazu je stav „Zajištěno — čeká na ověření", nikdy zelené „Pokryto" bez podložení. GARRY doporučí instalaci jen tam, kde schopnost skutečně chybí; kterou web nepotřebuje, lze označit jako „Nevyžadováno".</p>
			<?php foreach ( $order as $group_name ) :
				if ( empty( $groups[ $group_name ] ) ) continue;
			?>
				<h3><?php echo esc_html( $group_name ); ?></h3>
				<table class="widefat striped garry-security-capability-matrix">
					<thead>
						<tr>
							<th>Schopnost</th>
							<th>Poskytovatel</th>
							<th>Konfigurace</th>
							<th>Ověřeno</th>
							<th>Stav</th>
							<th>Akce</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $groups[ $group_name ] as $cap => $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row['label'] ); ?></strong></td>
								<td><?php echo empty( $row['providers'] ) ? '—' : esc_html( implode( ', ', $row['providers'] ) ); ?></td>
								<td><?php echo esc_html( $row['configuration_text'] ); ?></td>
								<td><?php echo esc_html( $row['verified_text'] ); ?></td>
								<td>
									<span class="garry-security-pill is-<?php echo esc_attr( self::capability_pill_class( $row['state'] ) ); ?>">
										<?php echo esc_html( $row['recommendation'] ); ?>
									</span>
									<?php if ( 'exempted' === $row['state'] ) : ?>
										<p class="description"><?php echo esc_html( $row['status_text'] ); ?></p>
									<?php endif; ?>
								</td>
								<td><?php self::render_capability_action( $cap, $row ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>
			<?php
		}

		/**
		 * Sloupec "Akce" v kapacitní matici: odkaz do nastavení poskytovatele
		 * u pokryté schopnosti, jinak formulář "Není potřeba pro tento web" /
		 * "Zrušit výjimku" (viz garry_security_capability_exception_set()).
		 * U konfliktu GARRY záměrně nenabízí žádnou zkratku – konflikt musí
		 * vyřešit administrátor ručně ve stránkách obou poskytovatelů.
		 */
		private static function render_capability_action( $cap, array $row ) {
			if ( in_array( $row['state'], array( 'verified_ok', 'verified_attention', 'unverified' ), true ) ) {
				if ( ! empty( $row['settings_url'] ) ) {
					echo '<a class="button button-small" href="' . esc_url( $row['settings_url'] ) . '">Nastavení</a>';
				} else {
					echo '<span class="description">—</span>';
				}
				return;
			}

			if ( 'conflict' === $row['state'] ) {
				echo '<span class="description">Vyřešte ručně u obou pluginů.</span>';
				return;
			}

			if ( ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) ) {
				echo '<span class="description">—</span>';
				return;
			}

			if ( 'exempted' === $row['state'] ) {
				?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="garry_security_clear_capability_exception">
					<input type="hidden" name="capability" value="<?php echo esc_attr( $cap ); ?>">
					<?php wp_nonce_field( 'garry_security_clear_capability_exception' ); ?>
					<?php submit_button( 'Zrušit výjimku', 'secondary small', 'submit', false ); ?>
				</form>
				<?php
				return;
			}

			$field_id = 'garry-exception-' . sanitize_html_class( $cap );
			?>
			<details>
				<summary>Není potřeba pro tento web</summary>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:6px">
					<input type="hidden" name="action" value="garry_security_set_capability_exception">
					<input type="hidden" name="capability" value="<?php echo esc_attr( $cap ); ?>">
					<?php wp_nonce_field( 'garry_security_set_capability_exception' ); ?>
					<p>
						<label for="<?php echo esc_attr( $field_id ); ?>-reason">Důvod</label><br>
						<input type="text" id="<?php echo esc_attr( $field_id ); ?>-reason" name="reason" class="regular-text" placeholder="např. web nemá vícero editorů">
					</p>
					<p>
						<label for="<?php echo esc_attr( $field_id ); ?>-until">Platí do (nepovinné)</label><br>
						<input type="date" id="<?php echo esc_attr( $field_id ); ?>-until" name="until">
					</p>
					<?php submit_button( 'Uložit výjimku', 'secondary small', 'submit', false ); ?>
				</form>
			</details>
			<?php
		}

		private static function render_recommended_plugins() {
			$registry = garry_security_provider_registry();
			?>
			<h2 class="title" id="garry-recommended-plugins">Volitelná rozšíření</h2>
			<p class="description">Schválený katalog není povinný balík – instalujte jen to, co pro tento web skutečně potřebujete. Přehled toho, co skutečně chybí, je v sekci „Pokrytí bezpečnostních schopností" výše; tahle tabulka je jen mechanismus instalace/aktivace.</p>
			<table class="widefat striped garry-security-providers">
				<thead>
					<tr>
						<th>Plugin</th>
						<th>Stav</th>
						<th>Capabilities</th>
						<th>Akce</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $registry as $slug => $info ) :
					$state = garry_security_provider_ladder_state( $slug );
					$labels = array(
						'recommended' => 'Doporučeno',
						'installed'   => 'Nainstalováno',
						'active'      => 'Aktivní',
					);
					$redundant_owners = ( 'active' !== $state ) ? garry_security_provider_redundant_owners( $slug ) : array();
				?>
					<tr>
						<td><?php echo esc_html( $info['label'] ); ?></td>
						<td><span class="garry-security-pill is-<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $labels[ $state ] ); ?></span></td>
						<td><?php echo esc_html( implode( ', ', $info['capabilities'] ) ); ?></td>
						<td>
							<?php if ( ! empty( $redundant_owners ) && 'installed' === $state ) : ?>
								<span class="description">Nainstalován, ale nepoužíván; překrývá <?php echo esc_html( implode( ', ', $redundant_owners ) ); ?>. Zvažte odstranění, pokud pro něj není důvod.</span>
							<?php elseif ( ! empty( $redundant_owners ) ) : ?>
								<span class="description">Netřeba instalovat – schopnosti už pokrývá <?php echo esc_html( implode( ', ', $redundant_owners ) ); ?>.</span>
							<?php elseif ( 'recommended' === $state && current_user_can( 'install_plugins' ) && current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<input type="hidden" name="action" value="garry_security_install_plugin">
									<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
									<?php wp_nonce_field( 'garry_security_install_plugin' ); ?>
									<?php submit_button( 'Instalovat', 'secondary', 'submit', false ); ?>
								</form>
							<?php elseif ( 'installed' === $state && current_user_can( 'activate_plugins' ) && current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<input type="hidden" name="action" value="garry_security_activate_plugin">
									<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>">
									<?php wp_nonce_field( 'garry_security_activate_plugin' ); ?>
									<?php submit_button( 'Aktivovat', 'secondary', 'submit', false ); ?>
								</form>
							<?php elseif ( 'active' === $state && ! empty( $info['settings_path'] ) ) : ?>
								<a class="button" href="<?php echo esc_url( admin_url( $info['settings_path'] ) ); ?>">Nastavení</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Karta prostředí a serveru – ryze informativní (PHP, MySQL, typ
		 * serveru, podpora LiteSpeed a .htaccess) + bezpečné stažení
		 * .htaccess jako lokální zálohy před ruční úpravou.
		 */
		private static function render_environment_card() {
			$env = garry_security_collect_environment_card();
			$lsc = $env['litespeed_cache'];
			?>
			<h2 class="title">Prostředí a server</h2>
			<div class="garry-security-cards">
				<div class="garry-security-card">
					<h2>Software</h2>
					<p>PHP: <strong><?php echo esc_html( $env['php_version'] ); ?></strong></p>
					<p>MySQL/MariaDB: <strong><?php echo esc_html( $env['mysql_version'] ); ?></strong> (<?php echo esc_html( $env['db_charset'] ); ?>)</p>
					<p>Typ serveru: <strong><?php echo esc_html( $env['server_type'] ); ?></strong></p>
					<p>LiteSpeed Cache:
						<?php if ( $lsc['cache_header_seen'] ) : ?>
							<span class="garry-security-pill is-pass">funkčně potvrzeno</span>
						<?php elseif ( $lsc['plugin_active'] ) : ?>
							<span class="garry-security-pill is-warning">plugin aktivní, cache hlavička neviděna</span>
						<?php elseif ( $lsc['is_litespeed_server'] ) : ?>
							<span class="garry-security-pill is-unknown">server je LiteSpeed, cache nepotvrzena</span>
						<?php else : ?>
							<span class="garry-security-pill is-not_applicable">nedetekováno</span>
						<?php endif; ?>
					</p>
					<p class="description">Kombinuje tři nezávislé signály: aktivní plugin LiteSpeed Cache, hlavičku <code>x-litespeed-cache</code> ve skutečné odpovědi webu, a jestli je server vůbec LiteSpeed. Jen typ serveru bez cache hlavičky ještě neznamená, že cache reálně funguje.</p>
				</div>
				<div class="garry-security-card" id="garry-htaccess-card">
					<h2>.htaccess</h2>
					<p>Podpora serveru: <strong><?php echo esc_html( $env['htaccess_supported'] ? 'Ano – ' . $env['server_type'] : 'Pravděpodobně ne – ' . $env['server_type'] ); ?></strong></p>
					<p class="description">Podle detekovaného typu serveru – Nginx a IIS soubor .htaccess nativně nečtou vůbec. Funkční potvrzení, že server .htaccess opravdu používá, dává nález ENV-007 (rozpozná platný WordPress rewrite blok v jeho obsahu).</p>
					<p>Soubor na webu: <strong><?php echo $env['htaccess_exists'] ? 'Nalezen' : 'Nenalezen'; ?></strong></p>
					<?php if ( $env['htaccess_exists'] ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="garry_security_download_htaccess">
							<?php wp_nonce_field( 'garry_security_download_htaccess' ); ?>
							<?php submit_button( 'Stáhnout bezpečnostní kopii .htaccess', 'secondary', 'submit', false ); ?>
						</form>
						<p class="description">Jen čtení a stažení – GARRY do souboru nikdy nezapisuje. Stáhněte si zálohu vždy před ruční úpravou.</p>
					<?php endif; ?>
				</div>

				<?php self::render_php_limits_card(); ?>
				<?php self::render_dns_card(); ?>
			</div>
			<?php
		}

		/**
		 * "Přístup k obnově" (P2) – kontakt, umístění záloh a odkaz na
		 * postup pro incident. Čistě informační, ruční pole – GARRY ho
		 * nikde neodvozuje ani nekontroluje. Zobrazeno jen v Technických
		 * detailech, dostupné jen s GARRY_SECURITY_CAP_MANAGE_PROVIDERS
		 * (nikdy Editor role – viz docblock u Garry_Security_Backup_Gate).
		 */
		private static function render_recovery_access_card() {
			$data = Garry_Security_Backup_Gate::get_recovery_access();
			?>
			<h2 class="title">Přístup k obnově</h2>
			<p class="description">Kontaktní osoba, umístění záloh a postup pro incident – jen pro administrátory, nikdy veřejně ani pro editory.</p>
			<?php if ( current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="garry-security-recovery-access">
					<input type="hidden" name="action" value="garry_security_save_recovery_access">
					<?php wp_nonce_field( 'garry_security_save_recovery_access' ); ?>
					<p><label>Kontaktní osoba pro incident<br><input type="text" name="contact" class="regular-text" value="<?php echo esc_attr( $data['contact'] ); ?>" placeholder="jméno, telefon nebo e-mail"></label></p>
					<p><label>Umístění záloh<br><input type="text" name="backup_location" class="regular-text" value="<?php echo esc_attr( $data['backup_location'] ); ?>" placeholder="např. UpdraftPlus → Google Drive, složka ..."></label></p>
					<p><label>Odkaz na postup pro incident (nepovinné)<br><input type="url" name="runbook_url" class="regular-text" value="<?php echo esc_attr( $data['runbook_url'] ); ?>" placeholder="https://…"></label></p>
					<?php submit_button( 'Uložit', 'secondary', 'submit', false ); ?>
					<?php if ( $data['verified_at'] ) : ?>
						<p class="description">Naposledy uloženo: <?php echo esc_html( gmdate( 'Y-m-d', $data['verified_at'] ) ); ?> UTC.</p>
					<?php endif; ?>
				</form>
			<?php else : ?>
				<p class="description">Zobrazení a úprava vyžaduje oprávnění spravovat GARRY providery.</p>
			<?php endif; ?>
			<?php
		}

		/**
		 * DNS záznamy domény webu + Cloudflare proxy signál. Read-only DNS
		 * dotazy (dns_get_record) a stejný self-request jako jinde v tomhle
		 * souboru – žádný zápis, žádné externí API.
		 */
		private static function render_dns_card() {
			$dns = garry_security_collect_dns_records();
			?>
			<div class="garry-security-card">
				<h2>DNS záznamy</h2>
				<?php if ( empty( $dns['supported'] ) ) : ?>
					<p class="description">DNS dotazy nejsou na tomto serveru dostupné.</p>
				<?php else :
					$cf = garry_security_cloudflare_status();
					?>
					<p>Doména: <strong><?php echo esc_html( $dns['domain'] ); ?></strong></p>
					<p>Cloudflare:
						<?php if ( $cf['proxied'] ) : ?>
							<span class="garry-security-pill is-warning">detekováno</span>
						<?php else : ?>
							<span class="garry-security-pill is-not_applicable">nedetekováno</span>
						<?php endif; ?>
					</p>
					<p class="description">
						<?php if ( $cf['proxied'] ) : ?>
							Web je pravděpodobně za Cloudflare (běžné proxy, nebo Cloudflare Tunnel – to zvenčí spolehlivě nerozlišíme).
						<?php else : ?>
							Nameservery ani hlavička <code>cf-ray</code> nenaznačují Cloudflare.
						<?php endif; ?>
					</p>
					<table class="widefat striped" style="max-width:520px">
						<tbody>
							<?php foreach ( array( 'A', 'AAAA', 'MX', 'NS', 'TXT', 'CNAME' ) as $type ) :
								$records = isset( $dns['records'][ $type ] ) ? $dns['records'][ $type ] : array();
								if ( empty( $records ) ) continue;
							?>
								<tr>
									<th style="width:70px;vertical-align:top"><?php echo esc_html( $type ); ?></th>
									<td>
										<?php foreach ( $records as $rec ) :
											$value = $rec['ip'] ?? $rec['ipv6'] ?? $rec['target'] ?? $rec['txt'] ?? $rec['host'] ?? '';
										?>
											<code style="display:block"><?php echo esc_html( $value ); ?></code>
										<?php endforeach; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * PHP limity – ryze informativní čtení přes ini_get(). Úprava
		 * vyžaduje php.ini/.htaccess/rozhraní hostingu, GARRY do nich sám
		 * nezasahuje – jen ukazuje aktuální hodnoty a doporučené minimum.
		 */
		/** Buňka PHP limitu – zvýrazněná, jen pokud je hodnota pod doporučeným minimem (viz garry_security_php_limits_below_threshold()). */
		private static function php_limit_cell( $key, $value, array $below ) {
			if ( isset( $below[ $key ] ) ) {
				return '<span class="garry-security-pill is-warning" title="' . esc_attr( $below[ $key ] ) . '">' . esc_html( $value ) . '</span>';
			}
			return esc_html( $value );
		}

		private static function render_php_limits_card() {
			$limits = garry_security_collect_php_limits();
			$below  = garry_security_php_limits_below_threshold( $limits );
			?>
			<div class="garry-security-card">
				<h2>PHP limity</h2>
				<table class="widefat striped" style="max-width:480px">
					<tbody>
						<tr><th style="width:220px">memory_limit</th><td><?php echo self::php_limit_cell( 'memory_limit', $limits['memory_limit'], $below ); ?></td></tr>
						<?php if ( $limits['wp_memory_limit'] ) : ?>
							<tr><th>WP_MEMORY_LIMIT</th><td><?php echo esc_html( $limits['wp_memory_limit'] ); ?></td></tr>
						<?php endif; ?>
						<?php if ( $limits['wp_max_memory_limit'] ) : ?>
							<tr><th>WP_MAX_MEMORY_LIMIT</th><td><?php echo esc_html( $limits['wp_max_memory_limit'] ); ?></td></tr>
						<?php endif; ?>
						<tr><th>upload_max_filesize</th><td><?php echo self::php_limit_cell( 'upload_max_filesize', $limits['upload_max_filesize'], $below ); ?></td></tr>
						<tr><th>post_max_size</th><td><?php echo esc_html( $limits['post_max_size'] ); ?></td></tr>
						<tr><th>max_execution_time</th><td><?php echo self::php_limit_cell( 'max_execution_time', $limits['max_execution_time'] . 's', $below ); ?></td></tr>
						<tr><th>max_input_time</th><td><?php echo esc_html( $limits['max_input_time'] ); ?>s</td></tr>
						<tr><th>max_input_vars</th><td><?php echo self::php_limit_cell( 'max_input_vars', $limits['max_input_vars'], $below ); ?></td></tr>
						<tr><th>OPcache</th><td><?php echo $limits['opcache_enabled'] ? 'Zapnuté' : 'Vypnuté/nedostupné'; ?></td></tr>
					</tbody>
				</table>
				<p class="description">Úprava vyžaduje php.ini, .htaccess nebo rozhraní hostingu – GARRY tyto hodnoty jen čte, nikdy sám nemění. Zvýrazněné hodnoty jsou pod doporučeným minimem (viz nález ENV-006 v tabulce Kontroly), ostatní jsou jen informativní.</p>
			</div>
			<?php
		}

		/**
		 * robots.txt (přes nativní WP filtr robots_txt – žádný zápis do
		 * souboru) a llms.txt (vlastní virtuální výstup, viz
		 * security-robots-llms.php pro vysvětlení, proč nejde o standard).
		 */
		private static function render_robots_llms() {
			$robots_settings = garry_security_robots_settings();
			$llms_settings   = garry_security_llms_settings();
			$robots_physical = garry_security_robots_physical_file_exists();
			$llms_physical   = garry_security_llms_physical_file_exists();
			?>
			<h2 class="title">Vystavení webu a crawleři: robots.txt a llms.txt</h2>
			<div class="notice notice-info inline garry-security-crawler-disclaimer">
				<p><code>robots.txt</code> je jen pokyn pro slušně se chovající crawlery – <strong>není to ochrana citlivé adresy</strong>. Zaškrtnutí „Zakázat procházení REST API" v robots.txt API samo o sobě nezablokuje, jen o to požádá vyhledávače; skutečnou ochranu musí řešit přístupová politika (viz WEB-004 v Kontrolách). <code>llms.txt</code> je dobrovolný, nemá standardní vynutitelnost a jeho stav se do bezpečnostního skóre GARRY nepočítá.</p>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="garry_security_save_robots_llms">
				<?php wp_nonce_field( 'garry_security_save_robots_llms' ); ?>

				<div class="garry-security-cards">
					<div class="garry-security-card">
						<h2>robots.txt</h2>
						<p class="description">Říká vyhledávačům a dalším robotům, které části webu smí procházet. WordPress ho generuje automaticky za běhu (přes vestavěný filtr <code>robots_txt</code>) – GARRY do žádného souboru nezapisuje, jen do tohoto výstupu doplňuje volitelné řádky.</p>
						<?php if ( $robots_physical ) : ?>
							<div class="notice notice-warning inline"><p>Na webu existuje vlastní fyzický soubor <code>robots.txt</code> – ten má vždy přednost a nastavení níže se na něj nepoužijí, dokud ho neodstraníte.</p></div>
						<?php endif; ?>
						<p><label><input type="checkbox" name="robots_block_ai_crawlers" value="1" <?php checked( ! empty( $robots_settings['block_ai_crawlers'] ) ); ?>> Blokovat známé AI/LLM trénovací crawlery (GPTBot, CCBot, ClaudeBot a další)</label></p>
						<p><label><input type="checkbox" name="robots_disallow_search" value="1" <?php checked( ! empty( $robots_settings['disallow_search'] ) ); ?>> Zakázat procházení výsledků interního vyhledávání (<code>/?s=</code>)</label></p>
						<p><label><input type="checkbox" name="robots_disallow_rest_api" value="1" <?php checked( ! empty( $robots_settings['disallow_rest_api'] ) ); ?>> Zakázat procházení REST API (<code>/wp-json/</code>)</label></p>
						<p><label><input type="checkbox" name="robots_add_sitemap_reference" value="1" <?php checked( ! empty( $robots_settings['add_sitemap_reference'] ) ); ?>> Přidat odkaz na mapu webu (<code>/wp-sitemap.xml</code>)</label></p>
						<p><strong>Aktuální výstup:</strong></p>
						<pre class="garry-security-preview"><?php echo esc_html( garry_security_robots_preview() ); ?></pre>
					</div>

					<div class="garry-security-card">
						<h2>llms.txt</h2>
						<p class="description">Nový, zatím neformální způsob, jak webu sdělit svůj postoj k AI/LLM systémům – obdoba robots.txt, ale bez ustáleného standardu ani jádrové podpory WordPressu. Dodržování je čistě na dobrovolnosti dané AI služby, stejně jako u robots.txt.</p>
						<?php if ( $llms_physical ) : ?>
							<div class="notice notice-warning inline"><p>Na webu existuje vlastní fyzický soubor <code>llms.txt</code> – ten má přednost, nastavení níže se použijí až po jeho odstranění.</p></div>
						<?php endif; ?>
						<p><label><input type="checkbox" name="llms_include_site_info" value="1" <?php checked( ! empty( $llms_settings['include_site_info'] ) ); ?>> Uvést název a popis webu</label></p>
						<p><label><input type="checkbox" name="llms_include_content_summary" value="1" <?php checked( ! empty( $llms_settings['include_content_summary'] ) ); ?>> Uvést stručný souhrn obsahu a odkaz na mapu webu</label></p>
						<p><label><input type="checkbox" name="llms_allow_ai_training" value="1" <?php checked( ! empty( $llms_settings['allow_ai_training'] ) ); ?>> Povolit použití obsahu pro trénink AI modelů</label></p>
						<p><label><input type="checkbox" name="llms_include_contact" value="1" <?php checked( ! empty( $llms_settings['include_contact'] ) ); ?>> Uvést kontaktní e-mail</label></p>
						<p><strong>Aktuální výstup na <?php echo esc_html( home_url( '/llms.txt' ) ); ?>:</strong></p>
						<pre class="garry-security-preview"><?php echo esc_html( garry_security_llms_build_content() ); ?></pre>
					</div>
				</div>

				<?php submit_button( 'Uložit nastavení robots.txt a llms.txt', 'primary', 'submit', false, current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
			</form>
			<?php
		}

		/**
		 * Scoped na vlastní stránku (GARRY-004: „globální admin assety mimo
		 * vlastní screen"). `admin_head` nedostává hook_suffix jako parametr,
		 * proto se ověřuje přes get_current_screen() – ID obrazovky vždy
		 * obsahuje local_menu_slug bez ohledu na to, kdo vyhrál volbu
		 * root_menu (viz Framework 2.3, docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md).
		 */
		public static function print_styles() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! $screen || false === strpos( $screen->id, self::PAGE_SLUG ) ) {
				return;
			}
			?>
			<style id="garry-security-admin-css">
				.garry-security-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin:16px 0}
				.garry-security-card{background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:16px 18px}
				.garry-security-card h2{margin-top:0;font-size:15px}
				.garry-security-counts{list-style:none;margin:8px 0;padding:0;display:flex;gap:14px;flex-wrap:wrap}
				.garry-security-counts li{font-size:13px}
				.garry-security-counts .is-critical strong{color:#d63638}
				.garry-security-counts .is-warning strong{color:#b45309}
				.garry-security-counts .is-unknown strong{color:#646970}
				.garry-security-counts .is-conflict strong{color:#8b2fc9}
				.garry-security-scan-form{margin:0}
				.garry-security-actions{display:flex;gap:10px;flex-wrap:wrap;margin:16px 0}
				.garry-security-providers{margin-bottom:24px}
				.garry-security-ecosystem{margin-bottom:24px}
				.garry-security-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;text-transform:uppercase}
				.garry-security-pill.is-pass{background:#edfaef;color:#1a7f37}
				.garry-security-pill.is-warning{background:#fef6e7;color:#b45309}
				.garry-security-pill.is-critical{background:#fdeeee;color:#d63638}
				.garry-security-pill.is-unknown{background:#f0f0f1;color:#646970}
				.garry-security-pill.is-conflict{background:#f3e8fd;color:#8b2fc9}
				.garry-security-pill.is-delegated{background:#e7f1fc;color:#0a4b78}
				.garry-security-pill.is-not_applicable{background:#f0f0f1;color:#8c8f94}
				.garry-security-pill.is-active{background:#edfaef;color:#1a7f37}
				.garry-security-pill.is-installed{background:#fef6e7;color:#b45309}
				.garry-security-pill.is-recommended{background:#f0f0f1;color:#646970}
				.garry-security-fix-card{margin-bottom:12px;max-width:900px}
				.garry-security-fix-card summary{cursor:pointer;font-weight:600;margin-top:8px}
				.garry-security-preview{background:#1d2327;color:#c3c4c7;padding:12px 14px;border-radius:4px;max-height:220px;overflow:auto;font-size:12px;line-height:1.6;white-space:pre-wrap;word-break:break-word}
				.garry-security-cards-5{grid-template-columns:repeat(auto-fit,minmax(200px,1fr))}
				.garry-security-action-now{background:#fff;border:1px solid #c3c4c7;border-left:4px solid #d63638;border-radius:6px;padding:14px 18px;margin:16px 0}
				.garry-security-action-now h2.title{margin-top:0}
				.garry-security-action-now-list{list-style:none;margin:0;padding:0}
				.garry-security-action-now-item{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:10px 0;border-bottom:1px dashed #e5e7eb;flex-wrap:wrap}
				.garry-security-action-now-item:last-child{border-bottom:none}
				.garry-security-action-now-item.is-critical{border-left:3px solid #d63638;padding-left:10px}
				.garry-security-action-now-item.is-conflict{border-left:3px solid #8b2fc9;padding-left:10px}
				.garry-security-action-now-item.is-warning{border-left:3px solid #b45309;padding-left:10px}
				.garry-security-action-now-cta{flex-shrink:0}
				.garry-security-events-list{list-style:none;margin:0;padding:0;font-size:12px}
				.garry-security-events-list li{padding:4px 0;border-bottom:1px dashed #e5e7eb}
				.garry-security-events-list li:last-child{border-bottom:none}
				.garry-security-capability-matrix{margin-bottom:20px}
				.garry-security-findings-filter{display:flex;gap:8px;align-items:center;margin:10px 0}
				.garry-security-control-group{background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:10px 14px;margin-bottom:10px}
				.garry-security-control-group summary{cursor:pointer;font-weight:600}
				.garry-security-control-group table{margin-top:10px}
				.garry-security-ecosystem-summary{max-width:520px;margin-bottom:20px}
				.garry-security-ecosystem-modules{margin-bottom:8px}
				.garry-security-crawler-disclaimer{margin:12px 0}
			</style>
			<?php
		}
	}
}
