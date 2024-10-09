<?php
function admin_inline_js() {
    ?>
    <script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        var button = document.getElementById("yourstoryz_getstoryz");
        if (button) {
            button.addEventListener("click", function(e) {
                e.preventDefault();
                button.disabled = true;
                button.innerHTML = "Fetching storyz...";
                fetch("<?php echo esc_url(admin_url('admin-ajax.php?action=call_api')); ?>")
                    .then(function(data) {
                        button.disabled = false;
                        button.innerHTML = "Get YourStoryz";
                    })
                    .catch(function(error) {
                        button.disabled = false;
                        button.innerHTML = "Something went wrong";
                    });
            });
        }
    });
    </script>
    <?php
}
add_action('admin_print_scripts', 'admin_inline_js');
?>
