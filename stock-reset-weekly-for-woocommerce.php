<?php
/*
Plugin Name: Stock Reset Weekly for WooCommerce
Plugin URI: https://www.linkedin.com/in/enyor/
Description: Plugin que actualiza el stock de los productos de Woocommerce semanalmente
Version: 1.0
Author: Enyor Pina
Author URI: https://www.linkedin.com/in/enyor/
*/

// Función que se ejecutará diariamente
// Evita que se acceda directamente a este archivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Añade el campo personalizado "auto stock" a la ficha de producto.
function add_auto_stock_field() {
    woocommerce_wp_text_input(
        array(
            'id'          => 'auto_stock',
            'label'       => __( 'Auto Stock', 'woocommerce' ),
            'placeholder' => '',
            'description' => __( 'Introduce el valor del stock para actualizar cada sábado a las 00:00 hrs.', 'woocommerce' ),
        )
    );
}
add_action( 'woocommerce_product_options_inventory_product_data', 'add_auto_stock_field' );

// Guarda el valor del campo personalizado "auto stock" cuando se guarda el producto.
function save_auto_stock_field( $post_id ) {
    $auto_stock = isset( $_POST['auto_stock'] ) ? $_POST['auto_stock'] : '';
    update_post_meta( $post_id, 'auto_stock', $auto_stock );
}
add_action( 'woocommerce_process_product_meta', 'save_auto_stock_field' );

// Función que actualiza el stock de los productos.
function stock_reset_weekly_for_woocommerce() {
    // Obtén todos los productos de WooCommerce.
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
    );
    $products = new WP_Query( $args );
    while ( $products->have_posts() ) : $products->the_post();
        $product = wc_get_product( get_the_ID() );
        // Obtenemos el valor del campo personalizado "auto stock".
        $auto_stock = get_post_meta( get_the_ID(), 'auto_stock', true );
        if ( $auto_stock !== '' ) {
            // Actualizamos el stock del producto con el valor del campo personalizado.
            $product->set_stock_quantity( $auto_stock );
            $product->set_stock_status('instock');
            $product->save();
        }
    endwhile;
    wp_reset_postdata();
}
// Programamos la tarea de actualización diaria a las 00:00 hrs.
register_activation_hook( __FILE__, 'stock_reset_weekly_for_woocommerce_schedule' );
function stock_reset_weekly_for_woocommerce_schedule() {
    // Programar evento para todos los sábados a las 00:00hrs
    wp_schedule_event( strtotime( 'next saturday 00:00' ), 'weekly', 'stock_reset_weekly_for_woocommerce' );
}

register_deactivation_hook( __FILE__, 'desactivar_programacion_stock' );
function desactivar_programacion_stock() {
    wp_clear_scheduled_hook( 'stock_reset_weekly_for_woocommerce' );
}

function stock_reset_weekly_for_woocommerce_settings_page() {
    if ( isset( $_POST['stock_reset_weekly_for_woocommerce_update'] ) ) {
        stock_reset_weekly_for_woocommerce();
        echo '<div class="updated"><p>' . __( 'Se ha actualizado el stock de los productos.', 'stock-reset-weekly-for-woocommerce' ) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="">
            <p class="submit">
                <input type="submit" name="stock_reset_weekly_for_woocommerce_update" class="button-primary" value="<?php esc_attr_e( 'Actualizar Stock', 'stock-reset-weekly-for-woocommerce' ); ?>" />
            </p>
        </form>
    </div>
    <?php
}
// Agrega la página de ajustes del plugin al menú de WordPress.
function stock_reset_weekly_for_woocommerce_admin_menu() {
    add_options_page(
        __( 'Stock Reset weekly for WooCommerce', 'stock-reset-weekly-for-woocommerce' ),
        __( 'Stock Reset', 'stock-reset-weekly-for-woocommerce' ),
        'manage_options',
        'stock-reset-weekly-for-woocommerce',
        'stock_reset_weekly_for_woocommerce_settings_page'
    );
}
add_action( 'admin_menu', 'stock_reset_weekly_for_woocommerce_admin_menu' );
