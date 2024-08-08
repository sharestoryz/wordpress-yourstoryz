<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Create a button that can be placed on the site using the shortcode [your_button] that fires the call_api function
function your_button_shortcode() {
    ?>
    <?php submit_button('Get YourStoryz', 'primary large', 'yourbutton', false); ?>
    <script type="text/javascript">
        document.getElementById('yourbutton').addEventListener('click', function() {
            fetch('<?php echo admin_url('admin-ajax.php?action=call_api'); ?>')
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                });
        });
    </script>
    <?php
}
add_shortcode('your_button', 'your_button_shortcode');
?>