import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'leaflet/dist/leaflet';
import 'leaflet.markercluster';
import { Chart, registerables } from 'chart.js';
import './bootstrap';

Chart.register(...registerables);
window.Chart = Chart;
