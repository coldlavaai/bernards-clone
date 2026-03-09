// Elementor Counter Fix - animates counter widgets
document.addEventListener('DOMContentLoaded', function() {
  var counters = document.querySelectorAll('.elementor-counter-number');
  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        var el = entry.target;
        var to = parseInt(el.getAttribute('data-to-value')) || 0;
        var duration = parseInt(el.getAttribute('data-duration')) || 2000;
        var delimiter = el.getAttribute('data-delimiter') || ',';
        var start = 0;
        var startTime = null;
        function animate(timestamp) {
          if (!startTime) startTime = timestamp;
          var progress = Math.min((timestamp - startTime) / duration, 1);
          var current = Math.floor(progress * to);
          el.textContent = current.toLocaleString();
          if (progress < 1) requestAnimationFrame(animate);
          else el.textContent = to.toLocaleString();
        }
        requestAnimationFrame(animate);
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.5 });
  counters.forEach(function(c) { observer.observe(c); });
});
