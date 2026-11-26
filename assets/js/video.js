(function () {
    document.querySelectorAll('[data-ys-video]').forEach(function (player) {
        var video = player.querySelector('video');
        var button = player.querySelector('[data-ys-play]');

        if (!video || !button) {
            return;
        }

        // The native controls stay in the markup for visitors without JavaScript.
        video.controls = false;
        button.hidden = false;

        button.addEventListener('click', function () {
            video.play();
        });

        video.addEventListener('play', function () {
            button.hidden = true;
            video.controls = true;
        });
    });
})();
