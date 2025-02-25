<?php
if (!defined('ABSPATH')) {
    exit;
}

function quran_page_settings_page() {
    $reciters = [
        'alafasy' => 'Mishary Rashid Alafasy',
        'husary' => 'Mahmoud Khalil Al-Husary',
        'minshawi' => 'Mohamed Siddiq Al-Minshawi',
        'abdulbasit' => 'Abdul Basit Abdus Samad'
    ];
    $translators = [
        'en.sahih' => 'Sahih International (English)',
        'ar' => 'Arabic',
        'fr.montada' => 'French (Montada)',
        'ur.jalandhry' => 'Urdu (Jalandhry)'
    ];
    ?>
    <div class="wrap quran-page-settings">
        <h1><?php _e('14thQA Page Settings', '14thqa-readinglisteningmemorizing-page'); ?></h1>
        <?php if (isset($_GET['settings-updated'])) : ?>
            <div id="message" class="updated notice is-dismissible">
                <p><?php _e('Settings saved successfully.', '14thqa-readinglisteningmemorizing-page'); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php
            settings_fields('quran_page_settings_group');
            do_settings_sections('quran_page_settings_group');
            ?>
            <h2><?php _e('Default Settings', '14thqa-readinglisteningmemorizing-page'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="quran_page_default_reciter"><?php _e('Default Reciter', '14thqa-readinglisteningmemorizing-page'); ?></label>
                    </th>
                    <td>
                        <select name="quran_page_default_reciter" id="quran_page_default_reciter">
                            <?php foreach ($reciters as $key => $name) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected(get_option('quran_page_default_reciter', 'alafasy'), $key); ?>><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Choose the default reciter for audio playback.', '14thqa-readinglisteningmemorizing-page'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="quran_page_default_translator"><?php _e('Default Translator', '14thqa-readinglisteningmemorizing-page'); ?></label>
                    </th>
                    <td>
                        <select name="quran_page_default_translator" id="quran_page_default_translator">
                            <?php foreach ($translators as $key => $name) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected(get_option('quran_page_default_translator', 'en.sahih'), $key); ?>><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Choose the default translation language.', '14thqa-readinglisteningmemorizing-page'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="quran_page_default_repeat"><?php _e('Default Repeat Count', '14thqa-readinglisteningmemorizing-page'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="quran_page_default_repeat" id="quran_page_default_repeat" value="<?php echo esc_attr(get_option('quran_page_default_repeat', 1)); ?>" min="1">
                        <p class="description"><?php _e('Number of times each ayah should repeat.', '14thqa-readinglisteningmemorizing-page'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="quran_page_show_translation"><?php _e('Show Translation by Default', '14thqa-readinglisteningmemorizing-page'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="quran_page_show_translation" id="quran_page_show_translation" value="1" <?php checked(get_option('quran_page_show_translation', 0)); ?>>
                        <p class="description"><?php _e('Check to show translations by default.', '14thqa-readinglisteningmemorizing-page'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Default Settings', '14thqa-readinglisteningmemorizing-page')); ?>
        </form>
    </div>
    <?php
}