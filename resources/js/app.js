import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'leaflet/dist/leaflet';
import 'leaflet.markercluster';
import { Chart, registerables } from 'chart.js';
import AOS from 'aos';
import './bootstrap';

Chart.register(...registerables);
window.Chart = Chart;

Chart.defaults.color = '#94a3b8';
Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.08)';

AOS.init({
    duration: 600,
    easing: 'ease-out-cubic',
    once: true,
    offset: 50,
});
