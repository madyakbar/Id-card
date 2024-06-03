<?php
/*
Plugin Name: Personal ID Card
Description: A plugin to create a personal ID card for users.
Version: 1.0
Author: Mehdi AKbar (CareSort)
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
class PersonalIDCard {
    
    public function __construct() {
        add_shortcode('personal_id_form', array($this, 'display_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_submit_personal_id_form', array($this, 'submit_personal_id_form'));
        add_action('wp_ajax_nopriv_submit_personal_id_form', array($this, 'submit_personal_id_form'));
        add_action('wp_ajax_save_id_card_image', array($this,'save_id_card_image'));
        add_action('wp_ajax_nopriv_save_id_card_image',array($this, 'save_id_card_image'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_filter('upload_mimes', array($this, 'custom_upload_mimes'));
        register_activation_hook(__FILE__, array($this, 'flush_rewrite_rules'));
        register_deactivation_hook(__FILE__, array($this, 'flush_rewrite_rules'));
    }

 public function custom_upload_mimes($mime_types) {
    // Add WebP MIME type
    $mime_types['webp'] = 'image/webp';
    return $mime_types;
}

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), '4.5.2', true);
        wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css', array(), '4.5.2');
        wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', array('jquery'), null, true);


        wp_enqueue_style('your-custom-css', plugins_url('/css/style.css', __FILE__));
        wp_enqueue_script('ajax-script', plugins_url('/js/ajax-script.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('ajax-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

        wp_enqueue_script('croppie-js', 'https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.4/croppie.min.js', array('jquery'), null, true);
        wp_enqueue_style('croppie-css', 'https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.4/croppie.min.css');
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^idcard/details/([^/]+)$', 'index.php?pagename=details&user_slug=$matches[1]', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'user_slug';
        return $vars;
    }

    public function flush_rewrite_rules() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }

    public function display_form() {
    $plugin_url = plugin_dir_url(__FILE__);

    ob_start();
    ?>
    <div class="mian_bg" style="background: #eeeeee; padding: 4rem 10px;">
        <form id="personal-id-form" enctype="multipart/form-data">
            <?php wp_nonce_field('personal_id_form_nonce', 'security'); ?>
            <div>
                <h3 class="mx-5 text-center">Please Fill This Form To Create A Personal ID Card</h3>
                <label for="theme" class="d-block text-center">Select Theme:</label>
                <div class="theme-selection text-center">
                    <label>
                        <img class="d-block" src="<?php echo $plugin_url; ?>images/theme1.png" alt="Theme 1" width="100">
                        <input type="radio" name="theme" value="theme1" required>
                    </label>
                    <label>
                        <img class="d-block" src="<?php echo $plugin_url; ?>images/theme2.png" alt="Theme 2" width="100">
                        <input type="radio" name="theme" value="theme2" required>
                    </label>
                    <label>
                        <img class="d-block" src="<?php echo $plugin_url; ?>images/theme3.png" alt="Theme 3" width="100">
                        <input type="radio" name="theme" value="theme3" required>
                    </label>
                </div>
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" class="mb-3" name="first_name" required>
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" class="mb-3" name="last_name" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <label for="mobile">Mobile Number:</label>
                <input type="text" id="mobile" name="mobile" required>
                <label for="address">Address:</label>
                <textarea id="address" name="address" required></textarea>
                <label for="image" class="mt-3 d-block">Image:</label>
                <input type="file" id="upload_image" accept="image/*" required>
                <div id="image_preview"></div>
                <input type="hidden" id="cropped_image" name="cropped_image">
                <div class="button__ text-center mt-5">
                    <button type="submit" style="padding: 15px 50px; border-radius: 8px;">Submit</button>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="croppieModal" tabindex="-1" aria-labelledby="croppieModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="croppieModalLabel">Crop Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="croppie-container"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="set-cropped-image">Set</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailsModalLabel">User Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body m-auto" id="user-details">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

    public function submit_personal_id_form() {
    check_ajax_referer('personal_id_form_nonce', 'security');

    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $mobile = sanitize_text_field($_POST['mobile']);
    $address = sanitize_textarea_field($_POST['address']);
    $image = $_POST['image'];
    $theme = sanitize_text_field($_POST['theme']);

    $user_id = wp_insert_user(array(
        'user_login' => $email,
        'user_email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'user_pass' => wp_generate_password()
    ));

    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
    }

    $upload_dir = wp_upload_dir();
    $image_data = base64_decode(str_replace('data:image/png;base64,', '', $image));
    $file_path = $upload_dir['path'] . "/user_image_{$user_id}.png";
    file_put_contents($file_path, $image_data);
    $file_url = $upload_dir['url'] . "/user_image_{$user_id}.png";

    update_user_meta($user_id, 'user_image', $file_url);
    update_user_meta($user_id, 'user_theme', $theme);
    update_user_meta($user_id, 'user_mobile', $mobile);
    update_user_meta($user_id, 'user_address', $address);

    $custom_url = site_url("/idcard/details/{$first_name}{$last_name}-{$user_id}");
    wp_send_json_success(array('image_url' => $file_url, 'first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'mobile' => $mobile, 'address' => $address, 'theme' => $theme, 'custom_url' => $custom_url));
}

function save_id_card_image() {

    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);

        if ($user_id > 0 && isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
            $uploaded_file = $_FILES['image'];

            if (strpos($uploaded_file['type'], 'image') !== false) {
                $upload_dir = wp_upload_dir();
                $upload_path = $upload_dir['path'] . '/';
                
                $filename = 'Id_card' . $user_id . '.png'; 
                
                $moved = move_uploaded_file($uploaded_file['tmp_name'], $upload_path . $filename);

                if ($moved) {
                    $file_url = $upload_dir['url'] . '/' . $filename;

                    $attachment = array(
                        'guid' => $file_url,
                        'post_mime_type' => $uploaded_file['type'],
                        'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );

                    $attachment_id = wp_insert_attachment($attachment, $upload_path . $filename);
                    if (!is_wp_error($attachment_id)) {
                        // Generate the metadata for the attachment, and update the database record.
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_path . $filename);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);

                        echo $file_url;
                    } else {
                        echo 'Error inserting attachment: ' . $attachment_id->get_error_message();
                    }
                } else {
                    echo 'Error moving file to upload directory.';
                }
            } else {
                echo 'Uploaded file is not an image.';
            }
        } else {
            echo 'Invalid user ID or no file uploaded.';
        }
    } else {
        echo 'User ID not provided.';
    }

    wp_die();
}


    


}

new PersonalIDCard();
?>
