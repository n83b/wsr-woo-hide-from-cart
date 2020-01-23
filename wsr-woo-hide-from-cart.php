<?php

/*
Plugin Name: WSR Woo Hide from cart
Plugin URI: http://websector.com.au
Description: Hide price and add to cart buttons
Version: 1.0.0
Author: WSR
Author URI: http://websector.com.au
License: A short license name. Example: GPL2
*/

namespace WSR\WSRWooHideFromCart;

defined('ABSPATH') or die('No script kiddies please!');

// Start up the engine
if (!class_exists('WSRWooHideFromCart')) {

    class WSRWooHideFromCart
    {
        public static $instance = false;

        private function __construct()
        {
            //Hide Add to cart button
            add_action('add_meta_boxes', array( $this, 'addCustomBox'));
            add_action('save_post', array( $this, 'wooHidecartChkSave'));
            add_filter('woocommerce_is_purchasable', array($this, 'isPurchasable'), 10, 2);
            add_filter('woocommerce_get_price_html', array($this, 'customPriceMessage'));
            add_action('manage_product_posts_columns', array($this, 'wooCustomAdminColumn'), 10, 1); //add custom column
            add_action('manage_product_posts_custom_column', array( $this, 'manageCustomAdminColumns'), 10, 2); //populate colum
            n
            //TODO:  Quick Edits
            //https://www.sitepoint.com/extend-the-quick-edit-actions-in-the-wordpress-dashboard/
            //add_action('quick_edit_custom_box', array( $this, 'displayQuickEditCustom'), 10, 2); //output form elements for quickedit interface

            //Free price text if $0
            add_filter('woocommerce_get_price_html', array( $this, 'customFreePriceText'));
            add_filter('woocommerce_is_purchasable', array( $this, 'removeAddToCartOn0'), 10, 2);
            //add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'purchasable_variation_date_range'), 20, 2 );
        }

        /**
         * If an instance exists, this returns it.  If not, it creates one
         */
        public static function getInstance()
        {
            if (!self::$instance) {
                self::$instance = new self();
            }
                
            return self::$instance;
        }

        /**
         * Hide Price if zero
         */
        public function customFreePriceText($pPrice)
        {
            global $product;
            $price = "0";

            //this is used to stop a bug with atelier theme where it tries to loop through products
            if (!$product instanceof WC_Product_Simple && !$product instanceof WC_Product_Variable) {
                return $pPrice;
            }

            $price = $product->get_price();

            if ($price == "0.00" || $price == "0" || $price == 0) {
                return '<div class="divCallForPrice">Call for price & availability.<br><a href="tel:0871300148">(08) 7130 0148</a></div>';
            } else {
                return $pPrice;
            }
        }

        /**
        * Remove add to cart
        */
        public function removeAddToCartOn0($purchasable, $product)
        {
            $price = $product->get_price();
            if ($price == "0.00" || $price == "0" || $price == 0) {
                $purchasable = false;
                remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 30);
                remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
                add_filter('woocommerce_loop_add_to_cart_link', array( $this, 'loopAddToCartLink'), 30);
            }
            return $purchasable;
        }

        public function loopAddToCartLink($quantity)
        {
            global $product;
            return '<a rel="nofollow" href="' . get_permalink($product->get_id()) . '" data-product_id="' . $product->get_id() . '" class="button product_type_simple">Read more</a>';
        }

        /**
        * Hide add to cart Checkbox
        */
        public function addCustomBox()
        {
            $screens = ['product'];
            foreach ($screens as $screen) {
                add_meta_box(
                    'wsr_woo_hidecart_chk_id',
                    'Hide from cart',
                    array( $this, 'wooHidecartChkHtml'),
                    $screen;
                );
            }
        }

        //Display checkbox html
        public function wooHidecartChkHtml($post)
        {
            $value = get_post_meta($post->ID, '_wsr_woo_hidecart_key', true);
            ?>
            <input type="checkbox" name="wsr_woo_hidecart" value="true" <?php echo ($value) ? 'checked' : '' ?> > Hide price and remove from cart
            <?php
        }

        /**
        *Save checkbox value
        **/
        public function wooHidecartChkSave($post_id)
        {
            if (array_key_exists('wsr_woo_hidecart', $_POST)) {
                update_post_meta(
                    $post_id,
                    '_wsr_woo_hidecart_key',
                    $_POST['wsr_woo_hidecart']
                );
            } else {
                delete_post_meta($post_id, '_wsr_woo_hidecart_key');
            }
        }

        /**
         * if checkbox, then make product not purchaseable
         */
        public function isPurchasable($purchasable, $product)
        {
            $cartHidden = get_post_meta($product->get_id(), '_wsr_woo_hidecart_key');
            // var_dump($cartHidden);
            if ($cartHidden) {
                $purchasable = false;
            }
            return $purchasable;
        }

        /*
        * if checkbox, then output differnt message
        */
        public function customPriceMessage($price)
        {
            global $post;

            if (get_post_meta($post->ID, '_wsr_woo_hidecart_key')) {
                return $price . '<br /><div class="divCallForPrice">To order or for more information call: <br><a href="tel:0871300148">(08) 7130 0148</a></div>';
            } else {
                return $price;
            }
        }

        public function wooCustomAdminColumn($columns)
        {
            $new_columns = array();
            $new_columns['hide_price'] = 'Hide Price';
            return array_merge($columns, $new_columns);
        }

        public function manageCustomAdminColumns($column_name, $post_id)
        {
            $html = '';
            if ($column_name == 'hide_price') {
                if (get_post_meta($post_id, '_wsr_woo_hidecart_key')) {
                    $html .= '<div id="wsr_woo_hidecart_' . $post_id . '">';
                    $html .= '&#10003;';
                    $html .= '</div>';
                }
            }
            echo $html;
        }

        public function displayQuickEditCustom($column)
        {
            $html = '';
            //output post featured checkbox
            if ($column == 'hide_price') {
                $html .= '<fieldset class="inline-edit-col-left clear">';
                    $html .= '<div class="inline-edit-group wp-clearfix">';
                        $html .= '<input type="checkbox" name="wsr_woo_hidecart" value="true"> Hide price and remove from cart';
                    $html .= '</div>';
                $html .= '</fieldset>';
            }
            echo $html;
        }

        /**
         *  Output to error log in current plugin directory
         */
        public function errorLog($msg)
        {
            $timezone_string = get_option('timezone_string');
            date_default_timezone_set($timezone_string);

            $log = "[" . date("Y-m-d g:ia") . "] " . $msg . "\n";
            errorLog($log, 3, plugin_dir_path(__FILE__) . '/debug.log');
        }
    }
}

// Instantiate our class
$WSRWooHideFromCart = WSRWooHideFromCart::getInstance();
