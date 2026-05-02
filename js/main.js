/**
 * مؤقت OTP + دوال مساعدة بسيطة إن وُجدت الصفحات المرتبطة.
 */
(function () {
    var el = document.getElementById('time');
    if (!el) {
        return;
    }

    var total = 120;
    function tick() {
        var m = Math.floor(total / 60);
        var s = total % 60;
        el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        if (total <= 0) {
            return;
        }
        total -= 1;
        setTimeout(tick, 1000);
    }
    tick();
})();
