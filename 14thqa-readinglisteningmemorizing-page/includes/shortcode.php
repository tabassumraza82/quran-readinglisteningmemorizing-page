<?php
if (!defined('ABSPATH')) {
    exit;
}

function quran_page_shortcode($atts) {
    $atts = shortcode_atts([
        'page' => 1,
        'reciter' => get_option('quran_page_default_reciter', 'alafasy'),
        'translator' => get_option('quran_page_default_translator', 'en.sahih'),
        'repeat' => get_option('quran_page_default_repeat', 1),
        'show_translation' => get_option('quran_page_show_translation', 0) ? 'true' : 'false'
    ], $atts, 'quran_page');

    $page = absint($atts['page']);
    $reciter = sanitize_text_field($atts['reciter']);
    $translator = sanitize_text_field($atts['translator']);
    $repeat = absint($atts['repeat']);
    $show_translation = filter_var($atts['show_translation'], FILTER_VALIDATE_BOOLEAN);

    $page = max(1, min(604, $page));
    $reciters = [
        'alafasy' => ['name' => 'Mishary Rashid Alafasy'],
        'husary' => ['name' => 'Mahmoud Khalil Al-Husary'],
        'minshawi' => ['name' => 'Mohamed Siddiq Al-Minshawi'],
        'abdulbasit' => ['name' => 'Abdul Basit Abdus Samad']
    ];
    $reciter_key = array_key_exists($reciter, $reciters) ? $reciter : 'alafasy';

    // Fetch page text
    $page_text = QuranPagePlayer::get_page_text($page);

    // Fetch surah names (transient caching similar to reference plugin)
    $surah_names = [];
    $transient = get_transient('quran_page_surah_names');
    if ($transient === false) {
        $response = wp_remote_get('https://api.alquran.cloud/v1/surah', ['timeout' => 10]);
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            if ($data && isset($data->data)) {
                foreach ($data->data as $surah) {
                    $surah_names[$surah->number] = $surah->name . ' (' . $surah->englishName . ')';
                }
                set_transient('quran_page_surah_names', $surah_names, WEEK_IN_SECONDS);
            }
        }
    } else {
        $surah_names = $transient;
    }

    $rand_id = rand(1000, 9999);

    $output = '<div class="quran-page-player" id="quran-page-' . esc_attr($rand_id) . '" data-page="' . esc_attr($page) . '" data-reciter="' . esc_attr($reciter_key) . '" data-translator="' . esc_attr($translator) . '" data-repeat="' . esc_attr($repeat) . '" data-show-translation="' . esc_attr($show_translation ? 'true' : 'false') . '">';
    $output .= '<div class="quran-text-block">';
    $output .= '<div class="quran-text">';
    if (is_array($page_text)) {
        $current_surah = null;
        foreach ($page_text as $ayah) {
            if ($current_surah !== $ayah['sura']) {
                // Add surah name before starting new surah's ayahs
                $surah_name = isset($surah_names[$ayah['sura']]) ? $surah_names[$ayah['sura']] : 'Surah ' . $ayah['sura'];
                $output .= '<div class="surah-name">' . esc_html($surah_name) . '</div>';
                $current_surah = $ayah['sura'];
            }
            $output .= '<span class="ayah" data-sura="' . esc_attr($ayah['sura']) . '" data-verse="' . esc_attr($ayah['verse']) . '">' . esc_html($ayah['text']) . ' (' . esc_html($ayah['verse']) . ')</span> ';
        }
    } else {
        $output .= esc_html($page_text);
    }
    $output .= '</div>';
    if ($show_translation && is_array($page_text)) {
        $output .= '<div class="translation">' . esc_html(QuranPagePlayer::get_translation($page_text[0]['sura'], $page_text[0]['verse'], $translator)) . '</div>';
    }
    $output .= '</div>';
    $output .= '<p class="player-title">' . esc_html(sprintf(__('Page %d by %s'), $page, $reciters[$reciter_key]['name'])) . '</p>';
    $output .= '<audio controls class="quran-audio"></audio>';
    $output .= '<div class="page-controls">';
    $output .= '<span class="current-page">' . esc_html($page) . '</span>';
    $output .= '<button class="toggle-text" data-text-hidden="off">' . esc_html__('Hide Text') . '</button>';
    $output .= '<button class="autonext-toggle" data-autonext="on">' . esc_html('Recite All') . '</button>';
    $output .= '<label class="repeat-toggle"><input type="checkbox" id="repeat-interval-toggle-' . esc_attr($rand_id) . '" onchange="toggleRepeatInterval(this, \'' . esc_attr($rand_id) . '\')"> ' . esc_html__('I will repeat too') . ' <span class="progress-circle"></span><span class="duration"></span></label>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}
add_shortcode('quran_page', 'quran_page_shortcode');