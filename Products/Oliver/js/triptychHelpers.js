let adTypes = {
  other: {
    layouts: [
      {
        hasImage: true,
        imagePosition: [12, 12, 268, 201],
        textPosition: [300, 100],
        textMaxWidth: 330,
        textSize: 40,
        maxLines: 3,
        preview: '/assets/ad_preview.svg',
      },
      {
        hasImage: false,
        textPosition: [20, 100],
        textMaxWidth: 608,
        textSize: 48,
        maxLines: 3,
        preview: '/assets/ad_preview.svg',
      },
    ],
  },
  promo: {
    layouts: [
      {
        hasImage: true,
        imagePosition: [12, 12, 268, 201],
        textPosition: [300, 100],
        textMaxWidth: 330,
        textSize: 40,
        maxLines: 3,
        preview: '/assets/ad_preview.svg',
      },
      {
        hasImage: false,
        textPosition: [20, 100],
        textMaxWidth: 608,
        textSize: 48,
        maxLines: 3,
        preview: '/assets/ad_preview.svg',
      },
    ],
  },
  notice: {
    layouts: [
      {
        hasImage: true,
        imagePosition: [12, 12, 268, 201],
        textPosition: [300, 100],
        textMaxWidth: 330,
        textSize: 40,
        maxLines: 3,
        preview: '/assets/ad_preview.svg',
      },
      {
        hasImage: false,
        textPosition: [20, 100],
        textMaxWidth: 608,
        textSize: 48,
        maxLines: 3,
        preview: '/assets/ad_preview.svg',
      },
    ],
  },
};

let triptych = null;
let ctx = null;
let image = null;
let scale = 1;

function drawImage(e, state, isInit) {
  let layout = adTypes[state.category].layouts[state.selectedLayout];
  ctx.clearRect(0, 0, triptych.width, triptych.height);
  // ctx.fillStyle = e ? e.target.value : state.backgroundColor;
  ctx.fillRect(0, 0, triptych.width, triptych.height);
  if (layout.hasImage && !isInit) {
    ctx.drawImage(
      image,
      0,
      0,
      image.width,
      image.height,
      ...layout.imagePosition.map(num => num * scale),
    );
  } else if (layout.hasImage) {
    image = new Image();
    image.onload = function() {
      ctx.drawImage(
        image,
        0,
        0,
        image.width,
        image.height,
        ...layout.imagePosition.map(num => num * scale),
      );
      if (!image.src.includes('sample-image.svg')) {
        setState({sampleImageUsed: false});
      }
    };
    image.onerror = function() {
      image.src = '/assets/sample-image.svg';
      setState({imageSrc: image.src, sampleImageUsed: true});
    };
    image.src = state.imageSrc;
    console.log(image.src);
  }
}

function reRenderText() {
  let event = new Event('input');
  let textInput = document.querySelector('.triptych-text');
  textInput.dispatchEvent(event);
}

function handleCanvasText(e, state) {
  let layout = adTypes[state.category].layouts[state.selectedLayout];
  ctx.font = `bold ${layout.textSize * scale}px Lato`;
  let words = document.querySelector('.triptych-text').value.split(' ');
  let lines = [];
  let currentLine = '';
  for (let i = 0; i < words.length; i++) {
    let word = words[i];
    if (ctx.measureText(word).width > layout.textMaxWidth * scale) {
      let firstPart = '';
      let idx = 0;
      while (
        ctx.measureText(firstPart + word[idx]).width <
        layout.textMaxWidth * scale
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
      layout.textMaxWidth * scale
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
    reRenderText();
    return;
  }
  drawImage(null, state);
  let textColor = state.foregroundColor || 'black';//document.querySelector('[name=text-color-picker]').value;
  ctx.fillStyle = textColor;
  for (let i = 0; i < lines.length && i < layout.maxLines; i++) {
    ctx.fillText(
      lines[i],
      layout.textPosition[0] * scale,
      layout.textPosition[1] * scale + 2 + layout.textSize * scale * i,
    );
  }
  ctx.fillStyle = state.backgroundColor;
}

function handleFileInput(layout, state) {
  if (layout.hasImage) {
    let hasInput = document.querySelector('.file-upload');
    if (!hasInput) {
      let fileUpload = document.createElement('input');
      fileUpload.type = 'file';
      fileUpload.classList.add('file-upload');
      fileUpload.accept = 'image/png, image/jpeg';
      fileUpload.oninput = function() {
        image = new Image();
        image.onload = function() {
          drawImage(null, state);
          reRenderText();
        };
        image.src = URL.createObjectURL(this.files[0]);
        setState({imageSrc: image.src, sampleImageUsed: false});
      };
      let label = document.querySelector('.input-options');
      label.style.visibility = 'visible';
      label.innerHTML = 'Choose Image';
      label.appendChild(fileUpload);
    }
  } else {
    let input = document.querySelector('.file-upload');
    let label = document.querySelector('.input-options');
    label.style.visibility = 'hidden';
    if (input) {
      label.removeChild(input);
    }
  }
}

function getImageFromCanvas(e, state) {
  let oldCanvas = triptych;
  let newCanvas = document.createElement('canvas');
  newCanvas.width = 1920;
  newCanvas.height = 675;
  triptych = newCanvas;
  let oldScale = scale;
  scale = 3;
  let oldCtx = ctx;
  let newCtx = newCanvas.getContext('2d');
  ctx = newCtx;
  drawImage(null, state);
  reRenderText();
  triptych = oldCanvas;
  ctx = oldCtx;
  // Change scale back here so that the further editing can be done if necessary
  scale = oldScale;
  let src = newCanvas.toDataURL('img/jpeg');
  setState({finalImageSrc: src});
}
