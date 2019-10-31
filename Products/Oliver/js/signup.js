let form = document.querySelector('form');


function signup() {
  let data = new FormData(form);
  let object = {};
  data.forEach((value, key) => {
    object[key] = value;
  });
  let json = JSON.stringify(object);
}

(() => {
  document.querySelector('.form-fields').innerHTML = [
    'name',
    'email',
    'password',
    'organization',
  ]
    .map(
      field => `
    <div class="form-group">
      <input 
        name="${field}" 
        type="${field}" 
        class="form-control" 
        id="${field}" 
        placeholder="${`${field[0].toUpperCase() + field.slice(1)}`}" 
        autocomplete="off"
      >
    </div>
  
  `,
    )
    .join('');
})();