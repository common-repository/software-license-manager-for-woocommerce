<?php
/**
 * Software License Manager for WooCommerce
 *
 * @package    Software License Manager for WooCommerce
 * @subpackage SlmForWooCommerceAdmin Management screen
	Copyright (c) 2019- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$slmforwoocommerceadmin = new SlmForWooCommerceAdmin();
add_action( 'admin_notices', array( $slmforwoocommerceadmin, 'notices' ) );

/** ==================================================
 * Management screen
 */
class SlmForWooCommerceAdmin {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'register_settings' ) );

		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );

	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'software-license-manager-for-woocommerce/slmforwoocommerce.php';
		}
		if ( $file === $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'options-general.php?page=slmforwoocommerce' ) . '">' . __( 'Settings' ) . '</a>';
		}
			return $links;
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_menu() {
		add_options_page( 'Software License Manager for WooCommerce Options', 'Software License Manager for WooCommerce', 'manage_options', 'slmforwoocommerce', array( $this, 'plugin_options' ) );
	}

	/** ==================================================
	 * For only admin style
	 *
	 * @since 1.00
	 */
	private function is_my_plugin_screen() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'settings_page_slmforwoocommerce' === $screen->id ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_options() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated();

		$scriptname                 = admin_url( 'options-general.php?page=slmforwoocommerce' );
		$slmforwoocommerce_settings = get_option( 'slmforwoocommerce' );

		if ( is_multisite() ) {
			$slm_install_url  = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=software-license-manager' );
			$slmc_install_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=software-license-manager-client' );
		} else {
			$slm_install_url  = admin_url( 'plugin-install.php?tab=plugin-information&plugin=software-license-manager' );
			$slmc_install_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=software-license-manager-client' );
		}
		$slm_install_html  = '<a href="' . $slm_install_url . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">Software License Manager</a>';
		$slmc_install_html = '<a href="' . $slmc_install_url . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">Software License Manager Client</a>';

		?>

		<div class="wrap">
		<h2>Software License Manager for WooCommerce</h2>

			<details>
			<summary><strong><?php esc_html_e( 'Various links of this plugin', 'software-license-manager-for-woocommerce' ); ?></strong></summary>
			<?php $this->credit(); ?>
			</details>

			<div class="wrap">
				<h2><?php esc_html_e( 'Settings' ); ?></h2>	

				<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
				<?php wp_nonce_field( 'slmf_set', 'slmforwoocommerce_set' ); ?>

				<div style="margin: 5px; padding: 5px;">
				<h3>Software License Manager</h3>
				<?php
				/* translators: %1$s: Software License Manager */
				echo wp_kses_post( sprintf( __( '%1$s must be installed on this server or another server.', 'software-license-manager-for-woocommerce' ), $slm_install_html ) );
				/* translators: %1$s: Software License Manager Client */
				echo wp_kses_post( sprintf( __( 'Using %1$s, you can easily embed the license key authentication screen.', 'software-license-manager-for-woocommerce' ), $slmc_install_html ) );
				?>
				<div style="display: block;padding:5px 5px"><?php esc_html_e( 'License Server URL', 'software-license-manager-for-woocommerce' ); ?> : <input type="text" name="license_server_url" style="width: 500px;" value="<?php echo esc_attr( $slmforwoocommerce_settings['license_server_url'] ); ?>"></div>
				<div style="display: block;padding:5px 5px"><?php esc_html_e( 'Secret Key for License Creation', 'software-license-manager-for-woocommerce' ); ?> : <input type="text" name="secretkey" style="width: 250px;" value="<?php echo esc_attr( $slmforwoocommerce_settings['secretkey'] ); ?>"></div>
				</div>

				<?php submit_button( __( 'Save Changes' ), 'large', 'Manageset', false ); ?>

				</form>

			</div>

		</div>
		<?php
	}
	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( __( 'https://wordpress.org/plugins/%s/faq', 'software-license-manager-for-woocommerce' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = __( 'https://shop.riverforest-wp.info/donate/', 'software-license-manager-for-woocommerce' );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'software-license-manager-for-woocommerce' ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php

	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 1.00
	 */
	private function options_updated() {

		if ( isset( $_POST['Manageset'] ) && ! empty( $_POST['Manageset'] ) ) {
			if ( check_admin_referer( 'slmf_set', 'slmforwoocommerce_set' ) ) {
				$slmforwoocommerce_settings = get_option( 'slmforwoocommerce' );
				if ( ! empty( $_POST['license_server_url'] ) ) {
					$slmforwoocommerce_settings['license_server_url'] = esc_url_raw( wp_unslash( $_POST['license_server_url'] ) );
				} else {
					$slmforwoocommerce_settings['license_server_url'] = site_url();
				}
				if ( ! empty( $_POST['secretkey'] ) ) {
					$slmforwoocommerce_settings['secretkey'] = sanitize_text_field( wp_unslash( $_POST['secretkey'] ) );
				} else {
					$slmforwoocommerce_settings['secretkey'] = null;
				}
				update_option( 'slmforwoocommerce', $slmforwoocommerce_settings );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html__( 'Settings' ) . ' --> ' . esc_html__( 'Settings saved.' ) . '</li></ul></div>';
			}
		}

	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		if ( get_option( 'slmforwoocommerce' ) ) {
			$slmwoo_settings = get_option( 'slmforwoocommerce' );
			/* ver 1.10 later */
			if ( array_key_exists( 'product_id', $slmwoo_settings ) &&
					array_key_exists( 'max_allowed_domains', $slmwoo_settings ) &&
					array_key_exists( 'expiry_second', $slmwoo_settings ) ) {
				unset( $slmwoo_settings['product_id'] );
				unset( $slmwoo_settings['max_allowed_domains'] );
				unset( $slmwoo_settings['expiry_second'] );
				update_option( 'slmforwoocommerce', $slmwoo_settings );
			}
		} else {
			$settings_tbl = array(
				'license_server_url'  => esc_url_raw( site_url() ),
				'secretkey'           => null,
			);
			update_option( 'slmforwoocommerce', $settings_tbl );
		}

	}

	/** ==================================================
	 * Notices
	 *
	 * @since 1.00
	 */
	public function notices() {

		if ( $this->is_my_plugin_screen() ) {
			if ( is_multisite() ) {
				$woo_install_url  = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce' );
			} else {
				$woo_install_url  = admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce' );
			}
			$woo_install_html  = '<a href="' . $woo_install_url . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">WooCommerce</a>';
			if ( ! class_exists( 'WooCommerce' ) ) {
				/* translators: %1$s: WooCommerce */
				echo '<div class="notice notice-warning is-dismissible"><ul><li>' . wp_kses_post( sprintf( __( 'Please use the %1$s.', 'software-license-manager-for-woocommerce' ), $woo_install_html ) ) . '</li></ul></div>';
			}
		}

	}

}


