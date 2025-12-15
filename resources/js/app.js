// jQuery is loaded globally from public/assets/js/jquery-3.7.1.min.js
// Ensure it's available
if (typeof window.$ === 'undefined' && typeof window.jQuery === 'undefined') {
    console.error('jQuery is not loaded. Please ensure jquery-3.7.1.min.js is loaded before this script.');
}

// Import Bootstrap
import 'bootstrap';

// Import DataTables
import 'datatables.net';
import 'datatables.net-responsive';

// Import Toastr and make it globally available
import toastr from 'toastr';
window.toastr = toastr;
import 'toastr/build/toastr.min.css';

// Import FontAwesome
import '@fortawesome/fontawesome-free/css/all.min.css';

// Import SCSS
import '../scss/app.scss';

// Import custom scripts
import './scripts.js';

