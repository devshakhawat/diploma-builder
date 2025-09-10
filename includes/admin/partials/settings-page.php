<div class="wrap diploma-builder-settings">
    <h1><?php _e('Diploma Builder Settings', 'diploma-builder'); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('diploma_builder_settings'); ?>
        <?php do_settings_sections('diploma_builder_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Allow Guest Diplomas', 'diploma-builder'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Allow Guest Diplomas', 'diploma-builder'); ?></span></legend>
                        <label for="diploma_allow_guests">
                            <input name="diploma_allow_guests" type="checkbox" id="diploma_allow_guests" value="1" <?php checked(1, get_option('diploma_allow_guests', 1)); ?>>
                            <?php _e('Allow guests to create diplomas without logging in', 'diploma-builder'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Maximum Diplomas per User', 'diploma-builder'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Maximum Diplomas per User', 'diploma-builder'); ?></span></legend>
                        <input name="diploma_max_per_user" type="number" id="diploma_max_per_user" value="<?php echo esc_attr(get_option('diploma_max_per_user', 10)); ?>" class="small-text">
                        <p class="description"><?php _e('Set the maximum number of diplomas each user can create (0 for unlimited)', 'diploma-builder'); ?></p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Default Paper Color', 'diploma-builder'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Default Paper Color', 'diploma-builder'); ?></span></legend>
                        <select name="diploma_default_paper" id="diploma_default_paper">
                            <option value="white" <?php selected('white', get_option('diploma_default_paper', 'white')); ?>><?php _e('Classic White', 'diploma-builder'); ?></option>
                            <option value="ivory" <?php selected('ivory', get_option('diploma_default_paper', 'white')); ?>><?php _e('Ivory Cream', 'diploma-builder'); ?></option>
                            <option value="light_blue" <?php selected('light_blue', get_option('diploma_default_paper', 'white')); ?>><?php _e('Light Blue', 'diploma-builder'); ?></option>
                            <option value="light_gray" <?php selected('light_gray', get_option('diploma_default_paper', 'white')); ?>><?php _e('Light Gray', 'diploma-builder'); ?></option>
                        </select>
                        <p class="description"><?php _e('Select the default paper color for new diplomas', 'diploma-builder'); ?></p>
                    </fieldset>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Diploma Product ID', 'diploma-builder'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Diploma Product ID', 'diploma-builder'); ?></span></legend>
                        <input name="diploma_single_product_id" type="number" id="diploma_single_product_id" value="<?php echo esc_attr(get_option('diploma_single_product_id', 0)); ?>" class="small-text">
                        <p class="description"><?php _e('Enter the WooCommerce Product ID for the diploma', 'diploma-builder'); ?></p>
                    </fieldset>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>