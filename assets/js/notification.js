// Auto-remove notifications after 5 seconds
setTimeout(function () {
  const alerts = document.querySelectorAll(".notification-container .alert");
  alerts.forEach(function (alert) {
    alert.style.opacity = "0";
    alert.style.transform = "translateX(30px)";
    setTimeout(function () {
      alert.remove();
    }, 500);
  });
}, 5000);
