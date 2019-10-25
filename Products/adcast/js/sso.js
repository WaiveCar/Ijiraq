(() => {
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
      status: true,
      cookie: true,
      xfbml: true,
      version: 'v4.0',
    });
    getProfileInfo(function(profile) {
      console.log('profile', profile);
    });
  };
})();

function handleGoogleSignIn(googleUser) {
  // Useful data for your client-side scripts:
  let profile = googleUser.getBasicProfile();
  console.log('ID: ' + profile.getId()); // Don't send this directly to your server!
  console.log('Full Name: ' + profile.getName());
  /*
  console.log('Given Name: ' + profile.getGivenName());
  console.log('Family Name: ' + profile.getFamilyName());
  console.log('Image URL: ' + profile.getImageUrl());
  console.log('Email: ' + profile.getEmail());
  */
  // The ID token you need to pass to your backend:
  let id_token = googleUser.getAuthResponse().id_token;
  console.log('ID Token: ' + id_token);
}

function getProfileInfo(cb) {
  FB.getLoginStatus(function(response) {
    // Do something here if user is already logged in
    if (response.status === 'connected') {
      FB.api(
        '/me',
        {
          fields: 'id,picture,email,first_name,last_name,name',
        },
        function(response) {
          if (cb) {
            cb(response);
          }
        },
      );
    } else {
      cb('not logged in');
    }
  });
}

function fbLogin(e) {
  e.preventDefault();
  FB.login(getProfileInfo.bind(this, info => console.log('info', info)));
}
