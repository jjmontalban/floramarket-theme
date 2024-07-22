<?php 


function sf_child_theme_dequeue_style() {
    wp_dequeue_style( 'storefront-style' );
    wp_dequeue_style( 'storefront-woocommerce-style' );
}
add_action( 'wp_enqueue_scripts', 'sf_child_theme_dequeue_style', 20 );

/**
 * @snippet       registrar sidebar para la pagina de categorias
 */
function fmk_category_sidebar() {
    register_sidebar(
        array (
            'name' => __( 'Category Sidebar', 'fmk' ),
            'id' => 'fmk-cat-bar',
            'description' => __( 'Category Sidebar', 'fmk' ),
            'before_widget' => '<div class="widget-content">',
            'after_widget' => "</div>",
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        )
    );
}
add_action( 'widgets_init', 'fmk_category_sidebar' );



/**
 * @snippet Registrar un nuevo menú de navegación para la topbar
 */
function register_topbar_menu() {
    register_nav_menu('topbar-menu', __('Topbar Menu', 'storefront-child'));
}
add_action('after_setup_theme', 'register_topbar_menu');

function display_topbar_menu() {
    if (has_nav_menu('topbar-menu')) {
        wp_nav_menu(array(
            'theme_location' => 'topbar-menu',
            'container'      => 'nav',
            'container_id'   => 'topbar-navigation',
            'container_class'=> 'topbar-navigation',
            'menu_id'        => 'topbar-menu',
            'menu_class'     => 'topbar-menu',
            'depth'          => 1,
        ));
    }
}




/**
 * @snippet       Añadir datos al grid en la pagina de categorias (woocommerce_after_shop_loop_item_title)
 * @snippet       Y en la pagina de producto (woocommerce_single_product_summary)
 */
function mostrar_atributos_disponibles() 
{
    global $product;
    
    $product_attributes = array( 'pa_color', 'pa_medida', 'pa_peso', 'pa_origen', 'pa_boton', 'pa_finca' );
    $attr_output = array();
    //Obtener los tallos por paquete
    $tallos  = $product->get_meta( '_qty_args' );
    // Obtener la cantidad de stock
    $stock_quantity = $product->get_stock_quantity();
        
    /* if ( $tallos['qty_min'] > 1 && !empty( $tallos ) && isset( $tallos['qty_min']) && $product->get_id() != 5297 && $product->get_id() != 6406 && $product->get_id() != 6405 && $product->get_id() != 6401  )   { */
    if (is_array($tallos) && isset($tallos['qty_min']) && $tallos['qty_min'] > 1) {
        $attr_output[] = '<span class="tallos-paquete"><strong>Tallos por paquete: </strong>'. $tallos['qty_min'] .'</span>';
    } 
    //para productos que se venden por unidad mostrar la descricion corta
    else{
        //Obtenemos la información del producto
	    $product_details = $product->get_data();
	    $short_description = $product_details['short_description'];
        //limpieza
	    $short_description = strip_shortcodes($short_description);
	    $short_description = wp_strip_all_tags($short_description);
        $attr_output[] = '<span class="description">' . $short_description . '</span>';
    }

    //Mostrar la cantidad de stock solo para productos con seguimiento de stock
    if( $stock_quantity != null ) {
    $attr_output[] = '<span class="product-stock-quantity"><strong>Cantidad disponible:</strong> ' . $stock_quantity . '</span>';
    }
    // Loop de atributos
    foreach( $product_attributes as $attribute ) 
    {
        if( taxonomy_exists( $attribute ) ) {
            $label_name = get_taxonomy( $attribute )->labels->singular_name;
            $value = $product->get_attribute($attribute);

            if( !empty($value) ){
                // Guardar el attributo para mostrarlo
                $attr_output[] = '<span class="'.$attribute.'"><strong>'.$label_name.'</strong>: '.$value.'</span>';
            }
        }
    }
        
    /* } */

    //Caso especial de productos variables preservados. para mostrar la medida
    if( $product->is_type('variable') && has_term( 'preservadas', 'product_cat' ) ) {
        
        // Loop through the array of simple product attributes
        if( taxonomy_exists( 'pa_medida' ) ) {
            $label_name = get_taxonomy( 'pa_medida' )->labels->singular_name;
            $value = $product->get_attribute( 'pa_medida' );
            
            if( !empty( $value ) ) {
                // Storing attributes for output
                $attr_output[] = '<span class="pa_medida"><strong>'.$label_name.'</strong>: '.$value.'</span>';
            }
        }
    }
    
    // Output attribute name / value pairs separate by a "<br>"
    echo '<div class="product-attributes">'.implode( '<br>', $attr_output ).'</div>';
}
add_action('woocommerce_after_shop_loop_item_title','mostrar_atributos_disponibles');
add_action( 'woocommerce_single_product_summary', 'mostrar_atributos_disponibles', 15 ); 


/**
 * @snippet       Mostrar atributos del producto en el email
 */
function custom_item_meta($item_id, $item, $order, $plain_text)
{
    $product_id = $item->get_product_id();
    $product = wc_get_product( $product_id );
    
    if ( $product->is_type('simple') ) 
    {
        $color = $product->get_attribute( 'pa_color' );     
        $medida = $product->get_attribute( 'pa_medida' );
        if ($color) {
            $label_color = get_taxonomy( 'pa_color' )->labels->singular_name;
            echo  "<br><p> $label_color : $color</p>";
        }
        if ($medida) {
            $label_medida = get_taxonomy( 'pa_medida' )->labels->singular_name;
            echo  "<br><p> $label_medida : $medida</p>";
        }
    }
}
add_action('woocommerce_order_item_meta_end', 'custom_item_meta', 10, 4);


/**
 * @snippet       Deshabilitar provincias 
 */
function eliminar_provincias( $provincias ) 
{
    unset($provincias['ES']['TF']);
    unset($provincias['ES']['GC']);
    unset($provincias['ES']['CE']);
    unset($provincias['ES']['ML']);
    
    return $provincias;
}
add_filter('woocommerce_states', 'eliminar_provincias');



/**
 * @snippet  Añadir al carrito rapido eligiendo cantidades
 */
function fmk_add_cart_cantidades_loop( $html, $product, $args ) 
{
    //condicion original que solo muestra el input en productos simples
    if ( $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' ) ) {
        $max_quantity = $product->get_stock_quantity(); // Obtener la cantidad disponible en el stock
        $html = '<div>' . woocommerce_quantity_input( array('max_value' => $max_quantity), $product, false ) . '</div>' . $html;
    }
    
    return $html;
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'fmk_add_cart_cantidades_loop', 9999, 3 );

function fmk_add_cart_cantidades() 
{
    wc_enqueue_js( 
        "$(document).on('change','.quantity .qty',function(){
            var max_quantity = parseInt($(this).closest('li.product').find('.qty').attr('max')); // Obtener la cantidad disponible
            var selected_quantity = parseInt($(this).val());

            if (selected_quantity > max_quantity) {
                alert('La cantidad seleccionada excede la disponibilidad del stock.');
                $(this).val(max_quantity); // Establecer la cantidad máxima disponible
            }

            $(this).closest('li.product').find('a.ajax_add_to_cart').attr('data-quantity',$(this).val());
        });" 
    );
}
add_action( 'woocommerce_after_shop_loop', 'fmk_add_cart_cantidades' );


/**
 * @snippet       redireccion tras registro 
 */
function custom_registration_redirect( $redirect ) {
    return wc_get_page_permalink( 'shop' ); // Redirige a la página de la tienda
}
add_filter( 'woocommerce_registration_redirect', 'custom_registration_redirect', 10, 1 );


/**
 * @snippet       Redireccion de usuarios logueados a la tienda
 */
function fmk_redirect_login_registration_if_logged_in() {
    if ( is_page() && is_user_logged_in() && ( has_shortcode( get_the_content(), 'wc_login_form_fmk' ) || has_shortcode( get_the_content(), 'wc_reg_form_fmk' ) ) ) {
        wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
        exit;
    }
}
add_action( 'template_redirect', 'fmk_redirect_login_registration_if_logged_in' ); 


/**
 * @snippet       Traducir cualquier texto en WordPress
 */
function traducir_cualquier_texto( $translated, $original, $domain ) {

    // Depuración: Ver qué texto se está traduciendo
    error_log( 'Original: ' . $original . ' | Traducido: ' . $translated . ' | Dominio: ' . $domain );

    // Comparaciones para traducir los textos
    if ( $translated === "Delivery details" ) {
        $translated = "Detalles de entrega";
    }
    if ( $translated === "Nombre de usuario o correo electrónico" ) {
        $translated = "Correo electrónico";
    }
    
    return $translated;
}
add_filter( 'gettext', 'traducir_cualquier_texto', 10, 3 );




/**
 * @snippet       Enviar email al admin por cada registro de cliente
 * @author        https://docs.woocommerce.com/document/notify-admin-new-account-created/
 */
function woocommerce_created_customer_admin_notification( $customer_id ) {
    // Enviar notificación al administrador
    wp_send_new_user_notifications( $customer_id, 'admin' );

    // Obtener los datos del usuario
    $user_data = get_userdata( $customer_id );

    // Obtener el nombre de usuario, la dirección de correo electrónico y el número de teléfono del usuario
    $email = $user_data->user_email;
    $phone = get_user_meta( $customer_id, 'billing_phone', true );

    // Crear el asunto del correo electrónico
    $subject = 'Nuevo registro de cliente en Floramarket';

    // Crear el cuerpo del correo electrónico
    $message = "Un nuevo cliente se ha registrado en Floramarket.es.\n\n";
    $message .= "Correo electrónico: $email\n";
    $message .= "Número de teléfono: $phone\n";
    // Añadir el enlace a la página de edición del usuario
    $edit_user_link = admin_url('user-edit.php?user_id=' . $customer_id);
    $message .= "Puedes ver los detalles del cliente aquí: $edit_user_link";
    
    // Enviar el correo electrónico al usuario
    wp_mail( 'comercial@floramarket.es', $subject, $message );
}
add_action( 'woocommerce_created_customer', 'woocommerce_created_customer_admin_notification' );



/**
 * @snippet       Ocultar Seccion en Mi Cuenta
 */
function hideSectionProfile( $items ) {
    unset($items['downloads']);
	unset($items['dashboard']);
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'hideSectionProfile', 999 );


/**
 * @snippet       Añadir confirmación de contraseña en el registro woocommerce
 */

//1. Verifica que la opción de generar la contraseña automáticamente no esté activada en la configuración de WooCommerce
function wc_register_form_password_validation() {
    if ( get_option( 'woocommerce_registration_generate_password' ) == 'no' ) {
	?>
	<p class="form-row form-row-wide">
		<label for="reg_password2"><?php _e( 'Repite la contraseña', 'woocommerce' ); ?> <span class="required">*</span></label>
		<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" class="input-text" name="password2" id="reg_password2" autocomplete="current-password" />
	</p>
	<?php
    }
}
add_action( 'woocommerce_register_form', 'wc_register_form_password_validation' );

//2. Valida las contraseñas y define el mensaje de error de validación en la página de registro
function register_password_validation($reg_errors, $sanitized_user_login, $user_email) {
	global $woocommerce;
	extract( $_POST );
	if ( strcmp( $password, $password2 ) !== 0 ) {
		return new WP_Error( 'registration-error', __( 'Las dos contraseñas no coinciden.', 'woocommerce' ) );
	}
	return $reg_errors;
}
add_filter('woocommerce_registration_errors', 'register_password_validation', 10,3);


/**
 * @snippet       Elimina obligatoriedad de contraseña fuerte 
 */
add_action ('wp_print_scripts', function () {
	if (wp_script_is ('wc-password-strength-meter', 'enqueued'))
		wp_dequeue_script ('wc-password-strength-meter');
}, 100);


/**
 * @snippet       Ordenar productos por nombre en la tienda
 */
function custom_catalog_order($args) {
    $args['orderby'] = 'title';
    $args['order'] = 'ASC';
    return $args;
}
add_filter('woocommerce_get_catalog_ordering_args', 'custom_catalog_order');


/**
 * @snippet Ocultar precios a no registrados
 */
function ocultar_precio_a_no_registrados($price, $product) {
    if (is_user_logged_in()) {
        return $price;
    } else {
        return '<a href="' . get_permalink(woocommerce_get_page_id('myaccount')) . '">Regístrate para ver los precios</a>';
    }
}
add_filter('woocommerce_get_price_html', 'ocultar_precio_a_no_registrados', 10, 2);


/**
 * @snippet Ocultar precios a no registrados en pagina de producto
 */
function ocultar_boton_a_no_registrados($purchasable, $product){
    if($purchasable && !is_user_logged_in()){
        return false;
    }
    return $purchasable;
}
add_filter('woocommerce_is_purchasable', 'ocultar_boton_a_no_registrados', 10, 2);


/**
 * @snippet Ocultar precios a no registrados en pagina de categorias
 */
function ocultar_boton_en_categorias_a_no_registrados($link){
    if(!is_user_logged_in()){
        $link = '<a href="' . get_permalink(woocommerce_get_page_id('myaccount')) . '">Regístrate para comprar</a>';
    }
    return $link;
}
add_filter('woocommerce_loop_add_to_cart_link', 'ocultar_boton_en_categorias_a_no_registrados');


add_filter('storefront_menu_items', 'add_loginout_link', 10, 2);
function add_loginout_link($items, $args) {
    if ($args->theme_location == 'primary') {
        if (is_user_logged_in()) {
            $items .= '<li class="menu-item"><a href="'. wp_logout_url() .'"><i class="fa fa-user"></i></a></li>';
        } else {
            $items .= '<li class="menu-item"><a href="'. wp_login_url() .'"><i class="fa fa-user"></i></a></li>';
        }
    }
    return $items;
}



/**
 * @snippet Función para eliminar el crédito del footer predeterminado
 */
function remove_storefront_footer_credit() {
    remove_action('storefront_footer', 'storefront_credit', 20);
}
add_action('init', 'remove_storefront_footer_credit');

// Función para agregar un nuevo contenido al footer
function custom_storefront_footer_credit() {
    ?>
    <div class="site-info">
        © Floramarket 2024
    </div>
    <?php
}
add_action('storefront_footer', 'custom_storefront_footer_credit', 20);
