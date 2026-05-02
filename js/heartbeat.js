function sendHeartbeat() {
    fetch('heartbeat.php').catch(function () {});
}
setInterval(sendHeartbeat, 2000);
sendHeartbeat();
window.addEventListener('beforeunload', function () {
    navigator.sendBeacon('heartbeat.php', new URLSearchParams({ action: 'offline' }));
});
