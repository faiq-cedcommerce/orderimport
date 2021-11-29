<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.cedcommerce.com
 * @since      1.0.0
 *
 * @package    Woo_Orderimport
 * @subpackage Woo_Orderimport/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Orderimport
 * @subpackage Woo_Orderimport/admin
 * @author     Faiq Masood <https://www.cedcommerce.com>
 */
class Woo_Orderimport_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Orderimport_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Orderimport_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-orderimport-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Orderimport_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Orderimport_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-orderimport-admin.js', array( 'jquery' ), $this->version, false );

	}
	// Creating the admin menu
	public function orderimport_admin_menu() {
		add_menu_page( 'Order Importer(order.json)', 'Order Importer (order.json)', 'manage_options', 'ced_json_orderimport', array( $this, 'ced_json_order_import'), 'dashicons-admin-generic', 7  );
	}

	public function ced_json_order_import(){
		?>
		<form action="" method="post" enctype="multipart/form-data">
			<input type="file" name="fileToUpload" id="fileToUpload">
			<input type="submit" value="Upload File" name="submittheform">
		</form>
	
		<?php	
			global $wp_filesystem;
			WP_Filesystem();
			$content_directory = $wp_filesystem->wp_content_dir() . 'uploads/';
			$wp_filesystem->mkdir( $content_directory . 'JSONCustomDirectory' );
			$target_dir_location = $content_directory . 'JSONCustomDirectory/';
	
			if(isset($_POST["submittheform"]) && isset($_FILES['fileToUpload'])) {
			
				$name_file = $_FILES['fileToUpload']['name'];
				$tmp_name = $_FILES['fileToUpload']['tmp_name'];
			
				if( move_uploaded_file( $tmp_name, $target_dir_location.$name_file ) ) {
					echo $target_dir_location.$name_file;
					echo "File was successfully uploaded";
				} else {
					echo "The file was not uploaded";
				}
			
			}

			define( 'FILE_TO_IMPORT', $target_dir_location.$name_file );
						
			if ( ! file_exists( FILE_TO_IMPORT ) ) :
				die( 'Unable to find ' . FILE_TO_IMPORT );
			endif;	
			
			$content 			= file_get_contents(FILE_TO_IMPORT);
			$content_decoded	= json_decode($content,true);
						
			$objProduct = new WC_Product();
			$objProduct->set_name($content_decoded['OrderArray']['Order'][0]['TransactionArray']['Transaction'][0]['Item']['Title']);
			$objProduct->set_status("publish"); 
			$objProduct->set_catalog_visibility('visible'); 
			$objProduct->set_description('This is the description');
			$objProduct->set_sku($content_decoded['OrderArray']['Order'][0]['TransactionArray']['Transaction'][0]['Item']['SKU']);
			$objProduct->set_manage_stock(true);
			$objProduct->set_stock_quantity(1);
			$objProduct->set_stock_status('instock');
			$objProduct->set_backorders('no');
			$objProduct->set_reviews_allowed(true);
			$objProduct->set_sold_individually(false);			
			$objProduct->set_regular_price($content_decoded['OrderArray']['Order'][0]['TransactionArray']['Transaction'][0]['TransactionPrice']['value']); 
			$objProduct->set_price($content_decoded['OrderArray']['Order'][0]['TransactionArray']['Transaction'][0]['TransactionPrice']['value']);
			$product_id 	= $objProduct->save();

			global $woocommerce;
			$address = array(
				'first_name' => $content_decoded['OrderArray']['Order'][0]['TransactionArray']['Transaction'][0]['Buyer']['UserFirstName'],
				'last_name'  => $content_decoded['OrderArray']['Order'][0]['TransactionArray']['Transaction'][0]['Buyer']['UserLastName'],
				'company'    => '',
				'email'      => $content_decoded['OrderArray']['Order'][0]['TransactionArray']['Transaction'][0]['Buyer']['Email'],
				'phone'      => $content_decoded['OrderArray']['Order'][0]['ShippingAddress']['Phone'],
				'address_1'  => $content_decoded['OrderArray']['Order'][0]['ShippingAddress']['Street1'],
				'address_2'  => $content_decoded['OrderArray']['Order'][0]['ShippingAddress']['Street2'],
				'city'       => $content_decoded['OrderArray']['Order'][0]['ShippingAddress']['CityName'],
				'state'      => $content_decoded['OrderArray']['Order'][0]['ShippingAddress']['StateOrProvince'],
				'postcode'   => $content_decoded['OrderArray']['Order'][0]['ShippingAddress']['PostalCode'],
				'country'    => $content_decoded['OrderArray']['Order'][0]['ShippingAddress']['CountryName']
			);

			$order = wc_create_order();
			$order->add_product( get_product( $product_id ), 1 ); 
			$order->set_address( $address, 'billing' );
			$order->set_address( $address, 'shipping' );
			$order->set_currency($content_decoded['OrderArray']['Order'][0]['AmountPaid']['currencyID']);
			$country_code = $order->get_shipping_country();

			// Set the array for tax calculations
			$calculate_tax_for = array(
				'country' => $country_code, 
				'state' => '', 
				'postcode' => '', 
				'city' => ''
			);

			
			$item_fee = new WC_Order_Item_Fee();

			$item_fee->set_name( "Tax" ); 
			$item_fee->set_amount( $content_decoded['OrderArray']['Order'][0]['TransactionArray']['Transaction'][0]['Taxes']['TaxDetails'][0]['TaxAmount']['value'] ); 
			$item_fee->set_tax_class( '' ); 
			$item_fee->set_tax_status( 'taxable' ); 
			$item_fee->set_total( $content_decoded['OrderArray']['Order'][0]['TransactionArray']['Transaction'][0]['Taxes']['TaxDetails'][0]['TaxOnSubtotalAmount']['value'] );		
			$item_fee->calculate_taxes( $calculate_tax_for );			
			$order->add_item( $item_fee );
			$ship_rate = new WC_Shipping_Rate();
			$ship_rate->id=0;
			$ship_rate->label=$content_decoded['OrderArray']['Order'][0]['ShippingServiceSelected']['ShippingService'];
			$ship_rate->taxes=array(); 
			$ship_rate->cost=$content_decoded['OrderArray']['Order'][0]['ShippingServiceSelected']['ShippingService']['ShippingServiceCost']['value']; 
			$order->add_shipping($ship_rate); 

			$order->calculate_totals();
			$order->update_status("completed");
		}	
}
