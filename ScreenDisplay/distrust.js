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

window.addEventListener("unload", () => handleVisibility(true));

