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

  function selectCategory(category) {
    document.querySelector(`#radio-${state.category}`).closest('.category-option').classList.remove('current-cat');
    setState({category});
    document.querySelector(`#radio-${state.category}`).closest('.category-option').classList.add('current-cat');
  }
  window.selectCategory = selectCategory;

  function capitalize(word) {
    return word[0].toUpperCase() + word.slice(1);
  }

  function categoryPage(state) {
    return `
      <div>
        <div class="select-category-title">
          <h2>Ad Type</h2>
        </div>
        <div class="d-flex justify-content-center">
          <div class="subtitle">
            A couple of sentances to provide further detail and instruction
          </div>
        </div>
        <div class="category-holder d-flex justify-content-between">
          ${categories
            .map(
              cat => `
          <div class="category-option ${
            state.category === cat ? 'current-cat' : ''
          }" oninput="selectCategory('${cat}')">
            <input id="radio-${cat}"type="radio" name="category" value="${cat}" ${
                state.category === cat ? 'checked' : ''
              }>
            <label for="radio-${cat}"><h1>${capitalize(cat)}</h1></label>
          </div>
        `,
            )
            .join('')}
        </div>
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
          <img src="/assets/sample-image.svg" crossorigin="anonymous"> 
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
    drawImage(null, state, true);
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
    };
    let topBtns = document.querySelectorAll('.top-bar-link');
    topBtns.forEach(function(btn) {
      let nextClick = btn.onclick;
      btn.onclick = function(e) {
        if (currentPage === 3) {
          getImageFromCanvas(e, state);
        }
        nextClick();
      };
    });
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
    {html: categoryPage, title: 'Ad Type'},
    {
      html: targetingPage,
      title: 'Locations',
      loadFunc: attachScript.bind(this, '/js/map.js'),
    },
    {html: layoutPage, title: 'Layout'},
    {html: adCreatePage, title: 'Edit', loadFunc: adCreateLoad},
    {html: summaryPage, title: 'Summary'},
    {html: paymentPage, title: 'Payment'},
  ];

  let currentPage = Number(window.location.pathname.split('/').pop());
  let backBtn = document.querySelector('#back-btn');
  let nextBtn = document.querySelector('#next-btn');

  function showPage(pageNum) {
    topRightEls[currentPage].classList.remove('top-bar-selected');
    if (pageNum < 0 || pageNum > pages.length - 1) {
      return;
    }
    backBtn.style.visibility = pageNum === 0 ? 'hidden' : 'visible';
    nextBtn.innerHTML =
      pageNum !== pages.length - 1
        ? 'next<i class="fas fa-chevron-right">'
        : 'buy';
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
    topRightEls[currentPage].classList.add('top-bar-selected');
  }

  window.showPage = showPage;
  let topRight = document.querySelector('.top-bar-right');
  topRight.innerHTML = pages
    .map(
      (page, idx) => `
    <div class="top-bar-link" onclick="showPage(${idx})">${page.title}</div>
  `,
    )
    .join('');
  let topRightEls = document.querySelectorAll('.top-bar-right .top-bar-link');
  topRightEls[currentPage].classList.add('top-bar-selected');

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
    topRightEls[currentPage].classList.remove('top-bar-selected');
    currentPage = Number(window.location.pathname.split('/').pop());
    showPage(currentPage);
  };
  showPage(currentPage);

  document.querySelector('#back-btn').onclick = () => showPage(currentPage - 1);
})();
