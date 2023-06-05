import './styles/app.css';

const $ = require('jquery');

$(function() {
    updateFormPriceType($('#course_type').val())
});

$('#course_type').on('change', function () {
    updateFormPriceType(this.value)
});

function updateFormPriceType(type) {
    if (type === 'free') {
        $('#course_price').parent().addClass('hidden');
        $('#course_duration').parent().addClass('hidden');
    } else if (type === 'rent') {
        $('#course_price').parent().removeClass('hidden');
        $('#course_duration').parent().removeClass('hidden');
    } else if (type === 'buy') {
        $('#course_price').parent().removeClass('hidden');
        $('#course_duration').parent().addClass('hidden');
    }
}