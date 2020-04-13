// The distrust system is for running on DSPs that are not ours.
// Essentially we have a periodic heartbeat that gets played back 
// to our servers and if it cuts out then we assume the device is
// offline or something like that.

var hidden, visibilityChange, isVisible, screentime = [];

if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
  hidden = "hidden";
  visibilityChange = "visibilitychange";
} else if (typeof document.msHidden !== "undefined") {
  hidden = "msHidden";
  visibilityChange = "msvisibilitychange";
} else if (typeof document.webkitHidden !== "undefined") {
  hidden = "webkitHidden";
  visibilityChange = "webkitvisibilitychange";
}

function handleVisibility(isExiting) {
  isVisible = isExiting || document[hidden];
  screentime.push([new Date(), isVisible]);
  let data = new FormData();
  data.append('_', JSON.stringify(screentime));
  navigator.sendBeacon("/tmp/beacon.php", data);
}

if(hidden !== undefined) {
  // Handle page visibility change
  document.addEventListener(visibilityChange, handleVisibility, false);
  handleVisibility()
} else {
  screentime.push([new Date(), false]);
  console.log("Visibility not supported");
}

function amIfullScreen() {
  // we are allowing for some kind of window decoration or branding, we shouldn't be too brutal.
  return Math.abs(window.outerHeight - screen.height) + Math.abs(window.outerWidth - screen.width) < 80;
}

window.addEventListener("unload", () => handleVisibility(true));

function hb() {
  //
  // we need to establish identity and our idea of 
  //
  //  the current time
  //  the last time we sent something
  //  what's currently being played
  //
  // not that we'll necessarily do anything with this xref but
  // if we need to establish consistency in the future this
  // will allow us to do so.
  //
}
