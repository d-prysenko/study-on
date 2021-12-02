/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import './styles/global.scss';
import './styles/app.css';

const $ = require('jquery');
// this "modifies" the jquery module: adding behavior to it
// the bootstrap module doesn't export/return anything
require('bootstrap');

$('#course_type').on('change', function () {
    if (this.value === 'free') {
        $('#course_price').parent().addClass('hidden');
        $('#course_duration').parent().addClass('hidden');
    } else if (this.value === 'rent') {
        $('#course_price').parent().removeClass('hidden');
        $('#course_duration').parent().removeClass('hidden');
    } else if (this.value === 'buy') {
        $('#course_price').parent().removeClass('hidden');
        $('#course_duration').parent().addClass('hidden');
    }
});
