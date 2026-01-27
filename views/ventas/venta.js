<script src="../../public/js/venta.js"></script>

const input = document.querySelector('input[list]');
const hidden = document.getElementById('producto_id');
const options = document.querySelectorAll('#lista_productos option');

input.addEventListener('input', () => {
    hidden.value = '';
    options.forEach(option => {
        if (option.value === input.value) {
            hidden.value = option.dataset.id;
        }
    });
});