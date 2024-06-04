
function validateForm() {
    var fechaInicio = document.getElementById('fecha_inicio').value;
    var fechaFin = document.getElementById('fecha_fin').value;
    var numViajeros = document.getElementById('num_viajeros').value;
    var errorMessage = '';

    if (!fechaInicio || !fechaFin) {
        errorMessage = 'Por favor, complete ambas fechas.';
    } else if (new Date(fechaFin) < new Date(fechaInicio)) {
        errorMessage = 'La fecha de fin no puede ser menor que la fecha de inicio.';
    } else if (numViajeros <= 0) {
        errorMessage = 'El número de viajeros debe ser mayor que 0.';
    }

    if (errorMessage) {
        alert(errorMessage);
        return false;
    }

    showLoader();
    return true;
}

function showLoader() {
    document.getElementById('loader').style.display = 'flex';
    document.getElementById('loader').style.position = 'absolute';
    document.getElementById('loader').style.justifyContent = 'center';
    document.getElementById('loader').style.alignItems = 'center';
    document.getElementById('loader').style.top = '0';
    document.getElementById('loader').style.left = '0';
    document.getElementById('loader').style.width = '100%';
    document.getElementById('loader').style.height = '100%';

    document.getElementById('modal-content').style.width = '60%';
    document.getElementById('modal-content').style.height = '40%';
    document.getElementById('modal-content').style.backgroundColor = '#293846';
    document.getElementById('modal-content').style.color = '#fff';
    document.getElementById('modal-content').style.borderRadius = '20px';
    document.getElementById('modal-content').style.textAlign = 'center';
}

function updateFechaFinMin() {
    var fechaInicio = document.getElementById('fecha_inicio').value;
    document.getElementById('fecha_fin').min = fechaInicio;
}

document.addEventListener("DOMContentLoaded", function() {
    var today = new Date().toISOString().split('T')[0];
    document.getElementById('fecha_inicio').min = today;
    updateFechaFinMin();
});
