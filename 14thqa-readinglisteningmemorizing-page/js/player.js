jQuery(document).ready(function($) {
    $('.quran-page-player').each(function() {
        const $player = $(this);
        const $audio = $player.find('.quran-audio');
        const $currentPage = $player.find('.current-page');
        const $translation = $player.find('.translation');
        const $quranText = $player.find('.quran-text');
        let $ayahs = $quranText.find('.ayah');
        const $title = $player.find('.player-title');
        const $autonextToggle = $player.find('.autonext-toggle');
        const $repeatToggle = $player.find('.repeat-toggle');
        const $progressCircle = $repeatToggle.find('.progress-circle');
        const $durationText = $repeatToggle.find('.duration');
        const $toggleText = $player.find('.toggle-text');

        let page = parseInt($player.data('page'));
        const reciter = $player.data('reciter');
        const translator = $player.data('translator');
        const repeat = parseInt($player.data('repeat'));
        const showTranslation = $player.data('show-translation') === 'true';
        let currentAyahIndex = 0;
        let autonext = true;
        let repeatInterval = false;
        let ayahDuration = 0;
        let countdownInterval = null;
/*
        const $canvas = $('<canvas class="text-overlay"></canvas>').hide();
        $quranText.parent().append($canvas);
        const canvas = $canvas[0];
        const ctx = canvas.getContext('2d');

        function resizeCanvas() {
    const rect = $quranText[0].getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;
    $canvas.css({
        width: rect.width + "px",
        height: rect.height + "px"
    });
    ctx.fillStyle = "rgb(0,0,0,0.1)";
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    console.log("Canvas resized: ", canvas.width, canvas.height)
        }
*/
		const $overlay = $("<div class='text-overlap'>").hide();
$quranText.parent().append($overlay);

function resizeOverlay() {
    const rect = $quranText[0].getBoundingClientRect();
    $overlay.css({
        width: rect.width + "px",
        height: rect.height + "px"
    });
    console.log("Overlay resized: ", rect.width, rect.height);
}
        function pad(num) {
            return num.toString().padStart(3, '0');
        }

        function loadAyah(sura, verse, play = false) {
            const reciterMap = {
                'alafasy': 'Alafasy_128kbps',
                'husary': 'Husary_128kbps',
                'minshawi': 'Minshawi_Mujawwad_192kbps',
                'abdulbasit': 'Abdul_Basit_Murattal_192kbps'
            };
            const reciterPath = reciterMap[reciter] || reciterMap['alafasy'];
            const url = `https://everyayah.com/data/${reciterPath}/${pad(sura)}${pad(verse)}.mp3`;
            $audio.attr('src', url);
            $title.text(`Page ${page}, Ayah ${verse} by ${reciter}`);

            $audio.off('error').on('error', function() {
                $title.text('Audio unavailable - check console');
            });

            const loadMetadata = new Promise((resolve) => {
                $audio.off('loadedmetadata').on('loadedmetadata', function() {
                    ayahDuration = $audio[0].duration || 5;
                    resolve();
                });
            });

            if (play) {
                loadMetadata.then(() => {
                    $audio[0].play().catch(function(e) {
                        $title.text('Audio playback failed: ' + e.message);
                    });
                });
            } else {
                $audio[0].pause();
                $audio[0].currentTime = 0;
                $title.text('Click an ayah to start audio');
            }

            $quranText.find('.ayah').removeClass('current');
            $quranText.find(`.ayah[data-sura="${sura}"][data-verse="${verse}"]`).addClass('current');

            if (showTranslation) {
                $translation.text('Loading translation...');
                $.ajax({
                    url: quranPageData.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'quran_page_get_translation',
                        sura: sura,
                        verse: verse,
                        translator: translator,
                        nonce: quranPageData.nonce
                    },
                    success: function(response) {
                        $translation.text(response.data || 'No translation available');
                        $translation.addClass('current');
                    },
                    error: function() {
                        $translation.text('Translation unavailable');
                        $translation.removeClass('current');
                    }
                });
            }
        }

        $audio.on('ended', function() {
            let repeats = $audio.data('repeat-count') || 0;
            if (repeats < repeat - 1) {
                repeats++;
                $audio.data('repeat-count', repeats);
                $audio[0].play();
            } else {
                $audio.data('repeat-count', 0);
                if (currentAyahIndex < $ayahs.length - 1 && autonext) {
                    if (repeatInterval && ayahDuration > 0) {
                        $progressCircle.addClass('active').css('--duration', ayahDuration + 's');
                        let remainingTime = ayahDuration;
                        $durationText.text(remainingTime.toFixed(1));
                        countdownInterval = setInterval(() => {
                            remainingTime -= 0.1;
                            if (remainingTime <= 0) {
                                clearInterval(countdownInterval);
                                countdownInterval = null;
                                $progressCircle.removeClass('active');
                                $durationText.text('');
                            } else {
                                $durationText.text(remainingTime.toFixed(1));
                            }
                        }, 100);
                        setTimeout(function() {
                            if (countdownInterval) {
                                clearInterval(countdownInterval);
                                countdownInterval = null;
                            }
                            $progressCircle.removeClass('active');
                            $durationText.text('');
                            currentAyahIndex++;
                            const $nextAyah = $ayahs.eq(currentAyahIndex);
                            loadAyah($nextAyah.data('sura'), $nextAyah.data('verse'), true);
                        }, ayahDuration * 1000);
                    } else {
                        currentAyahIndex++;
                        const $nextAyah = $ayahs.eq(currentAyahIndex);
                        loadAyah($nextAyah.data('sura'), $nextAyah.data('verse'), true);
                    }
                } else {
                    $title.text('Click an ayah to continue');
                }
            }
        });

        $toggleText.on("click", function() {
    const isHidden = $(this).attr("data-text-hidden") === "on";
    if (isHidden) {
        $canvas.hide();
        $(this).attr("data-text-hidden", "off").text("Hide Text");
        $quranText.css("pointer-events", "auto");
        console.log("Text shown");
    } else {
        resizeCanvas();
        $canvas.show();
        console.log("Canvas shown, width:", $canvas.width(), "height:", $canvas.height());
        $(this).attr("data-text-hidden", "on").text("Show Text");
        $quranText.css("pointer-events", "none");
        console.log("Text hidden");
    }
});

        $autonextToggle.on('click', function() {
            autonext = !autonext;
            $(this).attr('data-autonext', autonext ? 'on' : 'off');
            $(this).text(autonext ? 'Recite All' : 'Recite One');
        });

        $ayahs.on('click', function() {
            const sura = $(this).data('sura');
            const verse = $(this).data('verse');
            currentAyahIndex = $ayahs.index(this);
            loadAyah(sura, verse, true);
        });

        window.toggleRepeatInterval = function(element, playerId) {
            const player = document.getElementById('quran-page-' + playerId);
            if (player) {
                player.repeatInterval = element.checked;
                repeatInterval = player.repeatInterval;
            }
        };

        $(window).on('resize', resizeCanvas);
        resizeCanvas();
        loadAyah($ayahs.eq(0).data('sura'), $ayahs.eq(0).data('verse'), false);
        $player[0].repeatInterval = repeatInterval;
    });
});