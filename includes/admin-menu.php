<?php
// Show plugin in sidebar
add_action('admin_menu', 'yourstoryz_plugin_create_menu');

function yourstoryz_plugin_create_menu() {
    add_menu_page(
        'YourStoryz Info',
        'YourStoryz',
        'manage_options',
        'yourstoryz-plugin-info',
        'yourstoryz_plugin_info_page',
        'dashicons-buddicons-community',
        99, // Change position in sidebar
    );
    add_submenu_page(
        'yourstoryz-plugin-info',
        'YourStoryz Settings',
        'Settings',
        'manage_options',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_page'
    );
}

// Plugin info page
function yourstoryz_plugin_info_page() {
    ?>
    <div id="yourstoryz-plugin-overlay" style="display: none;">
        <div class="yourstoryz-plugin-content">
            <!-- <iframe src="https://www.yourstoryz.com" width="100%" height="100%"></iframe> -->
            <button class="yourstoryz-plugin-close" onclick="hide()">Close</button>
        </div>
    </div>
    <div class="yourstoryz-plugin-text">
        <img src="<?php echo plugin_dir_url( __FILE__ ) . '../assets/images/logo.svg'; ?>" alt="">
        <h1>Welcome to YourStoryz</h1>

        <p>Create & tell amazing stories that grab people's attention and bring your story to life seamlessly.</p>
        <h3 class="sub-title">Plugin</h3>
        <h2>What does this plugin do?</h2>
        <p>Create & tell amazing stories that grab people's attention and bring your story to life seamlessly.</p>
        <h3 class="sub-title">Plugin</h3>
        <h2>Who can use this plugin?</h2>
        <p>Create & tell amazing stories that grab people's attention and bring your story to life seamlessly.</p>
        <p>Please log in to the service to continue.</p>

        <div id="overlay">
            <button class="overlay-close" onclick="off()">Close</button>

            <div class="overlay-text">
                <p>Please enter YourStoryz Token in this field.</p>
                <form method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>">
                    <p><a class="" href="https://dashboard.yourstoryz.com/en/login" target=”_blank”>Login to YourStoryz.dashboard to create your YourStoryz Token.</a></p>
                    <input class="overlay-input" type="text" name="token" placeholder="token here"></input>

                    <input class="your-button center" type="submit" >
               </form>
                <?php
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    // collect value of input field
                    $name = $_POST['token'];
                    if (empty($name)) {
                        echo "Name is empty";
                    } else {
                        echo $name;
                    }
                }
                ?>
            </div>
        </div>

        <button class="your-button" onclick="on()">Instructions</button>
        <a class="your-button" href="https://dashboard.yourstoryz.com/en/login" target=”_blank”>Login to YourStoryz</a>

        <button class="yourstoryz-plugin-open" onclick="show()">YourStoryz Site</button>
        <?php echo do_shortcode('[your_button]'); ?>
    </div>
    
    <?php
}
function yourstoryz_plugin_settings_page() {
    ?>
        <h1>YourStoryz Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('yourstoryz_plugin_settings_group');
            do_settings_sections('yourstoryz-plugin-settings');
            submit_button();
            ?>
        </form>
    <?php
}
?>
