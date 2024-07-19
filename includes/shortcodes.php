<?php
// Shortcode for get yourstoryz button
function your_button_shortcode() {
    ?>
    <button class="your-button" id="yourbutton">Get YourStoryz</button>
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