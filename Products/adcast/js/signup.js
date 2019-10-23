let form = document.querySelector('form');

function onGoogleSignIn(googleUser) {
  // Useful data for your client-side scripts:
  let profile = googleUser.getBasicProfile();
  console.log("ID: " + profile.getId()); // Don't send this directly to your server!
  console.log('Full Name: ' + profile.getName());
  console.log('Given Name: ' + profile.getGivenName());
  console.log('Family Name: ' + profile.getFamilyName());
  console.log("Image URL: " + profile.getImageUrl());
  console.log("Email: " + profile.getEmail());

  // The ID token you need to pass to your backend:
  let id_token = googleUser.getAuthResponse().id_token;
  console.log("ID Token: " + id_token);
}

function signup() {
  let data = new FormData(form);
  let object = {};
  data.forEach((value, key) => {
    object[key] = value;
  });
  let json = JSON.stringify(object);
  console.log(json);
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
