<?php
/**
 * The Base Widget.
 *
 * @package MASElementor/Modules/Woocommerce/Widgets
 */

namespace MASElementor\Modules\Woocommerce\Widgets;

use Elementor\Controls_Manager;
use Elementor\Controls_Stack;
use Elementor\Core\Breakpoints\Manager as Breakpoints_Manager;
use MASElementor\Modules\Woocommerce\Classes\Products_Renderer;
use MASElementor\Modules\Woocommerce\Traits\Product_Id_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract Base Widget class
 */
abstract class Base_Widget extends \MASElementor\Base\Base_Widget {

	use Product_Id_Trait;

	/**
	 * Displayed_ids
	 *
	 * @var array
	 */
	protected $gettext_modifications;

	/**
	 * Get the categories for the widget.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'woocommerce-elements-single' );
	}

	/**
	 * Get the devices default.
	 *
	 * @return array
	 */
	protected function get_devices_default_args() {
		$devices_required = array();

		// Make sure device settings can inherit from larger screen sizes' breakpoint settings.
		foreach ( Breakpoints_Manager::get_default_config() as $breakpoint_name => $breakpoint_config ) {
			$devices_required[ $breakpoint_name ] = array(
				'required' => false,
			);
		}

		return $devices_required;
	}

	/**
	 * Add column responsive control.
	 *
	 * @return void
	 */
	protected function add_columns_responsive_control() {
		$this->add_responsive_control(
			'columns',
			array(
				'label'               => esc_html__( 'Columns', 'mas-addons-for-elementor' ),
				'type'                => Controls_Manager::NUMBER,
				'prefix_class'        => 'mas-grid%s-',
				'min'                 => 1,
				'max'                 => 12,
				'default'             => Products_Renderer::DEFAULT_COLUMNS_AND_ROWS,
				'tablet_default'      => '3',
				'mobile_default'      => '2',
				'required'            => true,
				'device_args'         => $this->get_devices_default_args(),
				'min_affected_device' => array(
					Controls_Stack::RESPONSIVE_DESKTOP => Controls_Stack::RESPONSIVE_TABLET,
					Controls_Stack::RESPONSIVE_TABLET  => Controls_Stack::RESPONSIVE_TABLET,
				),
			)
		);
	}

	/**
	 * Is WooCommerce Feature Active.
	 *
	 * Checks whether a specific WooCommerce feature is active. These checks can sometimes look at multiple WooCommerce
	 * settings at once so this simplifies and centralizes the checking.
	 *
	 * @since 3.5.0
	 *
	 * @param string $feature feature.
	 * @return bool
	 */
	protected function is_wc_feature_active( $feature ) {
		switch ( $feature ) {
			case 'checkout_login_reminder':
				return 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' );
			case 'shipping':
				if ( class_exists( 'WC_Shipping_Zones' ) ) {
					$all_zones = \WC_Shipping_Zones::get_zones();
					if ( count( $all_zones ) > 0 ) {
						return true;
					}
				}
				break;
			case 'coupons':
				return function_exists( 'wc_coupons_enabled' ) && wc_coupons_enabled();
			case 'signup_and_login_from_checkout':
				return 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' );
			case 'ship_to_billing_address_only':
				return wc_ship_to_billing_address_only();
		}

		return false;
	}

	/**
	 * Get Custom Border Type Options
	 *
	 * Return a set of border options to be used in different WooCommerce widgets.
	 *
	 * This will be used in cases where the Group Border Control could not be used.
	 *
	 * @since 3.5.0
	 *
	 * @return array
	 */
	public static function get_custom_border_type_options() {
		return array(
			'none'   => esc_html__( 'None', 'mas-addons-for-elementor' ),
			'solid'  => esc_html__( 'Solid', 'mas-addons-for-elementor' ),
			'double' => esc_html__( 'Double', 'mas-addons-for-elementor' ),
			'dotted' => esc_html__( 'Dotted', 'mas-addons-for-elementor' ),
			'dashed' => esc_html__( 'Dashed', 'mas-addons-for-elementor' ),
			'groove' => esc_html__( 'Groove', 'mas-addons-for-elementor' ),
		);
	}

	/**
	 * Init Gettext Modifications
	 *
	 * Should be overridden by a method in the Widget class.
	 *
	 * @since 3.5.0
	 */
	protected function init_gettext_modifications() {
		$this->gettext_modifications = array();
	}

	/**
	 * Filter Gettext.
	 *
	 * Filter runs when text is output to the page using the translation functions (`_e()`, `__()`, etc.)
	 * used to apply text changes from the widget settings.
	 *
	 * This allows us to make text changes without having to ovveride WooCommerce templates, which would
	 * lead to dev tax to keep all the templates up to date with each future WC release.
	 *
	 * @since 3.5.0
	 *
	 * @param string $translation translation.
	 * @param string $text text.
	 * @param string $domain text domain.
	 * @return string
	 */
	public function filter_gettext( $translation, $text, $domain ) {
		if ( 'woocommerce' !== $domain ) {
			return $translation;
		}

		if ( ! isset( $this->gettext_modifications ) ) {
			$this->init_gettext_modifications();
		}

		return array_key_exists( $text, $this->gettext_modifications ) ? $this->gettext_modifications[ $text ] : $translation;
	}
}
