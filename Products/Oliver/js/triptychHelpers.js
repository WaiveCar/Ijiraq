let adTypes = {
  announcement: {
    layouts: [
      {
        hasImage: true,
        imagePosition: [360, 12, 268, 201],
        textPosition: [12, 80],
        textMaxWidth: 340,
        textSize: 36,
        maxLines: 3,
      },
      {
        hasImage: false,
        textPosition: [12, 72],
        textMaxWidth: 616,
        textSize: 48,
        maxLines: 3,
      },
    ],
  },
  promo: {
    layouts: [
      {
        hasImage: true,
        imagePosition: [360, 12, 268, 201],
        textPosition: [12, 80],
        textMaxWidth: 340,
        textSize: 36,
        maxLines: 3,
      },
      {
        hasImage: false,
        textPosition: [12, 72],
        textMaxWidth: 616,
        textSize: 48,
        maxLines: 3,
      },
    ],
  },
  notice: {
    layouts: [
      {
        hasImage: true,
        imagePosition: [360, 12, 268, 201],
        textPosition: [12, 80],
        textMaxWidth: 340,
        textSize: 36,
        maxLines: 3,
      },
      {
        hasImage: false,
        textPosition: [12, 72],
        textMaxWidth: 616,
        textSize: 48,
        maxLines: 3,
      },
    ],
  },
};

let triptych = null;
let ctx = null;
let image = null;

function drawImage(e, state, isInit) {
  let layout = adTypes[state.category].layouts[state.selectedLayout];
  ctx.clearRect(0, 0, triptych.width, triptych.height);
  ctx.fillStyle = e ? e.target.value : state.backgroundColor;
  ctx.fillRect(0, 0, triptych.width, triptych.height);
  if (layout.hasImage && !isInit) {
    ctx.drawImage(
      image,
      0,
      0,
      image.width,
      image.height,
      ...layout.imagePosition.map(num => num * state.scale),
    );
  } else {
    image = new Image();
    image.onload = function() {
      ctx.drawImage(
        image,
        0,
        0,
        image.width,
        image.height,
        ...layout.imagePosition.map(num => num * state.scale),
      );
    };
    image.onerror = function() {
      image.src = '/assets/sample-image.svg';
      setState({imageSrc: image.src});
    };
    image.src = state.imageSrc;
  }
}

function reRenderText() {
  let event = new Event('input');
  let textInput = document.querySelector('.triptych-text');
  textInput.dispatchEvent(event);
}

function handleCanvasText(e, state) {
  let layout = adTypes[state.category].layouts[state.selectedLayout];
  ctx.font = `${layout.textSize * state.scale}px Arial`;
  let words = e.target.value.split(' ');
  let lines = [];
  let currentLine = '';
  for (let i = 0; i < words.length; i++) {
    let word = words[i];
    if (ctx.measureText(word).width > layout.textMaxWidth * state.scale) {
      let firstPart = '';
      let idx = 0;
      while (
        ctx.measureText(firstPart + word[idx]).width <
        layout.textMaxWidth * state.scale
      ) {
        firstPart += word[idx];
        idx++;
      }
      let secondPart = word.slice(firstPart.length);
      word = firstPart;
      words.splice(i + 1, 0, secondPart);
    }
    if (
      ctx.measureText(currentLine + word).width <
      layout.textMaxWidth * state.scale
    ) {
      currentLine += word + ' ';
    } else {
      lines.push(currentLine);
      currentLine = word + ' ';
    }
  }
  lines.push(currentLine);
  if (lines.length > layout.maxLines) {
    let text = e.target.value;
    document.querySelector('.triptych-text').value = text.slice(
      0,
      text.length - 1,
    );
    return;
  }
  drawImage(null, state);
  let textColor = document.querySelector('[name=text-color-picker]').value;
  ctx.fillStyle = textColor;
  for (let i = 0; i < lines.length && i < layout.maxLines; i++) {
    ctx.fillText(
      lines[i],
      layout.textPosition[0] * state.scale,
      layout.textPosition[1] * state.scale +
        2 +
        layout.textSize * state.scale * i,
    );
  }
  ctx.fillStyle = state.backgroundColor;
}

function handleFileInput(layout, state) {
  if (layout.hasImage) {
    let hasInput = document.querySelector('#fileUpload');
    if (!hasInput) {
      let fileUpload = document.createElement('input');
      fileUpload.type = 'file';
      fileUpload.id = 'fileUpload';
      fileUpload.accept = 'image/png, image/jpeg';
      fileUpload.oninput = function() {
        image = new Image();
        image.onload = function() {
          drawImage(null, state);
          reRenderText();
        };
        image.src = URL.createObjectURL(this.files[0]);
        setState({imageSrc: image.src});
      };
      document.querySelector('.input-options').appendChild(fileUpload);
    }
  } else {
    let input = document.querySelector('#fileUpload');
    if (input) {
      document.querySelector('.input-options').removeChild(input);
    }
  }
}

function getImageFromCanvas(e, state) {
  let oldCanvas = triptych;
  let newCanvas = document.createElement('canvas');
  newCanvas.width = 1920;
  newCanvas.height = 675;
  triptych = newCanvas;
  setState({scale: 3});
  let oldCtx = ctx;
  let newCtx = newCanvas.getContext('2d');
  ctx = newCtx;
  drawImage(null, state);
  reRenderText();
  triptych = oldCanvas;
  ctx = oldCtx;
  // Change scale back here so that the further editing can be done if necessary
  setState({scale: 1});
  let src = newCanvas.toDataURL('img/jpeg');
  setState({finalImageSrc: src});
}
