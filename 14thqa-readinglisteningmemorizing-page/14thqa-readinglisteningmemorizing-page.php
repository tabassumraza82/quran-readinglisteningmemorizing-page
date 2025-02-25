<?php
/*
 Plugin Name: 14thQA-ReadingListeningMemorizing-Page
 Plugin URI: https://www.14thquranacademy.com
 Description: Display Quran pages with clickable ayahs, audio recitation, optional translations, and memorization features via shortcode.
 Version: 1.0.0
 Author: Syed Tabassum Raza
 Author URI: https://www.14thquranacademy.com
 License: GPL-2.0+
 Requires at least: 6.0
 Tested up to: 6.7
 Requires PHP: 7.4
 Text Domain: 14thqa-readinglisteningmemorizing-page
*/

if (!defined('ABSPATH')) {
    exit;
}

class QuranPagePlayer {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_quran_page_get_translation', [$this, 'ajax_get_translation']);
        add_action('wp_ajax_quran_page_get_page_text', [$this, 'ajax_get_page_text']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('14thqa-page-css', plugins_url('css/player.css', __FILE__), [], '1.0.0');
        wp_enqueue_style('amiri-font', 'https://fonts.googleapis.com/css2?family=Amiri&display=swap', [], null);
        wp_enqueue_script('14thqa-page-js', plugins_url('js/player.js', __FILE__), ['jquery'], '1.0.0', true);

        $pages = range(1, 604);
        wp_localize_script('14thqa-page-js', 'quranPageData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('quran-page-nonce'),
            'is_rtl' => is_rtl() ? 'yes' : 'no',
            'total_pages' => 604
        ]);
    }

    public function register_shortcodes() {
        require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
    }

    public function register_settings_page() {
        add_options_page(
            __('14thQA Page Settings', '14thqa-readinglisteningmemorizing-page'),
            __('14thQA Page Player', '14thqa-readinglisteningmemorizing-page'),
            'manage_options',
            '14thqa-readinglisteningmemorizing-page',
            [$this, 'settings_page_content']
        );
    }

    public function register_settings() {
        register_setting('quran_page_settings_group', 'quran_page_default_reciter', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('quran_page_settings_group', 'quran_page_default_translator', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('quran_page_settings_group', 'quran_page_default_repeat', ['sanitize_callback' => 'absint']);
        register_setting('quran_page_settings_group', 'quran_page_show_translation', ['sanitize_callback' => 'absint']);
    }

    public function settings_page_content() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
        quran_page_settings_page();
    }

    public static function get_translation($sura, $verse, $edition) {
        $response = wp_remote_get("https://api.alquran.cloud/v1/ayah/{$sura}:{$verse}/{$edition}", ['timeout' => 10]);
        if (is_wp_error($response)) {
            error_log('Translation API error: ' . $response->get_error_message());
            return 'Translation unavailable (API error)';
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        return $data->data->text ?? 'Translation unavailable (no data)';
    }

    public static function get_page_text($page) {
        $response = wp_remote_get("https://api.alquran.cloud/v1/page/{$page}/ar", ['timeout' => 10]);
        if (is_wp_error($response)) {
            error_log('Page API error: ' . $response->get_error_message());
            return 'Page text unavailable (API error)';
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        if (!$data || !isset($data->data->ayahs)) {
            return 'Page text unavailable (no data)';
        }

        $ayahs = [];
        foreach ($data->data->ayahs as $ayah) {
            $ayahs[] = [
                'sura' => $ayah->surah->number,
                'verse' => $ayah->numberInSurah,
                'text' => $ayah->text
            ];
        }
        return $ayahs;
    }

    public function ajax_get_translation() {
        check_ajax_referer('quran-page-nonce', 'nonce');
        $sura = absint($_POST['sura']);
        $verse = absint($_POST['verse']);
        $translator = sanitize_text_field($_POST['translator']);
        $translation = self::get_translation($sura, $verse, $translator);
        wp_send_json_success($translation);
    }

    public function ajax_get_page_text() {
        check_ajax_referer('quran-page-nonce', 'nonce');
        $page = absint($_POST['page']);
        $page_text = self::get_page_text($page);
        wp_send_json_success($page_text);
    }
}

new QuranPagePlayer();