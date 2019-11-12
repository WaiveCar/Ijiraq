function handleGoogleSignIn(googleUser) {
  // Useful data for your client-side scripts:
  let profile = googleUser.getBasicProfile();
  console.log('ID: ' + profile.getId()); // Don't send this directly to your server!
  console.log('Full Name: ' + profile.getName());
  console.log('Given Name: ' + profile.getGivenName());
  console.log('Family Name: ' + profile.getFamilyName());
  console.log('Image URL: ' + profile.getImageUrl());
  console.log('Email: ' + profile.getEmail());

  // The ID token you need to pass to your backend:
  let id_token = googleUser.getAuthResponse().id_token;
  console.log('ID Token: ' + id_token);
}

(() => {
  document.querySelector('.form-fields').innerHTML = ['email', 'password']
    .map(field => {
      var type = field == 'email' ? 'text' : 'password';
      return `
    <div class="form-group">
      <input 
        name="${field}" 
        type="${type}" 
        class="form-control" 
        id="${field}" 
        placeholder="${`${field[0].toUpperCase() + field.slice(1)}`}" 
        autocomplete="off"
      >
    </div>
  
  `;
    })
    .join('');
  gapi.load('auth2', function() {
    let GoogleAuth = gapi.auth2
      .init({
        client_id:
          '237832253799-vrn0c73js364ub4pqob679obhp2m14dm.apps.googleusercontent.com',
      })
      .then(GoogleAuth => {
        if (GoogleAuth.isSignedIn.get()) {
          let user = GoogleAuth.currentUser.get();
          handleGoogleSignIn(user);
        }
      })
      .catch(e => console.log('Error loading google sigin api', e));
  });
})();