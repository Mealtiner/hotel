<?php
/**
 * GRID Hotel Components — kontakt, doprava, dotazník (GRID-SUITE-02 §4
 * „formuláře/poukazy"). Kontaktní a dotazníkový formulář jsou v baseline
 * (theme) čistě vizuální ukázky bez skutečného odeslání (onsubmit="return
 * false") — GRID-SUITE-02 §10 zakazuje TVÁŘIT SE, že fungují; ponechány
 * jasně označené jako ukázka, žádná regrese (baseline taky nic neodesílala).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Slepý (nefunkční) formulář — vizuální ukázka, jasně popsaná, nic neodesílá. */
function gridc_render_blind_form( $type ) {
	$note = '<p style="font-family:var(--f-mono);font-size:.66rem;color:var(--muted);margin-top:12px">// Ukázkový formulář — připraven k napojení, zatím neodesílá.</p>';
	ob_start();
	if ( 'kontakt' === $type ) :
		?>
		<form class="form-grid" onsubmit="return false" aria-label="Kontaktní formulář (ukázka)">
		  <div><label for="k-jmeno">Jméno</label><input id="k-jmeno" type="text" placeholder="Jméno"></div>
		  <div><label for="k-prijmeni">Příjmení</label><input id="k-prijmeni" type="text" placeholder="Příjmení"></div>
		  <div><label for="k-email">E-mail</label><input id="k-email" type="email" placeholder="vas@email.cz"></div>
		  <div><label for="k-tel">Telefon</label><input id="k-tel" type="tel" placeholder="+420 …"></div>
		  <div class="full"><label for="k-zprava">Vaše zpráva</label><textarea id="k-zprava" rows="4" placeholder="Dotaz k pobytu, termínu, rezervaci…"></textarea></div>
		  <div class="full"><button type="submit" class="btn" onclick="return false">Odeslat dotaz</button></div>
		</form>
		<?php echo $note;
	elseif ( 'dotaznik' === $type ) :
		$scale = function ( $name ) {
			$o = '<div class="df-scale-row" role="radiogroup">';
			for ( $i = 1; $i <= 5; $i++ ) {
				$o .= '<label class="df-opt"><input type="radio" name="' . esc_attr( $name ) . '" value="' . $i . '"><span>' . $i . '</span></label>';
			}
			return $o . '</div>';
		};
		?>
		<form class="dotaznik-form" onsubmit="return false" aria-label="Dotazník spokojenosti (ukázka)">
		  <p class="df-scale-legend">Hodnocení: <strong>1 = velmi dobře</strong> … <strong>5 = velmi špatně</strong></p>
		  <fieldset class="df-q"><legend>1/ Jak hodnotíte celkový dojem z hotelu?</legend><?php echo $scale( 'q1' ); ?></fieldset>
		  <fieldset class="df-q"><legend>2/ Jak hodnotíte přístup personálu?</legend><?php echo $scale( 'q2' ); ?></fieldset>
		  <fieldset class="df-q"><legend>3/ Jak hodnotíte naše snídaně?</legend><?php echo $scale( 'q3' ); ?></fieldset>
		  <fieldset class="df-q"><legend>4/ Jak hodnotíte společenské prostory hotelu?</legend>
		    <div class="df-sub"><span class="df-sublabel">Komfort</span><?php echo $scale( 'q4a' ); ?></div>
		    <div class="df-sub"><span class="df-sublabel">Čistota</span><?php echo $scale( 'q4b' ); ?></div>
		  </fieldset>
		  <fieldset class="df-q"><legend>5/ Jak hodnotíte naše pokoje?</legend>
		    <div class="df-sub"><span class="df-sublabel">Vybavení</span><?php echo $scale( 'q5a' ); ?></div>
		    <div class="df-sub"><span class="df-sublabel">Čistota</span><?php echo $scale( 'q5b' ); ?></div>
		  </fieldset>
		  <div class="df-q"><label for="d-pozn">6/ Rádi uvítáme Vaše připomínky a poznámky:</label><textarea id="d-pozn" rows="4"></textarea></div>
		  <p class="df-optional">Nepovinné údaje</p>
		  <div class="form-grid">
		    <div><label for="d-jmeno">Vaše jméno a příjmení</label><input id="d-jmeno" type="text"></div>
		    <div><label for="d-email">Váš e-mail</label><input id="d-email" type="email" placeholder="vas@email.cz"></div>
		  </div>
		  <button type="submit" class="btn" style="margin-top:20px" onclick="return false">Odeslat</button>
		  <p class="df-thanks">Děkujeme za Váš čas!</p>
		</form>
		<?php echo $note;
	endif;
	return ob_get_clean();
}

/* [grid_kontakt] — plné kontaktní informace + mapa + formulář. */
function gridc_sc_kontakt() {
	$a1     = gridhotel_get_option( 'adresa_1', 'Ostrovačická 936/65, Masarykův okruh' );
	$a2     = gridhotel_get_option( 'adresa_2', '641 00 Brno – Žebětín, ČR' );
	$telr   = gridhotel_get_option( 'tel_recepce', '+420 775 877 721' );
	$telrez = gridhotel_get_option( 'tel_rezervace', '+420 775 877 720' );
	$tels   = gridhotel_get_option( 'tel_shuttle', '+420 775 778 718' );
	$ico    = gridhotel_get_option( 'ico', '04996364' );
	$dic    = gridhotel_get_option( 'dic', 'CZ04996364' );
	$spis   = gridhotel_get_option( 'spis_znacka', 'Sp. zn. C 92997, KS v Brně' );
	$tel    = function ( $t ) { return preg_replace( '/\s+/', '', $t ); };
	$mapsrc = 'https://www.google.com/maps?q=' . rawurlencode( 'GRID HOTEL, Ostrovačická 936/65, 641 00 Brno-Žebětín' ) . '&output=embed&hl=cs';
	$navig  = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( 'GRID HOTEL, Ostrovačická 936/65, 641 00 Brno-Žebětín' );
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-component grid-component--kontakt" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap">
	    <span class="kicker">Kontakt</span>
	    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 12px">Jsme přímo v areálu Autodromu Brno</h1>
	    <p style="max-width:60ch;color:var(--muted)">Napište nám kvůli rezervaci, firemní akci, svatbě, cateringu, dárkovému poukazu nebo dopravě.</p>
	    <div class="kontakt-grid">
	      <div class="k-info">
	        <div class="k-block"><h3>Adresa</h3><p><?php echo esc_html( $a1 ); ?><br><?php echo esc_html( $a2 ); ?></p><a class="btn btn-ghost" href="<?php echo esc_url( $navig ); ?>" target="_blank" rel="noopener">Navigovat →</a></div>
	        <div class="k-block"><h3>Rezervace</h3><p><a href="tel:<?php echo esc_attr( $tel( $telrez ) ); ?>"><?php echo esc_html( $telrez ); ?></a><br><a href="mailto:reservations@gridhotel.cz">reservations@gridhotel.cz</a></p></div>
	        <div class="k-block"><h3>Recepce</h3><p><a href="tel:<?php echo esc_attr( $tel( $telr ) ); ?>"><?php echo esc_html( $telr ); ?></a><br><a href="mailto:reception@gridhotel.cz">reception@gridhotel.cz</a></p></div>
	        <div class="k-block"><h3>Shuttle bus</h3><p><a href="tel:<?php echo esc_attr( $tel( $tels ) ); ?>"><?php echo esc_html( $tels ); ?></a></p></div>
	        <div class="k-block"><h3>Vedení</h3><p>Zuzana Ulmanová<br>výkonná ředitelka<br><a href="mailto:ulmanova@gridhotel.cz">ulmanova@gridhotel.cz</a></p></div>
	        <div class="k-block"><h3>Provozovatel</h3><p>GRH s.r.o.<br>IČ: <?php echo esc_html( $ico ); ?> · DIČ: <?php echo esc_html( $dic ); ?><br><?php echo esc_html( $spis ); ?></p></div>
	        <div class="k-block k-full"><h3>Fakturační údaje</h3><p class="data">Fio banka a.s.<br>CZK: 2203313575/2010 · IBAN CZ43 2010 0000 0022 0331 3575<br>EUR: 2003368942/2010 · IBAN CZ10 2010 0000 0020 0336 8942<br>BIC/SWIFT: FIOBCZPPXXX</p></div>
	      </div>
	      <div class="k-form">
	        <h3>Napište nám</h3>
	        <?php echo gridc_render_blind_form( 'kontakt' ); ?>
	      </div>
	    </div>
	    <div class="doprava-map" style="margin-top:34px"><iframe src="<?php echo esc_url( $mapsrc ); ?>" title="Mapa — GRID HOTEL" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_kontakt', 'gridc_sc_kontakt' );

/* [grid_doprava] — jak se k nám dostanete (mapa, navigace, letiště). */
function gridc_sc_doprava() {
	$adresa = gridhotel_get_option( 'adresa_1', 'Ostrovačická 936/65, Masarykův okruh' ) . ', ' . gridhotel_get_option( 'adresa_2', '641 00 Brno – Žebětín' );
	$mapsrc = 'https://www.google.com/maps?q=' . rawurlencode( 'GRID HOTEL, Ostrovačická 936/65, 641 00 Brno-Žebětín' ) . '&output=embed&hl=cs';
	$navig  = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( 'GRID HOTEL, Ostrovačická 936/65, 641 00 Brno-Žebětín' );
	ob_start(); ?>
	<section class="sec sec-dark carbon sec-pad grid-component grid-component--doprava" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap">
	    <span class="kicker">Kontakt · Příjezd</span>
	    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 10px">Jak se k nám dostanete?</h1>
	    <p style="max-width:64ch;color:var(--muted)">GRID HOTEL najdete přímo v areálu Autodromu Brno na adrese <strong style="color:var(--fg)"><?php echo esc_html( $adresa ); ?></strong>. Při příjezdu se řiďte dopravním značením <strong style="color:var(--fg)">„Grand Prix"</strong> a <strong style="color:var(--fg)">„Paddock"</strong>.</p>
	    <div class="doprava-map"><iframe src="<?php echo esc_url( $mapsrc ); ?>" title="Mapa — GRID HOTEL" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>
	    <div class="doprava-actions"><a class="btn" href="<?php echo esc_url( $navig ); ?>" target="_blank" rel="noopener">Navigovat →</a><a class="btn btn-ghost" href="tel:+420775778718">Shuttle bus: +420 775 778 718</a></div>
	  </div>
	</section>
	<section class="sec sec-light sec-pad">
	  <div class="wrap">
	    <div class="dp-cols">
	      <div class="dp-card">
	        <h3>Automobilem</h3>
	        <div class="dp-route"><b>Z dálnice D1 od Prahy</b><span>Použijte exit 178 – Ostrovačice, dále podle dopravního značení Grand Prix a Paddock.</span></div>
	        <div class="dp-route"><b>Po dálnici D1 od Brna</b><span>Použijte exit 182 Kývalka, poté sledujte směrovky „Grand Prix" či „Paddock". Ve směru od Olomouce, Bratislavy či Vídně použijte rovněž exit 182 Kývalka.</span></div>
	        <div class="dp-route"><b>Z Brna mimo dálnici D1</b><span>Silnicí III. třídy 3842 od Brna-Žebětína. Značeno dopravním značením Autodrom a Grand Prix.</span></div>
	        <div class="dp-route"><b>Z Prahy mimo dálnici D1</b><span>Silnicí I/23 na Kývalku, dále po silnici II/602 – sledujte směrovky „Grand Prix" a „Paddock".</span></div>
	      </div>
	      <div class="dp-card">
	        <h3>Hromadnou dopravou</h3>
	        <div class="dp-route"><b>Autobus 402</b><span>Stálá linka ze Starého Lískovce (z Hlavního nádraží použijte tramvaj 8 směr Starý Lískovec).</span></div>
	        <div class="dp-route"><b>Autobus 400 · zdarma</b><span>Během mezinárodních podniků jezdí speciální linka z Mendlova náměstí zdarma (z Hlavního nádraží tramvaj 1 směr Bystrc). Jízdní řád je zveřejněn před konáním akce.</span></div>
	        <h3 style="margin-top:26px">Parkování &amp; taxi</h3>
	        <div class="dp-route"><b>Parkování</b><span>Plán parkovacích ploch bude zveřejněn před konáním akce.</span></div>
	        <div class="dp-route"><b>Taxi</b><span>Naše recepce vám ráda přivolá taxi.</span></div>
	      </div>
	    </div>
	  </div>
	</section>
	<section class="sec sec-dark carbon sec-pad">
	  <div class="wrap">
	    <span class="kicker">Letecky</span>
	    <h2 style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 6px">Nejbližší letiště</h2>
	    <div class="dp-air-grid">
	      <div class="dp-airport"><div class="dp-head"><h4>Letiště Brno</h4><span class="dp-badge">25 km</span></div><p>Použijte dálnici D1 a exit 182 Kývalka, poté sledujte směrovky „Grand Prix" či „Paddock".</p><span class="dp-vign">Dálniční známka: CZ</span></div>
	      <div class="dp-airport"><div class="dp-head"><h4>Praha – Ruzyně</h4><span class="dp-badge">210 km</span></div><p>Pražský okruh, dále dálnice D1 a exit 178 Ostrovačice, poté směrovky „Grand Prix" či „Paddock".</p><span class="dp-vign">Dálniční známka: CZ</span></div>
	      <div class="dp-airport"><div class="dp-head"><h4>Vídeň – Schwechat</h4><span class="dp-badge">160–200 km</span></div><p><strong style="color:var(--fg)">Trasa 1:</strong> A4 (E85) směr Bratislava → D2 do Brna → D1, exit 182 Kývalka (200 km).</p><p><strong style="color:var(--fg)">Trasa 2:</strong> A4 → A23 → S1 → A5 → silnice 7 směr Brno → I/52 (R52) → D1 směr Praha, exit 182 Kývalka (160 km).</p><span class="dp-vign">Dálniční známky: AT, SK, CZ</span></div>
	      <div class="dp-airport"><div class="dp-head"><h4>Bratislava</h4><span class="dp-badge">150 km</span></div><p>Dálnicí D2 do Brna a poté dálnicí D1 směr Praha, exit 182 Kývalka.</p><span class="dp-vign">Dálniční známky: SK, CZ</span></div>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_doprava', 'gridc_sc_doprava' );

/* [grid_form_dotaznik] — stránka s dotazníkem spokojenosti. */
function gridc_sc_form_dotaznik() {
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-component grid-component--dotaznik" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:820px">
	    <span class="kicker">Zpětná vazba</span>
	    <h1 style="font-size:clamp(2.2rem,5vw,3.6rem);margin:14px 0 10px">Dotazník spokojenosti</h1>
	    <p style="color:var(--muted);margin-bottom:26px">Budeme rádi za vaše hodnocení pobytu v GRID HOTELU — pomůže nám zlepšovat služby.</p>
	    <?php echo gridc_render_blind_form( 'dotaznik' ); ?>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_form_dotaznik', 'gridc_sc_form_dotaznik' );
