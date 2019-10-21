(() => {
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

  let initialState = {
    categories: ['announcement', 'promo', 'notice'],
    category: '',
    selectedLayout: 0,
    backgroundColor: 'white',
    scale: 1,
  };
  let state = {};

  function setState(updateObj) {
    Object.assign(state, updateObj);
    localStorage.setItem('savedState', JSON.stringify(state));
  }

  window.setState = setState;

  function attachScript(src) {
    let script = document.createElement('script');
    script.src = src;
    document.body.appendChild(script);
  }

  function categoryPage(props) {
    return `
      <div>
        Select Category
        ${props.categories
          .map(
            cat => `
          <div oninput="setState({category : '${cat}'})">
            <input type="radio" name="category" value="${cat}" ${
              props.category === cat ? 'checked' : ''
            }>
            <label for="${cat}">${cat}</label>
          </div>
        `,
          )
          .join('')}
      </div>
    `;
  }

  function targetingPage(props) {
    return `
      <div>
        Select Targeting
        <div id="map" style="width: 100%; height: 30vw"></div>
      </div>
    `;
  }

  function layoutPage(props) {
    return `
      <div>
        Select Layout
        <div class="layout-options">
          ${adTypes[state.category].layouts
            .map(
              (layout, i) =>
                `
        <input oninput="setState({selectedLayout: ${i}})" type="radio" name="triptych-options" value="${i}" ${
                  i === state.selectedLayout ? 'checked' : ''
                }>
        <label for="option${i}">${i}</label>
        `,
            )
            .join('')}
        </div>
      </div>
    `;
  }

  function adCreatePage(props) {
    return `
      <style>
        .triptych-images {
          display: none;
        }
        .triptych-text {
          width: 500px;
          height: 60px;
        }
        #triptych-edit {
          border: 1px solid black;
        }
      </style>
      <div>
        Ad Info
        <div class="triptych-images">
          <img src="https://upload.wikimedia.org/wikipedia/commons/d/de/Aspect-ratio-4x3.svg" crossorigin="anonymous"> 
        </div>
        <div class="input-options">
          <input type="color" name="background-color-picker"><label for="background-color-picker">Background Color</label>
          <input type="color" name="text-color-picker"><label for="text-color-picker">Text Color</label>
          <textarea type="text" class="triptych-text" placeholder="enter text"></textarea>
        </div>
        <div>
          <div style="width: 100px; height 200px;">
            <canvas id="triptych-edit" width="640" height="225">
          </div>
          </canvas>
        </div>
        <button class="choose-image">Choose This</button>
      </div>
    `;
  }
  let triptych = null;
  let ctx = null;
  let image = null;
  function adCreateLoad() {
    triptych = document.querySelector('#triptych-edit');
    ctx = triptych.getContext('2d');
    image = document.querySelector('.triptych-images img');
    document.querySelector('[name=background-color-picker]').oninput = function(e) {
      drawImage(e);
      reRenderText();
    }
    document.querySelector('.triptych-text').oninput = handleCanvasText;
    document.querySelector('[name=text-color-picker]').oninput = reRenderText;
    drawImage();
    reRenderText();
  }

  function drawImage(e) {
    let layout = adTypes[state.category].layouts[state.selectedLayout];
    ctx.clearRect(0, 0, triptych.width, triptych.height);
    ctx.fillStyle = e ? e.target.value : state.backgroundColor;
    setState({backgroundColor: ctx.fillStyle});
    ctx.fillRect(0, 0, triptych.width, triptych.height);
    if (layout.hasImage) {
      ctx.drawImage(image, 0, 0, image.width, image.height, ...layout.imagePosition.map(num => num * state.scale));
    }
  }

  function reRenderText() {
    let event = new Event('input');
    let textInput = document.querySelector('.triptych-text');
    textInput.dispatchEvent(event);
  }
  
  function handleFileInput(layout) {
    if (layout.hasImage) {
      let hasInput = document.querySelector('#fileUpload');
      if (!hasInput) {
        let fileUpload = document.createElement('input');
        fileUpload.type = 'file';
        fileUpload.id = 'fileUpload';
        fileUpload.accept = "image/png, image/jpeg";
        fileUpload.oninput = function() {
          image = new Image();
          image.onload = function() {
            drawImage();
            reRenderText();
          }
          image.src = URL.createObjectURL(this.files[0]);
        }
        document.querySelector('.input-options').appendChild(fileUpload);
      }
    } else {
      let input = document.querySelector('#fileUpload');
      if (input) {
        document.querySelector('.input-options').removeChild(input);
      }
    }
  }

  function handleCanvasText(e) {
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
        while (ctx.measureText(firstPart + word[idx]).width < layout.textMaxWidth * state.scale) {
          firstPart += word[idx];
          idx++;
        }
        let secondPart = word.slice(firstPart.length);
        word = firstPart;
        words.splice(i + 1, 0, secondPart);
      }
      if (ctx.measureText(currentLine + word).width < layout.textMaxWidth * state.scale) {
        currentLine += word + ' ';
      } else {
        lines.push(currentLine);
        currentLine = word + ' ';
      }
    }
    lines.push(currentLine);
    if (lines.length > layout.maxLines) {
      let text = e.target.value;
      document.querySelector('.triptych-text').value = text.slice(0, text.length - 1);
      return;
    }
    drawImage();
    let textColor = document.querySelector('[name=text-color-picker]').value;
    ctx.fillStyle = textColor;
    for (let i = 0; i < lines.length && i < layout.maxLines; i++) {
      ctx.fillText(lines[i], layout.textPosition[0] * state.scale, ((layout.textPosition[1] * state.scale) + 2 + (layout.textSize * state.scale * i)));
    }
    ctx.fillStyle = state.backgroundColor;
  }

  function budgetPage(props) {
    return `
      <div>
        Edit Budget
      </div>
    `;
  }

  function summaryPage(props) {
    return `
      <div>
        Summary
      </div>
    `;
  }

  function paymentPage(props) {
    return `
      <div>
        Payment
      </div>
    `;
  }

  let pages = [
    {html: categoryPage},
    {html: targetingPage, loadFunc: attachScript.bind(this, '/js/map.js')},
    {html: layoutPage},
    {html: adCreatePage, loadFunc: adCreateLoad},
    {html: budgetPage},
    {html: summaryPage},
    {html: paymentPage},
  ];

  let currentPage = Number(window.location.pathname.split('/').pop());
  let backBtn = document.querySelector('#back-btn');
  let nextBtn = document.querySelector('#next-btn');

  function showPage(pageNum) {
    if (pageNum < 0 || pageNum > pages.length - 1) {
      return;
    }
    backBtn.style.visibility = pageNum === 0 ? 'hidden' : 'visible';
    nextBtn.textContent = pageNum !== pages.length - 1 ? 'Next' : 'Submit';
    nextBtn.onclick =
      pageNum !== pages.length - 1
        ? () => showPage(currentPage + 1)
        : () => submit(state);
    document.querySelector('#anchor').innerHTML = pages[pageNum].html(
      state,
      anchor,
    );
    if (pages[pageNum].loadFunc) {
      pages[pageNum].loadFunc();
    }
    if (currentPage !== pageNum) {
      window.history.pushState(
        {},
        pageNum,
        window.location.origin + '/campaigns/wizard/' + pageNum,
      );
    }
    currentPage = pageNum;
  }

  function submit(data) {
    console.log('Submitting: ', data);
  }

  let savedState = localStorage.getItem('savedState');
  if (savedState) {
    setState(JSON.parse(savedState));
  } else {
    setState(initialState);
  }

  window.onpopstate = function() {
    currentPage = Number(window.location.pathname.split('/').pop());
    showPage(currentPage);
  };
  showPage(currentPage);

  document.querySelector('#back-btn').onclick = () => showPage(currentPage - 1);
})();
