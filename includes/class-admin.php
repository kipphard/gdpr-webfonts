<?php
/**
 * WordPress-Admin-UI: Menüs, Seiten und POST-Handler.
 *
 * @package Kipphard\Dsgvo_Webfonts
 */

namespace Kipphard\Dsgvo_Webfonts;

defined( 'ABSPATH' ) || exit;

/**
 * Registriert Admin-Menüs und verarbeitet Formular-Übermittlungen.
 */
class Admin {

	/**
	 * Alle WordPress-Hooks für den Admin-Bereich registrieren.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_dwf_run_scan', array( $this, 'handle_run_scan' ) );
		add_action( 'admin_post_dwf_save_settings', array( $this, 'handle_save_settings' ) );
	}

	/**
	 * Hauptmenü und Untermenüs registrieren.
	 */
	public function register_menus() {
		add_menu_page(
			__( 'DSGVO Webfonts', 'dsgvo-webfonts' ),
			__( 'DSGVO Webfonts', 'dsgvo-webfonts' ),
			Helpers::CAP,
			DWF_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-shield',
			80
		);

		add_submenu_page(
			DWF_SLUG,
			__( 'Dashboard', 'dsgvo-webfonts' ),
			__( 'Dashboard', 'dsgvo-webfonts' ),
			Helpers::CAP,
			DWF_SLUG,
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			DWF_SLUG,
			__( 'Einstellungen', 'dsgvo-webfonts' ),
			__( 'Einstellungen', 'dsgvo-webfonts' ),
			Helpers::CAP,
			'dsgvo-webfonts-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * CSS und JS nur auf Plugin-Admin-Seiten einbinden.
	 *
	 * @param string $hook Aktueller Admin-Seiten-Hook-Suffix.
	 */
	public function enqueue_assets( $hook ) {
		$plugin_hooks = array(
			'toplevel_page_' . DWF_SLUG,
			DWF_SLUG . '_page_dsgvo-webfonts-settings',
		);
		if ( ! in_array( $hook, $plugin_hooks, true ) ) {
			return;
		}
		wp_enqueue_style(
			'dwf-admin',
			DWF_URL . 'assets/admin.css',
			array(),
			DWF_VERSION
		);
		wp_enqueue_script(
			'dwf-admin',
			DWF_URL . 'assets/admin.js',
			array(),
			DWF_VERSION,
			true
		);
	}

	// -------------------------------------------------------------------------
	// POST-Handler
	// -------------------------------------------------------------------------

	/**
	 * Verarbeitet die "Scan starten"-Formularübermittlung.
	 * Pro scannt alle Seiten, Free scannt die Standard-Auswahl.
	 */
	public function handle_run_scan() {
		Helpers::guard_post( 'dwf_run_scan' );

		$urls   = Helpers::is_pro() ? Scanner::all_urls() : Scanner::default_urls();
		$report = ( new Scanner() )->scan( $urls );
		update_option( Helpers::OPT_LAST_REPORT, $report );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => DWF_SLUG,
					'notice' => 'scan_done',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Speichert die Plugin-Einstellungen.
	 */
	public function handle_save_settings() {
		Helpers::guard_post( 'dwf_save_settings' );

		update_option( Helpers::OPT_SETTINGS, Helpers::sanitize_settings( $_POST ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'dsgvo-webfonts-settings',
					'notice' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// Seiten-Renderer
	// -------------------------------------------------------------------------

	/**
	 * Rendert die Dashboard-Seite.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( Helpers::CAP ) ) {
			return;
		}

		$report = get_option( Helpers::OPT_LAST_REPORT );
		$notice = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$is_pro = Helpers::is_pro();
		?>
		<div class="wrap dwf-wrap">
			<h1><?php esc_html_e( 'DSGVO Webfonts & externe Anfragen', 'dsgvo-webfonts' ); ?></h1>

			<?php if ( 'scan_done' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Scan abgeschlossen. Die Ergebnisse werden unten angezeigt.', 'dsgvo-webfonts' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="dwf-scan-box">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="dwf-scan-form">
					<input type="hidden" name="action" value="dwf_run_scan">
					<?php wp_nonce_field( 'dwf_run_scan' ); ?>
					<button type="submit" class="button button-primary dwf-scan-btn">
						<?php esc_html_e( 'Scan starten', 'dsgvo-webfonts' ); ?>
					</button>
					<span class="dwf-scan-running" style="display:none;">
						<?php esc_html_e( 'Scan läuft …', 'dsgvo-webfonts' ); ?>
					</span>
					<p class="description">
						<?php if ( $is_pro ) : ?>
							<?php esc_html_e( 'Scannt alle veröffentlichten Seiten und Beiträge (bis zu 200 URLs).', 'dsgvo-webfonts' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Kostenlose Version: scannt die Startseite sowie bis zu 4 zuletzt veröffentlichte Seiten/Beiträge.', 'dsgvo-webfonts' ); ?>
							<a href="https://products.kipphard.com/dsgvo-webfonts" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Mit Pro alle Seiten scannen →', 'dsgvo-webfonts' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</form>
			</div>

			<?php if ( empty( $report ) ) : ?>
				<div class="dwf-empty-state">
					<span class="dashicons dashicons-shield dwf-empty-icon"></span>
					<h2><?php esc_html_e( 'Noch kein Scan durchgeführt', 'dsgvo-webfonts' ); ?></h2>
					<p>
						<?php esc_html_e( 'Klicke auf "Scan starten", um das gerenderte HTML deiner Seiten auf externe Anfragen zu prüfen. Das Plugin erkennt Dienste wie Google Fonts, Analytics, YouTube, Gravatar und weitere, die personenbezogene Daten an Dritte übertragen.', 'dsgvo-webfonts' ); ?>
					</p>
					<p class="dwf-disclaimer">
						<strong><?php esc_html_e( 'Hinweis:', 'dsgvo-webfonts' ); ?></strong>
						<?php esc_html_e( 'Statischer Scan des ausgelieferten HTML – per JavaScript dynamisch nachgeladene Anfragen werden ggf. nicht erkannt. Ersetzt keine Rechtsberatung.', 'dsgvo-webfonts' ); ?>
					</p>
				</div>
			<?php else : ?>
				<?php $this->render_report( $report ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Rendert den Scan-Bericht.
	 *
	 * @param array<string,mixed> $report Bericht-Array aus dem Scanner.
	 */
	private function render_report( array $report ) {
		$findings       = isset( $report['findings'] ) ? (array) $report['findings'] : array();
		$counts         = isset( $report['counts'] ) ? (array) $report['counts'] : array( 'high' => 0, 'medium' => 0, 'low' => 0 );
		$total_services = isset( $report['total_services'] ) ? (int) $report['total_services'] : 0;
		$total_requests = isset( $report['total_requests'] ) ? (int) $report['total_requests'] : 0;
		$scanned_at     = isset( $report['scanned_at'] ) ? (int) $report['scanned_at'] : 0;

		// Höchstes Risiko-Level ermitteln.
		$overall_risk = 'low';
		if ( ! empty( $counts['high'] ) ) {
			$overall_risk = 'high';
		} elseif ( ! empty( $counts['medium'] ) ) {
			$overall_risk = 'medium';
		}
		?>
		<div class="dwf-report">

			<div class="dwf-summary-row">
				<span class="dwf-badge dwf-badge-<?php echo esc_attr( $overall_risk ); ?>">
					<?php
					printf(
						/* translators: %s: Risiko-Level (Hoch/Mittel/Niedrig) */
						esc_html__( 'Gesamtrisiko: %s', 'dsgvo-webfonts' ),
						esc_html( Helpers::risk_label( $overall_risk ) )
					);
					?>
				</span>
				<span class="dwf-summary-stat">
					<?php
					printf(
						/* translators: %d: Anzahl erkannter Dienste */
						esc_html__( '%d externer Dienst(e)', 'dsgvo-webfonts' ),
						(int) $total_services
					);
					?>
				</span>
				<span class="dwf-summary-stat">
					<?php
					printf(
						/* translators: %d: Gesamtanzahl externer Anfragen */
						esc_html__( '%d externe Anfrage(n)', 'dsgvo-webfonts' ),
						(int) $total_requests
					);
					?>
				</span>
				<?php if ( $scanned_at > 0 ) : ?>
					<span class="dwf-summary-stat">
						<?php
						printf(
							/* translators: %s: Datum und Uhrzeit des Scans */
							esc_html__( 'Gescannt am: %s', 'dsgvo-webfonts' ),
							esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $scanned_at ) )
						);
						?>
					</span>
				<?php endif; ?>
			</div>

			<p class="dwf-disclaimer">
				<strong><?php esc_html_e( 'Hinweis:', 'dsgvo-webfonts' ); ?></strong>
				<?php esc_html_e( 'Statischer Scan des ausgelieferten HTML – per JavaScript dynamisch nachgeladene Anfragen werden ggf. nicht erkannt. Ersetzt keine Rechtsberatung.', 'dsgvo-webfonts' ); ?>
			</p>

			<?php if ( empty( $findings ) ) : ?>
				<div class="dwf-no-findings">
					<p><?php esc_html_e( 'Keine externen Anfragen erkannt. Bitte prüfe dennoch manuell, ob JavaScript-basierte Anfragen vorliegen.', 'dsgvo-webfonts' ); ?></p>
				</div>
			<?php else : ?>
				<h2><?php esc_html_e( 'Erkannte externe Dienste', 'dsgvo-webfonts' ); ?></h2>
				<div class="dwf-finding-list">
					<?php foreach ( $findings as $finding ) : ?>
						<?php
						$risk     = isset( $finding['risk'] ) ? sanitize_key( $finding['risk'] ) : 'medium';
						$category = isset( $finding['category'] ) ? sanitize_key( $finding['category'] ) : 'other';
						$label    = isset( $finding['label'] ) ? $finding['label'] : '';
						$hint     = isset( $finding['hint'] ) ? $finding['hint'] : '';
						$count    = isset( $finding['count'] ) ? (int) $finding['count'] : 0;
						$samples  = isset( $finding['samples'] ) ? (array) $finding['samples'] : array();
						?>
						<div class="dwf-finding-card dwf-card-<?php echo esc_attr( $risk ); ?>">
							<div class="dwf-finding-header">
								<span class="dwf-badge dwf-badge-<?php echo esc_attr( $risk ); ?>">
									<?php echo esc_html( Helpers::risk_label( $risk ) ); ?>
								</span>
								<span class="dwf-category-chip">
									<?php echo esc_html( Helpers::category_label( $category ) ); ?>
								</span>
								<strong class="dwf-finding-label"><?php echo esc_html( $label ); ?></strong>
								<span class="dwf-finding-count">
									<?php
									printf(
										/* translators: %d: Anzahl Anfragen */
										esc_html__( '%d Anfrage(n)', 'dsgvo-webfonts' ),
										$count
									);
									?>
								</span>
							</div>
							<div class="dwf-finding-body">
								<?php if ( '' !== $hint ) : ?>
									<p class="dwf-finding-hint">
										<strong><?php esc_html_e( 'Empfehlung:', 'dsgvo-webfonts' ); ?></strong>
										<?php echo esc_html( $hint ); ?>
									</p>
								<?php endif; ?>
								<?php if ( ! empty( $samples ) ) : ?>
									<details class="dwf-samples-wrap">
										<summary><?php esc_html_e( 'Beispiel-URLs anzeigen', 'dsgvo-webfonts' ); ?></summary>
										<ul class="dwf-sample-list">
											<?php foreach ( $samples as $sample_url ) : ?>
												<li>
													<a href="<?php echo esc_url( $sample_url ); ?>" target="_blank" rel="noopener noreferrer nofollow">
														<?php echo esc_html( $sample_url ); ?>
													</a>
												</li>
											<?php endforeach; ?>
										</ul>
									</details>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="dwf-quick-actions card">
				<h2><?php esc_html_e( 'Schnell-Maßnahmen', 'dsgvo-webfonts' ); ?></h2>
				<p>
					<?php esc_html_e( 'Einige häufige Anfragen kannst du direkt über die Plugin-Einstellungen deaktivieren oder lokal ersetzen.', 'dsgvo-webfonts' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dsgvo-webfonts-settings' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Zu den Einstellungen', 'dsgvo-webfonts' ); ?>
					</a>
				</p>
			</div>

		</div>
		<?php
	}

	/**
	 * Rendert die Einstellungsseite.
	 */
	public function render_settings() {
		if ( ! current_user_can( Helpers::CAP ) ) {
			return;
		}

		$notice   = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$settings = Helpers::get_settings();
		$is_pro   = Helpers::is_pro();
		?>
		<div class="wrap dwf-wrap">
			<h1><?php esc_html_e( 'Einstellungen', 'dsgvo-webfonts' ); ?></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Einstellungen gespeichert.', 'dsgvo-webfonts' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="dwf-settings-info card">
				<h2><?php esc_html_e( 'Kostenlose Version', 'dsgvo-webfonts' ); ?></h2>
				<p>
					<?php esc_html_e( 'Die kostenlose Version scannt die Startseite sowie bis zu 4 zuletzt veröffentlichte Seiten/Beiträge und bietet grundlegende Schnell-Maßnahmen für häufige DSGVO-Probleme.', 'dsgvo-webfonts' ); ?>
				</p>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dwf_save_settings">
				<?php wp_nonce_field( 'dwf_save_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Google Fonts entfernen', 'dsgvo-webfonts' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="remove_google_fonts" value="1"
									<?php checked( $settings['remove_google_fonts'] ); ?>>
								<?php esc_html_e( 'Google Fonts entfernen (extern → keine Anfrage an Google)', 'dsgvo-webfonts' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Entfernt alle externen Google-Fonts-Anfragen aus dem HTML (<link>-Tags, @import). Stelle sicher, dass du die Schriften lokal hostest oder andere Schriften verwendest.', 'dsgvo-webfonts' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'WordPress-Emojis deaktivieren', 'dsgvo-webfonts' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="disable_emojis" value="1"
									<?php checked( $settings['disable_emojis'] ); ?>>
								<?php esc_html_e( 'WordPress-Emojis deaktivieren (entfernt s.w.org-Anfrage)', 'dsgvo-webfonts' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'WordPress lädt standardmäßig Emoji-Skripte von s.w.org. Diese Option deaktiviert dieses Verhalten vollständig.', 'dsgvo-webfonts' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Gravatar lokal ersetzen', 'dsgvo-webfonts' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="disable_gravatar" value="1"
									<?php checked( $settings['disable_gravatar'] ); ?>>
								<?php esc_html_e( 'Gravatar lokal ersetzen (keine Anfrage an gravatar.com)', 'dsgvo-webfonts' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Ersetzt alle Gravatar-Anfragen durch einen lokalen SVG-Platzhalter-Avatar. Kein externer Request an gravatar.com.', 'dsgvo-webfonts' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Einstellungen speichern', 'dsgvo-webfonts' ) ); ?>
			</form>

			<?php if ( ! $is_pro ) : ?>
				<div class="dwf-pro-teaser card">
					<h2><?php esc_html_e( 'DSGVO Webfonts Pro', 'dsgvo-webfonts' ); ?></h2>
					<ul class="dwf-pro-features">
						<li>
							<span class="dashicons dashicons-media-text"></span>
							<?php esc_html_e( 'Google Fonts automatisch lokal hosten (Schriften bleiben erhalten)', 'dsgvo-webfonts' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-video-alt3"></span>
							<?php esc_html_e( 'Externe Einbettungen lokalisieren: YouTube (nocookie), Google Maps & Vimeo als Klick-zum-Laden-Platzhalter', 'dsgvo-webfonts' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-clock"></span>
							<?php esc_html_e( 'Geplanter automatischer Re-Scan + E-Mail-Bericht', 'dsgvo-webfonts' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-networking"></span>
							<?php esc_html_e( 'Multisite-Unterstützung', 'dsgvo-webfonts' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-pdf"></span>
							<?php esc_html_e( 'PDF-Konformitätsbericht', 'dsgvo-webfonts' ); ?>
						</li>
					</ul>
					<p>
						<a href="https://products.kipphard.com/dsgvo-webfonts" target="_blank" rel="noopener noreferrer" class="button button-secondary">
							<?php esc_html_e( 'Jetzt upgraden', 'dsgvo-webfonts' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

		</div>
		<?php
	}
}
