<?php 
/**
 * Template Name: Product Table
 */
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" 
      rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" 
      crossorigin="anonymous">

<!-- DATATABLES -->      
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.0.1/css/buttons.dataTables.min.css">
<!-- Incluye DataTables Responsive desde un CDN (asegúrate de tener conexión a internet) -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.0.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.html5.min.js"></script>

<?php get_header(); ?>

<?php get_template_part( 'template-parts/breadcrumbs' ); ?>

<?php do_action( 'rhodes_before_main' ); ?>

<?php do_action( 'rhodes_before_entry', 'main', get_the_ID() ); ?>



<main class="main">

    <div class="container">

        <div class="row">

            <div id="site-content" class="col-12">
                <br>
                <p class="text-center">Tabla de precios actualizada a <?php echo date("d/m/Y");  ?></p>
                <p class="text-center">Los precios indicados no contienen el 10% de iva reducido</p>
                <br>

                <!-- Obtener los productos -->
                <?php 
                $args = array(
                    'status' => 'publish', // Obtener solo productos publicados
                    'limit' => -1, // Obtener todos los productos, usar -1 para obtener todos los productos
                    'return' => 'objects', // Obtener los objetos de producto en lugar de IDs
                    'stock_status' => 'instock', // Obtener productos con stock
                    'type' => array('simple', 'variable'), // Obtener productos simples y variables
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat', // Taxonomía de categorías de productos en WooCommerce
                            'field' => 'id',
                            'terms' => array(202, 188), // IDs de las categorías que deseas excluir
                            'operator' => 'NOT IN', // Operador para excluir las categorías
                        ),
                    ),

                );

                $products = wc_get_products($args);
                ?>  

                <!-- filtro de origenes -->
                <div class="col-10 offset-1">
                    <label for="origenFilter" class="form-label">Filtrar por Origen:</label>
                    <select id="origenFilter" class="form-select">
                            <option value="">Todos los Orígenes</option>
                            <?php
                            // Array para almacenar los términos únicos de 'pa_origen'
                            $origenes = array();
                            // Obtener términos únicos del atributo 'pa_origen'
                            foreach ($products as $product) {
                                $product_origenes = $product->get_attribute('pa_origen');
                                if (!empty($product_origenes)) {
                                    $origenes = array_merge($origenes, explode(', ', $product_origenes));
                                }
                            }
                            // Filtrar duplicados y mostrar opciones del select
                            $origenes = array_unique($origenes);
                            foreach ($origenes as $origen) {
                                echo '<option value="' . esc_attr($origen) . '">' . esc_html($origen) . '</option>';
                            }
                            ?>
                        </select>
                </div>
                <br><br>
                <table id="productTable" class="table table-striped table-bordered table-sm">

                    <thead class="thead-dark">
                        <tr class="text-center">
                            <th scope="col">Nombre</th>
                            <th scope="col">Medidas</th>
                            <th scope="col">Color</th>
                            <th scope="col">Origen</th>
                            <th scope="col">Tallos x Paquete</th>
                            <th scope="col">Precio x Tallo</th>
                            <th scope="col">Stock (nº de tallos)</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php 
                        foreach ($products as $product) 
                        {
                            // Si el producto es variable
                            if ($product->is_type('variable')) {
                                // Obtener los atributos de color y origen del producto principal
                                $pa_color = esc_html($product->get_attribute('pa_color'));
                                $pa_origen = esc_html($product->get_attribute('pa_origen'));

                                // Obtener las variaciones del producto
                                $variations = $product->get_available_variations();
                                // Iterar sobre las variaciones y mostrar cada variación como una fila
                                foreach ($variations as $variation) {
                                    // Obtener atributos de la variación
                                    $pa_medida = esc_html($variation['attributes']['attribute_pa_medida']);
                                    $price = wc_price($variation['display_price']);
                                    $stock_quantity = $variation['max_qty'];

                                    // Mostrar la fila de la variación con atributos del producto principal
                                    echo "<tr class='product-row' data-pa_origen='{$pa_origen}'>";
                                    echo '<td><a href="' . esc_url(get_permalink($product->get_id())) . '">' . esc_html($product->get_name()) . '</a></td>';
                                    echo '<td class="text-center">' . $pa_medida . '</td>';
                                    echo '<td class="text-center">' . $pa_color . '</td>';
                                    echo '<td class="text-center">' . $pa_origen . '</td>';
                                    echo '<td class="text-center">' . esc_html($product->get_meta('_qty_args')['qty_min']) . '</td>';
                                    if ( is_user_logged_in() ) {
                                        echo '<td class="text-center">' . $price . '</td>';
                                    } else {
                                        echo '<td class="text-center"><a href="' . esc_url( wp_login_url() ) . '">Iniciar sesión para ver los precios</a></td>';
                                    }
                                    
                                    echo '<td class="text-center">' . $stock_quantity . '</td>';
                                    echo '</tr>';
                                }
                            } else { // Si el producto no es variable, mostrar el producto simple
                                echo "<tr class='product-row' data-pa_origen='{$product->get_attribute("pa_origen")}'>";
                                echo '<td><a href="' . esc_url(get_permalink($product->get_id())) . '">' . esc_html($product->get_name()) . '</a></td>';
                                echo '<td class="text-center">' . esc_html($product->get_attribute('pa_medida')) . '</td>';
                                echo '<td class="text-center">' . esc_html($product->get_attribute('pa_color')) . '</td>';
                                echo '<td class="text-center">' . esc_html($product->get_attribute('pa_origen')) . '</td>';
                                $qty_args = $product->get_meta('_qty_args');
                                if (is_array($qty_args) && isset($qty_args['qty_min'])) {
                                    echo '<td class="text-center">' . esc_html($qty_args['qty_min']) . '</td>';
                                } else {
                                    // Imprime un valor predeterminado o deja la celda vacía si '_qty_args' no es un array o no tiene la clave 'qty_min'.
                                    echo '<td class="text-center"></td>';
                                }
                                                                /* Ocultar precios para clientes no logueados */
                                if ( is_user_logged_in() ) {
                                    echo '<td class="text-center">' . wc_price($product->get_price()) . '</td>';
                                } else {
                                    echo '<td class="text-center"><a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">Iniciar sesión para ver los precios</a></td>';                                }
                                echo '<td class="text-center">' . esc_html($product->get_stock_quantity()) . '</td>';
                                echo '</tr>';
                            }
                        }
                        ?>

    
                    </tbody>

                </table>

                <?php wp_reset_query(); ?>
            </div>
        </div>
    </div>
</main>

<?php do_action( 'rhodes_after_main' ); ?>




<style>

    

    #productTable_wrapper {
        width: 100%;
        overflow-x: auto;

    }
/* Color del borde superior */
  #productTable thead th {
    border-top: 1px solid #dee2e6; 
  }

  /* Estilos para alinear los botones de exportación a la derecha */
.top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}


</style>



<script>
    // Esperar a que se cargue el documento
    $(document).ready(function() {

        // Inicializar DataTables
        $('#productTable').DataTable({
            responsive: true,
            "paging": false,
            "info": false, // Deshabilitar la información de la tabla
            "searching": false, // Deshabilitar el formulario de búsqueda
            "buttons": [
        {
            extend: 'csv',
            text: 'Exportar a CSV',
            exportOptions: {
                rows: { 
                    search: 'applied',
                    order: 'applied' 
                }
            }
        },
        {
            extend: 'excel',
            text: 'Exportar a Excel',
            exportOptions: {
                rows: { 
                    search: 'applied',
                    order: 'applied' 
                }
            }
        },
        {
            extend: 'pdf',
            text: 'Exportar a PDF',
            exportOptions: {
                rows: { 
                    search: 'applied',
                    order: 'applied' 
                }
            }
        }
    ],

        "dom": '<"top"fB>rt<"bottom"lip><"clear">',
            
        // Exportar a documentos
        customize: function(doc) {
            // Iterar sobre las filas de la tabla
            doc.content[1].table.body.forEach(function(row, index) {
                // Obtener el precio de la celda actual y dividirlos en líneas
                var priceCell = row[5].split('<br>');
                // Reemplazar la celda del precio con líneas separadas
                doc.content[1].table.body[index][5] = priceCell.join('\n'); // Unir las líneas con un salto de línea para el PDF
            });
        }


        });

        // Configurar el filtro personalizado por Origen (Select)
        $('#origenFilter').on('change', function() {
            var selectedOrigin = this.value;
            var rows = $('.product-row');

            // Ocultar todas las filas y mostrar solo las que coinciden con el origen seleccionado
            rows.each(function() {
                var productOrigen = $(this).data('pa_origen'); // Obtener el origen del atributo de datos
                if (selectedOrigin === '' || productOrigen === selectedOrigin) {
                    $(this).show(); // Mostrar la fila
                } else {
                    $(this).hide(); // Ocultar la fila
                }
            });
        });



        // Configurar el filtro personalizado por Origen (Select)
        $('#origenFilter').on('change', function() {
            var selectedOrigin = this.value;
            var rows = $('.product-row');

            // Ocultar todas las filas y mostrar solo las que coinciden con el origen seleccionado
            rows.each(function() {
                var productOrigen = $(this).data('pa_origen'); // Obtener el origen del atributo de datos
                if (selectedOrigin === '' || productOrigen === selectedOrigin) {
                    $(this).show(); // Mostrar la fila
                } else {
                    $(this).hide(); // Ocultar la fila
                }
            });
        });

  });
</script>



<?php
get_footer();

 