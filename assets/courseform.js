import './styles/app.css';

const $ = require('jquery');

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