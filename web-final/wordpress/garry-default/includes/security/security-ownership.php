<?php
/**
 * GARRY Security – ownership/conflict engine (Fáze 2).
 *
 * Podle GARRY-WP-SECURITY-MASTER-SPEC.md sekce 6 „Conflict engine": jedna
 * capability smí mít právě jednoho aktivního vlastníka. GARRY tu nikdy nic
 * cizího automaticky nevypíná – jen detekuje a hlásí (viz
 * GARRY-WP-SECURITY-BEHAVIOR-SPEC.md sekce 10.4).
 *
 * Wordfence záměrně NEnabízí capabilitu `captcha_login` v registru (viz
 * security-providers.php) – jeho reCAPTCHA je ve výchozím GARRY profilu
 * vypnutá a bez ověřeného čtení jeho interních options bychom tu jinak
 * hlásili false-positive konflikt s Turnstile jen proto, že je Wordfence
 * aktivní. To přesně odpovídá zásadě „unknown není bezpečné, ale ani
 * neověřené critical není v pořádku" – radši nedeklarovat konflikt, který
 * nejde spolehlivě dokázat.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function garry_security_collect_ownership() {
	$registry       = garry_security_provider_registry();
	$exclusive_caps = garry_security_exclusive_capabilities();
	$owners_by_cap  = array();

	foreach ( $registry as $slug => $info ) {
		if ( ! garry_security_provider_is_active( $slug ) ) {
			continue;
		}
		foreach ( $info['capabilities'] as $cap ) {
			$owners_by_cap[ $cap ][] = $slug;
		}
	}

	$results = array();
	foreach ( $exclusive_caps as $cap ) {
		$owners     = isset( $owners_by_cap[ $cap ] ) ? $owners_by_cap[ $cap ] : array();
		$control_id = 'OWN-' . strtoupper( str_replace( '_', '-', $cap ) );

		if ( count( $owners ) > 1 ) {
			$results[ $control_id ] = array(
				'state'    => 'conflict',
				'severity' => 'high',
				'message'  => sprintf(
					'Capability "%1$s" mají současně aktivní: %2$s. Vyberte jednoho vlastníka a u ostatních odpovídající modul ručně vypněte.',
					$cap,
					implode( ', ', array_map( function ( $s ) use ( $registry ) { return $registry[ $s ]['label']; }, $owners ) )
				),
				'evidence' => array( 'capability' => $cap, 'owners' => $owners ),
			);
		} elseif ( 1 === count( $owners ) ) {
			$results[ $control_id ] = array(
				'state'    => 'delegated',
				'severity' => 'info',
				'message'  => sprintf( 'Capability "%1$s" vlastní %2$s.', $cap, $registry[ $owners[0] ]['label'] ),
				'evidence' => array( 'capability' => $cap, 'owner' => $owners[0] ),
			);
		} else {
			$results[ $control_id ] = array(
				'state'    => 'not_applicable',
				'severity' => 'info',
				'message'  => sprintf( 'Capability "%1$s" nemá aktivního GARRY-katalogového vlastníka.', $cap ),
				'evidence' => array( 'capability' => $cap ),
			);
		}
	}

	return $results;
}

/**
 * Lidsky čitelné popisky bezpečnostních "schopností" pro kartu Pokrytí
 * bezpečnostních schopností (nahrazuje prostý seznam "doporučené pluginy"
 * – zpětná vazba: "GARRY má doporučit nový plugin jen tehdy, když daná
 * schopnost opravdu chybí", ne podle toho, jestli existuje).
 */
function garry_security_capability_labels() {
	return array(
		'firewall'                   => 'Firewall',
		'malware_scan'                => 'Sken malwaru',
		'login_rate_limit'            => 'Rate-limit přihlášení',
		'two_factor'                  => '2FA',
		'captcha_login'                => 'CAPTCHA loginu',
		'backup'                       => 'Zálohy',
		'smtp'                          => 'SMTP',
		'activity_log'                 => 'Audit změn',
		'vulnerability_intelligence'   => 'Vulnerability scanning',
	);
}

/**
 * Do jakého bloku "GARRY ekosystém → Přehled" karta Pokrytí bezpečnostních
 * schopností kapacitu zařadí (návrh struktury, sekce 3 "Pokrytí bezpečnostních
 * schopností" – "Schopnosti rozdělit do bloků").
 */
function garry_security_capability_groups() {
	return array(
		'firewall'                  => 'Ochrana a přístup',
		'malware_scan'              => 'Ochrana a přístup',
		'login_rate_limit'          => 'Ochrana a přístup',
		'two_factor'                => 'Ochrana a přístup',
		'captcha_login'             => 'Ochrana a přístup',
		'backup'                    => 'Obnovitelnost',
		'smtp'                      => 'Provoz a dohled',
		'activity_log'              => 'Provoz a dohled',
		'vulnerability_intelligence' => 'Provoz a dohled',
	);
}

/**
 * Výjimky "Není potřeba pro tento web" u jednotlivých schopností – odlehčená
 * náhrada plného "Nastavení politiky webu" z návrhu (profily/prahy/schvalovatelé
 * pro každou jednotlivou kontrolu). GARRY 2.1 řeší jen úroveň schopností, ne
 * každé jednotlivé kontroly – plný policy engine zůstává pro budoucí verzi
 * (viz README, "Záměrně nedokončeno"). Výjimka nikdy nepotlačí konflikt nebo
 * kritický nález, jen "chybí poskytovatel" stav u schopnosti, kterou web
 * podle administrátora nepotřebuje.
 */
function garry_security_capability_exceptions() {
	$stored = get_option( 'garry_security_capability_exceptions', array() );
	return is_array( $stored ) ? $stored : array();
}

function garry_security_capability_exception_set( $capability, $reason, $until = '' ) {
	$exceptions              = garry_security_capability_exceptions();
	$exceptions[ $capability ] = array(
		'reason'   => sanitize_text_field( $reason ),
		'by'       => get_current_user_id(),
		'by_name'  => wp_get_current_user()->display_name,
		'set_at'   => time(),
		'until'    => $until ? sanitize_text_field( $until ) : '',
	);
	update_option( 'garry_security_capability_exceptions', $exceptions, false );
}

function garry_security_capability_exception_clear( $capability ) {
	$exceptions = garry_security_capability_exceptions();
	unset( $exceptions[ $capability ] );
	update_option( 'garry_security_capability_exceptions', $exceptions, false );
}

/**
 * Aktivní (dosud neexpirovaná) výjimka pro danou schopnost, nebo null.
 * Expirovaná výjimka se chová, jako by neexistovala – "Není potřeba" se
 * po datu expirace samo vrátí na "Zvážit doplnění", aby staré rozhodnutí
 * nezůstalo tiše platit navždy.
 */
function garry_security_capability_active_exception( $capability ) {
	$exceptions = garry_security_capability_exceptions();
	if ( ! isset( $exceptions[ $capability ] ) ) {
		return null;
	}
	$exception = $exceptions[ $capability ];
	if ( ! empty( $exception['until'] ) && strtotime( $exception['until'] ) < time() ) {
		return null;
	}
	return $exception;
}

/**
 * Pokrytí bezpečnostních schopností napříč celým katalogem providerů –
 * jeden řádek na schopnost, ne na plugin. Kde má GARRY skutečně ověřený
 * detail (Turnstile konfigurace, UpdraftPlus historie záloh), zobrazí ho;
 * jinak jen poctivé "Aktivní" (ověřeno jen to, že je plugin aktivní, ne
 * jeho vnitřní konfigurace – stejná zásada jako jinde v tomhle pluginu).
 */
function garry_security_capability_matrix() {
	$registry = garry_security_provider_registry();
	$labels   = garry_security_capability_labels();

	// Union všech capabilities deklarovaných v katalogu, ne jen "exclusive" set.
	$all_caps = array();
	foreach ( $registry as $info ) {
		foreach ( $info['capabilities'] as $cap ) {
			if ( ! in_array( $cap, $all_caps, true ) ) {
				$all_caps[] = $cap;
			}
		}
	}

	$owners_by_cap = array();
	foreach ( $registry as $slug => $info ) {
		if ( ! garry_security_provider_is_active( $slug ) ) {
			continue;
		}
		foreach ( $info['capabilities'] as $cap ) {
			$owners_by_cap[ $cap ][] = $slug;
		}
	}

	$groups = garry_security_capability_groups();

	$rows = array();
	foreach ( $all_caps as $cap ) {
		$owners = isset( $owners_by_cap[ $cap ] ) ? $owners_by_cap[ $cap ] : array();
		$label  = isset( $labels[ $cap ] ) ? $labels[ $cap ] : $cap;
		$group  = isset( $groups[ $cap ] ) ? $groups[ $cap ] : 'Provoz a dohled';

		if ( count( $owners ) > 1 ) {
			$rows[ $cap ] = array(
				'label'              => $label,
				'group'              => $group,
				'state'              => 'conflict',
				'providers'          => array_map( function ( $s ) use ( $registry ) { return $registry[ $s ]['label']; }, $owners ),
				'configuration_text' => 'Aktivní současně u více pluginů',
				'verified_text'      => '—',
				'status_text'        => 'Aktivní současně u více pluginů',
				'recommendation'     => 'Vyřešit konflikt',
				'settings_url'       => '',
			);
			continue;
		}

		if ( 1 === count( $owners ) ) {
			$rows[ $cap ] = garry_security_capability_row_for_owner( $cap, $label, $group, $owners[0], $registry );
			continue;
		}

		$exception = garry_security_capability_active_exception( $cap );
		if ( $exception ) {
			$rows[ $cap ] = array(
				'label'              => $label,
				'group'              => $group,
				'state'              => 'exempted',
				'providers'          => array(),
				'configuration_text' => '—',
				'verified_text'      => '—',
				'status_text'        => sprintf(
					'Nevyžadováno – schválil %1$s%2$s. Důvod: %3$s',
					! empty( $exception['by_name'] ) ? $exception['by_name'] : 'administrátor',
					! empty( $exception['until'] ) ? ', platí do ' . $exception['until'] : ' (bez expirace)',
					! empty( $exception['reason'] ) ? $exception['reason'] : '—'
				),
				'recommendation'     => 'Nevyžadováno',
				'settings_url'       => '',
			);
			continue;
		}

		$rows[ $cap ] = array(
			'label'              => $label,
			'group'              => $group,
			'state'              => 'missing',
			'providers'          => array(),
			'configuration_text' => '—',
			'verified_text'      => '—',
			'status_text'        => 'Žádný aktivní poskytovatel z katalogu',
			'recommendation'     => 'Zvážit doplnění',
			'settings_url'       => '',
		);
	}

	return $rows;
}

/**
 * Sestaví jeden řádek kapacitní matice pro schopnost s právě jedním
 * aktivním vlastníkem. Rozlišuje tři stavy (verze 2.2, oprava zpětné
 * vazby "neoznačovat POKRYTO, pokud je Ověřeno Neověřeno"):
 *
 * - `verified_ok`       – GARRY má skutečný, čerstvý důkaz účinnosti,
 * - `verified_attention` – GARRY má důkaz, ale ten ukazuje na problém
 *                          (částečné pokrytí, chybějící test obnovy…),
 * - `unverified`         – jen aktivní plugin, žádný ověřený důkaz.
 *
 * Jen `verified_ok` se v tabulce zobrazí jako "V pořádku"; `unverified`
 * dostane výslovně "Zajištěno — čeká na ověření", ne zelené "Pokryto".
 */
function garry_security_capability_row_for_owner( $cap, $label, $group, $slug, array $registry ) {
	$state               = 'unverified';
	$configuration_text  = 'Aktivní';
	$verified_text       = 'Neověřeno (jen aktivní plugin, ne jeho vnitřní konfigurace)';

	if ( 'captcha_login' === $cap && 'simple-cloudflare-turnstile' === $slug && function_exists( 'garry_security_check_turnstile_configuration' ) ) {
		$login_check = garry_security_check_turnstile_configuration();
		$reset_check = function_exists( 'garry_security_check_turnstile_on_reset' ) ? garry_security_check_turnstile_on_reset() : array( 'state' => 'unknown' );

		if ( 'pass' === $login_check['state'] && 'pass' === $reset_check['state'] ) {
			$state              = 'verified_ok';
			$configuration_text = 'Login + reset hesla';
			$verified_text      = 'Ověřeno na obou stránkách (site key nastaven, widget se vykresluje)';
		} elseif ( 'pass' === $login_check['state'] ) {
			$state              = 'verified_attention';
			$configuration_text = 'Login ano, reset hesla neověřen/chybí';
			$verified_text      = 'Login ověřen, na resetu hesla widget nenalezen – viz PROV-TURNSTILE-RESET';
		} elseif ( 'warning' === $login_check['state'] ) {
			$state              = 'verified_attention';
			$configuration_text = 'Konfigurace není úplná';
			$verified_text      = 'Viz PROV-TURNSTILE-CONFIG';
		}
	} elseif ( 'backup' === $cap && 'updraftplus' === $slug && class_exists( 'Garry_Security_Backup_Gate' ) ) {
		$hint         = Garry_Security_Backup_Gate::get_own_history_hint();
		$config       = Garry_Security_Backup_Gate::get_backup_configuration_hint();
		$restore_test = Garry_Security_Backup_Gate::get_restore_test();

		$config_bits = array();
		if ( $config ) {
			$config_bits[] = ! empty( $config['remote_services'] ) ? 'vzdálené úložiště: ' . implode( ', ', $config['remote_services'] ) : 'jen lokální úložiště';
			if ( null !== $config['retain_files'] ) {
				$config_bits[] = 'retence ' . $config['retain_files'] . 'x';
			}
		}
		$configuration_text = $config_bits ? implode( ' · ', $config_bits ) : 'Nastaveno v UpdraftPlus';

		if ( $hint ) {
			$age_text = sprintf( 'poslední záloha %s', gmdate( 'Y-m-d', $hint['last_backup_set_time'] ) );
			if ( $restore_test ) {
				$test_age_days = (int) floor( ( time() - $restore_test['recorded_at'] ) / DAY_IN_SECONDS );
				if ( $test_age_days <= 180 ) {
					$state         = 'verified_ok';
					$verified_text = sprintf( '%1$s · test obnovy před %2$d dny', $age_text, $test_age_days );
				} else {
					$state         = 'verified_attention';
					$verified_text = sprintf( '%1$s · test obnovy před %2$d dny (starší 180 dní)', $age_text, $test_age_days );
				}
			} else {
				$state         = 'verified_attention';
				$verified_text = $age_text . ' · test obnovy nezaznamenán';
			}
		} else {
			$verified_text = 'Historie záloh zatím neověřena';
		}
	} elseif ( 'two_factor' === $cap ) {
		if ( 'two-factor' === $slug && class_exists( 'Two_Factor_Core' ) && method_exists( 'Two_Factor_Core', 'is_user_using_two_factor' ) ) {
			$admins  = get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) );
			$total   = count( $admins );
			$enabled = 0;
			foreach ( $admins as $admin_id ) {
				if ( Two_Factor_Core::is_user_using_two_factor( $admin_id ) ) {
					$enabled++;
				}
			}
			$configuration_text = 'Funkce dostupná (Two-Factor)';
			$verified_text       = sprintf( '%1$d/%2$d administrátorů aktivováno · vynucení neověřeno', $enabled, $total );
			if ( $total > 0 && $enabled === $total ) {
				$state = 'verified_ok';
			} else {
				$state = 'verified_attention';
			}
		} else {
			$configuration_text = 'Funkce dostupná';
			$verified_text      = sprintf( 'Zda ji administrátoři skutečně používají, GARRY nemá jak bezpečně ověřit u %s (nedokumentované interní API)', $registry[ $slug ]['label'] );
		}
	} elseif ( 'firewall' === $cap && 'wordfence' === $slug && function_exists( 'garry_security_check_wordfence_waf_extended' ) ) {
		$waf = garry_security_check_wordfence_waf_extended();
		if ( 'pass' === $waf['state'] ) {
			$state              = 'verified_ok';
			$configuration_text = 'Rozšířená ochrana (WAF bootstrap nalezen)';
			$verified_text      = 'Bootstrap soubor rozšířené ochrany nalezen v kořeni webu';
		} else {
			$configuration_text = 'Aktivní, režim neověřen';
			$verified_text      = 'Bootstrap soubor rozšířené ochrany nenalezen – viz PROV-WORDFENCE-WAF';
		}
	}

	return array(
		'label'              => $label,
		'group'              => $group,
		'state'              => $state,
		'providers'          => array( $registry[ $slug ]['label'] ),
		'configuration_text' => $configuration_text,
		'verified_text'      => $verified_text,
		'status_text'        => $configuration_text . ' – ' . $verified_text,
		'recommendation'     => 'verified_ok' === $state ? 'V pořádku' : ( 'verified_attention' === $state ? 'Zkontrolovat' : 'Zajištěno — čeká na ověření' ),
		'settings_url'       => isset( $registry[ $slug ]['settings_path'] ) ? admin_url( $registry[ $slug ]['settings_path'] ) : '',
	);
}

function garry_security_ownership_catalog_entries() {
	$entries = array();
	foreach ( garry_security_exclusive_capabilities() as $cap ) {
		$control_id             = 'OWN-' . strtoupper( str_replace( '_', '-', $cap ) );
		$entries[ $control_id ] = array( 'title' => 'Vlastnictví capability: ' . $cap, 'category' => 'Ownership / konflikty' );
	}
	return $entries;
}
