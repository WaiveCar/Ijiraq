function showErrorModal(title, text) {
  document.querySelector('#error-modal-title').textContent = title;
  document.querySelector('#error-modal-body').textContent = text;
  $('#error-modal').modal();
}
