let form = document.querySelector('form');

function handleGoogleSignIn(googleUser) {
  // Useful data for your client-side scripts:
  let profile = googleUser.getBasicProfile();
  /*
  console.log('ID: ' + profile.getId()); // Don't send this directly to your server!
  console.log('Full Name: ' + profile.getName());
  console.log('Given Name: ' + profile.getGivenName());
  console.log('Family Name: ' + profile.getFamilyName());
  console.log('Image URL: ' + profile.getImageUrl());
  console.log('Email: ' + profile.getEmail());

  // The ID token you need to pass to your backend:
  let id_token = googleUser.getAuthResponse().id_token;
  console.log('ID Token: ' + id_token);
  */
}

function fbLogin(e) {
  e.preventDefault();
  FB.getLoginStatus(function(response) {
    if (response.session) {
      top.location.href = 'https://localhost:5000/signup';
    } else {
      top.location.href = `https://www.facebook.com/dialog/oauth?client_id=536536940468408&redirect_uri=https://localhost:5000/signup&scope=email,read_stream`;
    }
  });
}

function checkFacebookLoginState() {
  FB.getLoginStatus(function(response) {
    console.log('respons', response);
  });
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
  // Google Login init
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
  // Facebook login init
  window.fbAsyncInit = function() {
    FB.init({
      appId: '536536940468408',
      cookie: true,
      xfbml: true,
      version: 'v4.0',
    });

    FB.getLoginStatus(function(response) {
      // Do something here if user is already logged in
      if (response.status === 'connected') {
        FB.api(
          '/me',
          {
            fields: 'id,picture,email,first_name,last_name,name',
          },
          function(response) {
            console.log('profile info: ', response);
          },
        );
      }
    });
  };
  (function(d, s, id) {
    var js,
      fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {
      return;
    }
    js = d.createElement(s);
    js.id = id;
    js.src = 'https://connect.facebook.net/en_US/sdk.js';
    fjs.parentNode.insertBefore(js, fjs);
  })(document, 'script', 'facebook-jssdk');
})();
