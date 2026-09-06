<?php
/** Formulář nastavení — vykresluje gflb_admin_page(). */
if ( ! defined( 'ABSPATH' ) ) exit;

function gflb_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$n = gflb_get();
	?>
	<div class="wrap gflb-wrap">
		<h1>Foto lightbox</h1>
		<p class="description" style="max-width:840px">
			Lightbox se na frontendu zapne sám nad odkazy na obrázky ve vybraných kontejnerech.
			Shortcode <code>[garry_lightbox]</code> přenastaví jednu stránku,
			<code>[garry_lightbox_galerie ids="12,13"]</code> vykreslí vlastní mřížku náhledů.
			V Elementoru je k dispozici widget <strong>GARRY – Foto lightbox</strong>.
		</p>

		<form method="post" action="options.php" class="gflb-form">
			<?php settings_fields( 'gflb_group' ); ?>

			<div class="gflb-sloupce">
			<div class="gflb-hlavni">

			<h2 class="title">Pozadí</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Typ</th><td>
					<?php gflb_pole_vyber( $n, 'pozadi_typ', array(
						'solid'  => 'Plná barva',
						'linear' => 'Lineární přechod',
						'radial' => 'Radiální přechod',
						'conic'  => 'Kónický přechod',
						'rohy'   => 'Barva v každém rohu',
					) ); ?>
					<p class="description">U typu „Barva v každém rohu“ se skládají čtyři radiální přechody přes sebe.</p>
				</td></tr>
				<tr class="gflb-jen-prechod"><th scope="row">Barva 1 → 2</th><td>
					<?php gflb_pole_barva( $n, 'pozadi_barva1' ); ?>
					<?php gflb_pole_barva( $n, 'pozadi_barva2' ); ?>
					<p class="description">U plné barvy se použije jen Barva 1.</p>
				</td></tr>
				<tr class="gflb-jen-prechod"><th scope="row">Prostřední barva</th><td>
					<?php gflb_pole_barva( $n, 'pozadi_barva3' ); ?>
					<p class="description">Nepovinná třetí zastávka. Prázdné pole = plynulý přechod jen mezi Barvou 1 a 2.</p>
				</td></tr>
				<tr class="gflb-jen-prechod"><th scope="row">Pozice prostřední barvy</th><td>
					<?php gflb_pole_text( $n, 'pozadi_zlom', 'V procentech. Nižší hodnota drží sytou barvu u rohu, vyšší ji roztáhne přes plochu.', 'number', 'min="5" max="95" step="1"' ); ?>
				</td></tr>
				<tr class="gflb-jen-uhel"><th scope="row">Úhel</th><td>
					<?php gflb_pole_text( $n, 'pozadi_uhel', 'Ve stupních. 225° = z pravého horního rohu do levého dolního.', 'number', 'min="0" max="360" step="1"' ); ?>
				</td></tr>
				<tr class="gflb-jen-stred"><th scope="row">Střed</th><td>
					<?php gflb_pole_vyber( $n, 'pozadi_stred', array(
						'center'       => 'Střed',
						'top right'    => 'Vpravo nahoře',
						'top left'     => 'Vlevo nahoře',
						'bottom right' => 'Vpravo dole',
						'bottom left'  => 'Vlevo dole',
					) ); ?>
				</td></tr>
				<tr class="gflb-jen-rohy"><th scope="row">Barvy rohů</th><td>
					<p><label class="gflb-roh">vlevo nahoře <?php gflb_pole_barva( $n, 'pozadi_roh_lh' ); ?></label>
					   <label class="gflb-roh">vpravo nahoře <?php gflb_pole_barva( $n, 'pozadi_roh_ph' ); ?></label></p>
					<p><label class="gflb-roh">vlevo dole <?php gflb_pole_barva( $n, 'pozadi_roh_ld' ); ?></label>
					   <label class="gflb-roh">vpravo dole <?php gflb_pole_barva( $n, 'pozadi_roh_pd' ); ?></label></p>
				</td></tr>
				<tr><th scope="row">Krytí</th><td>
					<?php gflb_pole_text( $n, 'pozadi_kryti', 'V procentech. Nižší hodnota nechá prosvítat stránku pod lightboxem.', 'number', 'min="0" max="100" step="1"' ); ?>
				</td></tr>
				<tr><th scope="row">Rozostření pozadí</th><td>
					<?php gflb_pole_text( $n, 'pozadi_rozostreni', 'V pixelech, 0 = vypnuto. Na slabších zařízeních zpomaluje otevírání.', 'number', 'min="0" max="40" step="1"' ); ?>
				</td></tr>
			</table>

			<h2 class="title">Logo</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Zobrazit</th><td><?php gflb_pole_prepinac( $n, 'logo_zobrazit', 'Zobrazit logo v lightboxu' ); ?></td></tr>
				<tr><th scope="row">Zdroj</th><td>
					<?php gflb_pole_vyber( $n, 'logo_zdroj', array(
						'web'     => 'Logo webu (Vzhled → Přizpůsobit)',
						'priloha' => 'Vybraný obrázek z médií',
						'url'     => 'Vlastní adresa',
					) ); ?>
				</td></tr>
				<tr class="gflb-jen-priloha"><th scope="row">Obrázek</th><td>
					<input type="hidden" name="<?php echo esc_attr( GFLB_OPTION ); ?>[logo_priloha]" id="gflb-logo-priloha" value="<?php echo esc_attr( (string) $n['logo_priloha'] ); ?>">
					<button type="button" class="button" id="gflb-vybrat-logo">Vybrat obrázek</button>
					<button type="button" class="button-link" id="gflb-zrusit-logo">odebrat</button>
					<div id="gflb-nahled-loga">
						<?php if ( $n['logo_priloha'] ) echo wp_get_attachment_image( (int) $n['logo_priloha'], 'medium' ); ?>
					</div>
				</td></tr>
				<tr class="gflb-jen-url"><th scope="row">Adresa</th><td><?php gflb_pole_text( $n, 'logo_url', 'Úplná adresa obrázku.', 'url' ); ?></td></tr>
				<tr><th scope="row">Pozice</th><td>
					<?php gflb_pole_vyber( $n, 'logo_pozice', array(
						'vlevo-nahore'  => 'Vlevo nahoře',
						'vpravo-nahore' => 'Vpravo nahoře',
						'vlevo-dole'    => 'Vlevo dole',
						'vpravo-dole'   => 'Vpravo dole',
					) ); ?>
				</td></tr>
				<tr><th scope="row">Výška</th><td><?php gflb_pole_text( $n, 'logo_vyska', 'V pixelech.', 'number', 'min="10" max="200"' ); ?></td></tr>
				<tr><th scope="row">Krytí</th><td><?php gflb_pole_text( $n, 'logo_kryti', 'V procentech.', 'number', 'min="0" max="100"' ); ?></td></tr>
			</table>

			<h2 class="title">Text nad snímkem</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Zobrazit</th><td><?php gflb_pole_prepinac( $n, 'nadpis_zobrazit', 'Zobrazit popisek nad snímkem' ); ?></td></tr>
				<tr><th scope="row">Šablona</th><td>
					<?php gflb_pole_text( $n, 'nadpis_sablona', '', 'text' ); ?>
					<p class="description">Zástupné značky:
						<code>{web}</code> název webu,
						<code>{galerie}</code> nadpis sekce s galerií,
						<code>{index}</code> pořadí snímku,
						<code>{celkem}</code> počet snímků,
						<code>{titulek}</code>, <code>{popisek}</code>, <code>{popis}</code>, <code>{alt}</code>.
					</p>
				</td></tr>
				<tr><th scope="row">Zarovnání</th><td>
					<?php gflb_pole_vyber( $n, 'nadpis_zarovnani', array( 'left' => 'Vlevo', 'center' => 'Na střed', 'right' => 'Vpravo' ) ); ?>
				</td></tr>
				<tr><th scope="row">Barva</th><td><?php gflb_pole_barva( $n, 'nadpis_barva' ); ?></td></tr>
				<tr><th scope="row">Velikost</th><td><?php gflb_pole_text( $n, 'nadpis_velikost', 'V pixelech.', 'number', 'min="8" max="40"' ); ?></td></tr>
			</table>

			<h2 class="title">Informace pod snímkem</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Zobrazit</th><td><?php gflb_pole_prepinac( $n, 'popisek_zobrazit', 'Zobrazit doprovodnou informaci pod snímkem' ); ?></td></tr>
				<tr><th scope="row">Zdroj</th><td>
					<?php gflb_pole_vyber( $n, 'popisek_zdroj', array(
						'caption'     => 'Popisek z knihovny médií',
						'description' => 'Popis z knihovny médií',
						'alt'         => 'Alternativní text obrázku',
						'title'       => 'Titulek obrázku',
					) ); ?>
					<p class="description">
						U galerie z <code>[garry_lightbox_galerie]</code> plugin načte všechny čtyři zdroje z knihovny médií.
						U cizích galerií pracuje s tím, co je opravdu ve stránce — popisek z <code>&lt;figcaption&gt;</code>,
						alternativní text a titulek z obrázku. Popis z médií tam WordPress nevypisuje, takže volba
						„Popis z knihovny médií“ zůstane u cizích galerií prázdná.
					</p>
				</td></tr>
				<tr><th scope="row">Barva</th><td><?php gflb_pole_barva( $n, 'popisek_barva' ); ?></td></tr>
				<tr><th scope="row">Velikost</th><td><?php gflb_pole_text( $n, 'popisek_velikost', 'V pixelech.', 'number', 'min="8" max="40"' ); ?></td></tr>
			</table>

			<h2 class="title">Ukazatel pořadí</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Zobrazit</th><td>
					<?php gflb_pole_prepinac( $n, 'ukazatel_zobrazit', 'Zobrazit vodorovný ukazatel pod snímkem' ); ?><br>
					<?php gflb_pole_prepinac( $n, 'ukazatel_cisla', 'Vypisovat pod body čísla snímků' ); ?>
				</td></tr>
				<tr><th scope="row">Barvy</th><td>
					<p><label class="gflb-roh">čára <?php gflb_pole_barva( $n, 'ukazatel_barva_cary' ); ?></label>
					   <label class="gflb-roh">projitá část <?php gflb_pole_barva( $n, 'ukazatel_barva_vypln' ); ?></label></p>
					<p><label class="gflb-roh">bod <?php gflb_pole_barva( $n, 'ukazatel_barva_bod' ); ?></label>
					   <label class="gflb-roh">aktivní bod <?php gflb_pole_barva( $n, 'ukazatel_barva_aktiv' ); ?></label>
					   <label class="gflb-roh">číslo <?php gflb_pole_barva( $n, 'ukazatel_barva_cislo' ); ?></label></p>
				</td></tr>
				<tr><th scope="row">Bodů v jednom okně</th><td>
					<?php gflb_pole_prepinac( $n, 'ukazatel_auto', 'Spočítat podle skutečné šířky ukazatele' ); ?>
					<p><?php gflb_pole_text( $n, 'ukazatel_max_bodu', '', 'number', 'min="2" max="200"' ); ?></p>
					<p class="description">
						U delších sérií se body nezmenšují, ale stránkují po oknech — jinak by se čísla
						slila a přestala splňovat čitelnost podle WCAG. Sousední okna sdílejí krajní bod,
						takže poslední bod jednoho okna je prvním bodem dalšího (1–20, 20–39, 39–58…).
						Se zapnutým automatickým výpočtem je tohle číslo horní strop; na užší obrazovce
						se okno samo zmenší.
					</p>
				</td></tr>
			</table>

			<h2 class="title">Náhledy pod ukazatelem</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Zobrazit</th><td>
					<?php gflb_pole_prepinac( $n, 'nahledy_zobrazit', 'Zobrazit pás náhledů celé série' ); ?>
					<p class="description">Pás se posouvá tak, aby aktivní snímek zůstal na třetí pozici zleva — dva náhledy dozadu, tři dopředu.</p>
				</td></tr>
				<tr><th scope="row">Výška náhledu</th><td><?php gflb_pole_text( $n, 'nahledy_vyska', 'V pixelech.', 'number', 'min="28" max="160"' ); ?></td></tr>
				<tr><th scope="row">Mezera</th><td><?php gflb_pole_text( $n, 'nahledy_mezera', 'V pixelech.', 'number', 'min="0" max="40"' ); ?></td></tr>
				<tr><th scope="row">Krytí neaktivních</th><td><?php gflb_pole_text( $n, 'nahledy_kryti', 'V procentech.', 'number', 'min="0" max="100"' ); ?></td></tr>
				<tr><th scope="row">Rámeček aktivního</th><td>
					<?php gflb_pole_barva( $n, 'nahledy_ramecek' ); ?>
					<p class="description">Prázdné pole = převezme barvu aktivního bodu ukazatele.</p>
				</td></tr>
			</table>

			<h2 class="title">Šipky a zavírání</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Šipky</th><td><?php gflb_pole_prepinac( $n, 'sipky_zobrazit', 'Zobrazit šipky doleva a doprava' ); ?></td></tr>
				<tr><th scope="row">Styl</th><td>
					<?php gflb_pole_vyber( $n, 'sipky_styl', array( 'sipka' => 'Samotná šipka', 'kruh' => 'Šipka v kroužku', 'ctverec' => 'Šipka v rámečku' ) ); ?>
				</td></tr>
				<tr><th scope="row">Barvy šipek</th><td>
					<label class="gflb-roh">základní <?php gflb_pole_barva( $n, 'sipky_barva' ); ?></label>
					<label class="gflb-roh">při najetí <?php gflb_pole_barva( $n, 'sipky_hover' ); ?></label>
				</td></tr>
				<tr><th scope="row">Velikost šipek</th><td><?php gflb_pole_text( $n, 'sipky_velikost', 'V pixelech.', 'number', 'min="20" max="120"' ); ?></td></tr>
				<tr><th scope="row">Barvy křížku</th><td>
					<label class="gflb-roh">základní <?php gflb_pole_barva( $n, 'zavrit_barva' ); ?></label>
					<label class="gflb-roh">při najetí <?php gflb_pole_barva( $n, 'zavrit_hover' ); ?></label>
				</td></tr>
			</table>

			<h2 class="title">Další galerie</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Zobrazit</th><td>
					<?php gflb_pole_prepinac( $n, 'dalsi_zobrazit', 'Nabídnout v pravém dolním rohu přechod na další galerii' ); ?>
					<p class="description">
						Vykreslí se jen tam, kde stránka nabídne cíl atributem <code>data-glb-dalsi</code>
						na kontejneru galerie (u GRID Hotelu to dělá šablona detailu kategorie pokoje).
						Lightbox se nezavře — načte snímky další galerie a přepíše adresu; stránka pod ním
						se doopravdy načte až při zavření.
					</p>
				</td></tr>
				<tr><th scope="row">Popisek</th><td>
					<?php gflb_pole_text( $n, 'dalsi_popisek', '', 'text' ); ?>
					<p class="description">Značka <code>{nazev}</code> se nahradí názvem další galerie.</p>
				</td></tr>
				<tr><th scope="row">Barvy</th><td>
					<label class="gflb-roh">základní <?php gflb_pole_barva( $n, 'dalsi_barva' ); ?></label>
					<label class="gflb-roh">při najetí <?php gflb_pole_barva( $n, 'dalsi_hover' ); ?></label>
				</td></tr>
			</table>

			<h2 class="title">Chování</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Zapnuto</th><td><?php gflb_pole_prepinac( $n, 'aktivni', 'Lightbox je na webu aktivní' ); ?></td></tr>
				<tr><th scope="row">Kde se aktivuje</th><td>
					<textarea name="<?php echo esc_attr( GFLB_OPTION ); ?>[selektory]" rows="2" class="large-text code"><?php echo esc_textarea( (string) $n['selektory'] ); ?></textarea>
					<p class="description">CSS selektory odkazů, které lightbox převezme. Oddělujte čárkou.</p>
				</td></tr>
				<tr><th scope="row">Ovládání</th><td>
					<?php gflb_pole_prepinac( $n, 'smycka', 'Z posledního snímku pokračovat na první' ); ?><br>
					<?php gflb_pole_prepinac( $n, 'klavesnice', 'Ovládání klávesnicí (šipky, Esc, Home, End)' ); ?><br>
					<?php gflb_pole_prepinac( $n, 'gesta', 'Gesta na dotykových zařízeních (přejetí prstem)' ); ?><br>
					<?php gflb_pole_prepinac( $n, 'kolecko', 'Přepínání kolečkem myši' ); ?><br>
					<?php gflb_pole_prepinac( $n, 'predlozit', 'Přednačítat sousední snímky' ); ?><br>
					<?php gflb_pole_prepinac( $n, 'hash', 'Zapisovat pořadí snímku do adresy (#foto-3)' ); ?>
				</td></tr>
				<tr><th scope="row">Automatické přehrávání</th><td>
					<?php gflb_pole_prepinac( $n, 'autoplay', 'Přepínat snímky samo' ); ?>
					<p><?php gflb_pole_text( $n, 'autoplay_ms', 'Prodleva v milisekundách.', 'number', 'min="1000" max="60000" step="500"' ); ?></p>
				</td></tr>
			</table>

			<h2 class="title">Stránky s lightboxem</h2>
			<?php
			$stranky = gflb_stranky();
			$funkce  = gflb_prepinatelne();
			if ( ! $stranky ) : ?>
				<p class="description" style="max-width:840px">
					Zatím prázdné. Seznam se plní sám: jakmile se lightbox na nějaké stránce
					poprvé vykreslí, objeví se tady řádek. Pak u něj jde vypnout, co na té
					stránce nechcete — třeba náhledy u velké fotogalerie nebo přechod na
					další galerii mimo detaily pokojů.
				</p>
			<?php else :
				uasort( $stranky, function ( $a, $b ) {
					return strcasecmp( (string) ( $a['nazev'] ?? '' ), (string) ( $b['nazev'] ?? '' ) );
				} ); ?>
				<input type="hidden" name="<?php echo esc_attr( GFLB_STRANKY ); ?>[__odeslano]" value="1">
				<p class="description" style="max-width:840px">
					Bez zaškrtnutého <strong>vlastního nastavení</strong> se stránka řídí globálním
					nastavením výše. Zaškrtnutím se pro ni uplatní přesně to, co je v jejím řádku.
				</p>
				<div style="overflow-x:auto">
				<table class="widefat striped gflb-stranky">
					<thead><tr>
						<th>Stránka</th>
						<th style="text-align:center">Vlastní<br>nastavení</th>
						<?php foreach ( $funkce as $popisek ) : ?>
							<th style="text-align:center"><?php echo esc_html( $popisek ); ?></th>
						<?php endforeach; ?>
					</tr></thead>
					<tbody>
					<?php foreach ( $stranky as $klic => $radek ) :
						$vlastni = false;
						foreach ( array_keys( $funkce ) as $f ) { if ( array_key_exists( $f, $radek ) ) { $vlastni = true; break; } }
						$pole = GFLB_STRANKY . '[' . $klic . ']'; ?>
						<tr>
							<td>
								<?php if ( ! empty( $radek['url'] ) ) : ?>
									<a href="<?php echo esc_url( $radek['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $radek['nazev'] ?: $klic ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $radek['nazev'] ?: $klic ); ?>
								<?php endif; ?>
								<br><span class="description"><?php echo esc_html( $klic ); ?></span>
							</td>
							<td style="text-align:center">
								<input type="checkbox" class="gflb-vlastni" name="<?php echo esc_attr( $pole ); ?>[__vlastni]" value="1" <?php checked( $vlastni ); ?>>
							</td>
							<?php foreach ( array_keys( $funkce ) as $f ) :
								$zapnuto = array_key_exists( $f, $radek ) ? ! empty( $radek[ $f ] ) : ! empty( $n[ $f ] ); ?>
								<td style="text-align:center">
									<input type="checkbox" name="<?php echo esc_attr( $pole . '[' . $f . ']' ); ?>" value="1"
										<?php checked( $zapnuto ); ?> <?php disabled( ! $vlastni ); ?>>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				</div>
			<?php endif; ?>

			<?php submit_button(); ?>
			</div>

			<div class="gflb-nahled-sloupec">
				<h2 class="title">Náhled</h2>
				<div class="gflb-nahled" id="gflb-nahled" data-web="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<div class="gflb-nahled-pozadi"></div>
					<img class="gflb-nahled-logo" src="<?php echo esc_url( gflb_logo_url( $n ) ); ?>" alt="">
					<p class="gflb-nahled-nadpis"></p>
					<div class="gflb-nahled-scena"><span>FOTO</span></div>
					<p class="gflb-nahled-popisek">Popisek snímku</p>
					<div class="gflb-nahled-ukazatel">
						<span class="gflb-nahled-cara"></span>
						<span class="gflb-nahled-vypln"></span>
						<ol></ol>
					</div>
				</div>
				<p class="description">Náhled je orientační — ukazuje pozadí, logo, popisky a ukazatel podle nastavení nad ním.</p>
			</div>
			</div>
		</form>
	</div>
	<?php
}
