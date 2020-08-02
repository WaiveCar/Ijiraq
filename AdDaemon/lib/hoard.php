<?

require  'vendor/autoload.php';
function hoard_discover($payload) {
  // We're going to be stupid right now and do something that
  // could potentially, through sloppy coding, lead to collisions
  // like we had with waivescreen uids.
  //
  // This is MOSTLY because I've been lagging for 6 months
  // on writing this so I have to toss ambitious dreams
  // out the window in order to become unstuck.
  if(!isset($payload['uid'])) {
    // We'll trust the uid from the screen for now
    if(isset($_SESSION['uid'])) {
      $payload['uid'] = $_SESSION['uid'];
    } // otherwise we have to generate a new screen id

  }
}


