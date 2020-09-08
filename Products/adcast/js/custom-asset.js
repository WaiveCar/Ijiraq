
function addtime(n) {
  if(n === false) {
    duration = 0;
    $("#runtime").hide();
  } else {
    duration += n;
    if(duration == 0) {
      $("#runtime").hide();
    } else {
      $("#runtime").html("Runtime: " + duration.toFixed(2) + " sec").show();
    }
  }
}
  // The event handler below handles the user uploading new files
  uploadInput = document.getElementById('image-upload');
    if (uploadInput) {
    uploadInput.addEventListener('change', function() {
      $(`.preview-holder-${ratio}`).siblings().removeClass('selector');
      $(`.preview-holder-${ratio}`).addClass('selector');
      var container = $(`.preview-holder-${ratio} .assets`);

      addtime(false);
      Array.prototype.slice.call(uploadInput.files).forEach(function(file) {

        let reader = new FileReader();

        reader.onload = function(e) {
          var asset, reference;

          let row = $(
            ['<div class="screen">',
               '<img src="/screen-black.png" class="bg">',
               '<button type="button" class="remove-asset btn btn-sm btn-dark">',
               '<i class="fas fa-times"></i>',
               '</button>',
               '<div class="asset-container"></div>',
            '</div>'].join(''));

          reference = _job.append(e.target.result);

          if(file.type.split('/')[0] === 'image') {
            asset = document.createElement('img');
            asset.onload = function() {
              resize(asset, asset.width, asset.height);
              container.append(row);
              addtime( 7.5 );
            }

            asset.src = e.target.result;
            asset.className = 'asset';
          } else {
            asset = document.createElement('video');
            var src = document.createElement('source');

            asset.setAttribute('preload', 'auto');
            asset.setAttribute('loop', 'true');
            asset.appendChild(src);

            src.src = e.target.result;

            asset.ondurationchange = function(e) {
              asset.currentTime = 0;
              asset.play();
              resize(asset, asset.videoWidth, asset.videoHeight);
              container.append(row);
              addtime( e.target.duration );
            }
          }

          $(".remove-asset", row).click(function() {
            _job.remove(reference);
            row.remove();
          });

          $(".asset-container", row).append(asset);
        };
        reader.readAsDataURL(file);
      });

      if(isFirst) {
        _preview.Play();
        isFirst = false;
      }
    });
  }
