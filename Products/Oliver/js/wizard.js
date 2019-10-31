(() => {
  document.querySelector('.navbar-nav').classList.add('in-wizard');
  let categories = ['announcement', 'promo', 'notice'];
  let initialState = {
    category: categories[0],
    selectedLayout: 0,
    backgroundColor: 'white',
    textColor: 'black',
    canvasText: '',
    scale: 1,
    imageSrc: null,
    finalImageSrc: null,
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

  function categoryPage(state) {
    return `
      <div>
        Select Category
        ${categories
          .map(
            cat => `
          <div oninput="setState({category : '${cat}'})">
            <input type="radio" name="category" value="${cat}" ${
              state.category === cat ? 'checked' : ''
            }>
            <label for="${cat}">${cat}</label>
          </div>
        `,
          )
          .join('')}
      </div>
    `;
  }

  function targetingPage(state) {
    return `
      <div>
        Select Targeting
        <div id="map" style="width: 100%; height: 30vw"></div>
      </div>
    `;
  }

  function layoutPage(state) {
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

  function adCreatePage(state) {
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
        </div>
      </div>
    `;
  }

  function adCreateLoad() {
    triptych = document.querySelector('#triptych-edit');
    ctx = triptych.getContext('2d');
    if (state.imageSrc) {
      image = document.createElement('img');
      image.src = state.imageSrc;
    } else {
      image = document.querySelector('.triptych-images img');
    }

    let backgroundColorPicker = document.querySelector(
      '[name=background-color-picker]',
    );
    backgroundColorPicker.value = state.backgroundColor;
    backgroundColorPicker.oninput = function(e) {
      drawImage(e, state);
      setState({backgroundColor: e.target.value});
      reRenderText();
    };
    document.querySelector('.triptych-text').value = state.canvasText;
    document.querySelector('.triptych-text').oninput = function(e) {
      setState({canvasText: e.target.value});
      handleCanvasText(e, state);
    };
    let textColorPicker = document.querySelector('[name=text-color-picker]');
    textColorPicker.value = state.textColor;
    textColorPicker.oninput = function(e) {
      reRenderText();
      setState({textColor: e.target.value});
    };
    drawImage(null, state);
    reRenderText();
    handleFileInput(
      adTypes[state.category].layouts[state.selectedLayout],
      state,
    );
    let nextBtn = document.querySelector('#next-btn');
    let nextOnClick = nextBtn.onclick;
    nextBtn.onclick = function(e) {
      getImageFromCanvas(e, state);
      nextOnClick();
    }
  }

  function summaryPage(state) {
    return `
      <div>
        Summary
        <div>
          <img src="${state.finalImageSrc}" width="50%">
        </div>
      </div>
    `;
  }

  function paymentPage(state) {
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
    nextBtn.innerHTML = pageNum !== pages.length - 1 ? 'next<i class="fas fa-chevron-right">' : 'buy';
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
