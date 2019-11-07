(() => {
  document.querySelector('.navbar-nav').classList.add('in-wizard');
  let categories = ['announcement', 'promo', 'notice'];
  let initialState = {
    category: categories[0],
    selectedLayout: 0,
    backgroundColor: 'white',
    textColor: 'black',
    canvasText: '',
    imageSrc: null,
    finalImageSrc: null,
  };
  let state = {};
  let categoryTips = {
    announcement: 'Description for the announcement',
    promo: 'Description from the promo option',
    notice: 'Description for the notice option',
  };

  window.setState = function (updateObj) {
    Object.assign(state, updateObj);
    localStorage.setItem('savedState', JSON.stringify(state));
  }

  function attachScript(src) {
    let script = document.createElement('script');
    script.src = src;
    document.body.appendChild(script);
  }

  window.selectCategory = function(category) {
    document
      .querySelector(`#radio-${state.category}`)
      .closest('.category-option')
      .classList.remove('current-cat');
    setState({category});
    document
      .querySelector(`#radio-${state.category}`)
      .closest('.category-option')
      .classList.add('current-cat');
  }

  function capitalize(word) {
    return word[0].toUpperCase() + word.slice(1);
  }

  function post(ep, body, cb) {
    fetch(new Request(`http://adcast/api/${ep}`, {
      method: 'POST', 
      body: JSON.stringify(body)
    })).then(res => {
      if (res.status === 200) {
        return res.json();
      }
    }).then(cb);
  }

  function categoryPage(state) {
    return `
      <div>
        <div class="wizard-title">
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
            <label for="radio-${cat}">
              <h1 data-toggle="popover" data-placement="bottom" data-content="${
                categoryTips[cat]
              }">
                ${capitalize(cat)}
              </h1>
            </label>
          </div>
        `,
            )
            .join('')}
        </div>
      </div>
    `;
  }

  function handlePopover() {
    let template = `
      <div class="popover" role="tooltip">
        <div class="arrow"></div>
        <div class="popover-body"></div>
      </div>
    `;
    $('[data-toggle="popover"]').popover({
      template,
      container: 'body',
      html: true,
      trigger: 'hover',
    });
  }

  function targetingPage(state) {
    doMap();
    return `
      <div>
        <div class="wizard-title">
          <h2>Locations</h2>
        </div>
        <div class="d-flex justify-content-center">
          <div class="subtitle">
            A couple of sentances to provide further detail and instruction
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-lg-12">
            <div class="card">
              <div class="card-body">

                <select id="type" class="custom-select custom-select-lg">
                  <option value="Circle">Circle</option>
                  <option value="Polygon">Geofence</option>
                </select>
                <div style='width:100%;height:40vw' id='map'></div>
              </div>
            </div>
          </div>
        </div>

        <div class="location-input d-flex justify-content-center">
          <input type="text" placeholder="specify a city, town or zip code">
        </div>
      </div>
    `;
  }

  window.selectLayout = function(idx) {
    document
      .querySelector('.selected-layout')
      .classList.remove('selected-layout');
    setState({selectedLayout: idx});
    document
      .querySelector(`label[for=layout-${idx}]`)
      .classList.add('selected-layout');
  }

  function layoutPage(state) {
    return `
      <div class="layout-selection">
        <div class="wizard-title">
          <h2>Select a Layout for your Ad</h2>
        </div>
        <div class="d-flex justify-content-center">
          <div class="subtitle">
            A couple of sentances to provide further detail and instruction
          </div>
        </div>
        <div class="layout-options">
          ${adTypes[state.category].layouts
            .map(
              (layout, i) =>
                `
        <div class="layout-option">
          <input id="layout-${i}" oninput="selectLayout(${i})" type="radio" name="triptych-options" value="${i}" ${
                  i === state.selectedLayout ? 'checked' : ''
                }>
          <label class="layout-preview ${
            i === state.selectedLayout ? 'selected-layout' : ''
          }" for="layout-${i}">
            <img src="${layout.preview}">
          </label>
        </div>
        `,
            )
            .join('')}
        </div>
      </div>
    `;
  }

  let windowWidth = window.innerWidth - 20;
  scale = windowWidth < 640 ? (windowWidth - 20) / 640 : 1;

  window.onresize = function(e) {
    let windowWidth = window.innerWidth - 20;
    scale = windowWidth < 640 ? (windowWidth - 20) / 640 : 1;
    document.querySelector('#triptych-edit').width = 640 * scale;
    document.querySelector('#triptych-edit').height = 225 * scale;
    drawImage(e, state);
    reRenderText();
  }

  function adCreatePage(state) {
    return `
      <div>
        <div class="wizard-title">
          <h2>Create your Ad</h2>
        </div>
        <div class="d-flex justify-content-center mt-4">
          <canvas id="triptych-edit" width="${640 * scale}" height="${225 * scale}">
        </div>
        <div class="d-flex justify-content-center mt-2">
          <textarea type="text" class="triptych-text" placeholder="enter text"></textarea>
          <div class="ml-3">
            <div class="mt-1 color-input">
              <span>
                <input type="color" name="background-color-picker"><label for="background-color-picker">Background</label>
              </span>
              <span class="ml-2">
                <input type="color" name="text-color-picker"><label for="text-color-picker">Text</label>
              </span>
            </div>
            <div class="input-options d-flex justify-content-center">
            </div>
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
    {
      html: categoryPage,
      title: 'Ad Type',
      loadFunc: handlePopover,
    },
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

  function doMap() {
    $.getJSON("http://adcast/api/screens?active=1&removed=0", function(Screens) {
      self._map = map({points:Screens});
      let success = false;

      if(success) {
        _map.load(_campaign.shape_list);
      } else {
        _map.center([-118.34,34.06], 11);
      }
    });
  }

  self.clearmap = () => _map.clear();
  self.removeShape = () => _map.removeShape();

  function geosave() {
    var coords = _map.save();
    // If we click on the map again we should show the updated coords
    _campaign.shape_list = coords;
    post('campaign_update', {id: _id, geofence: coords}, res => {
      show({data: 'Updated Campaign'}, 1000);
    });
  }

  window.showPage = function(pageNum) {
    topRightEls[currentPage].classList.remove('top-bar-selected');
    if (pageNum < 0 || pageNum > pages.length - 1) {
      return;
    }
    backBtn.style.visibility = pageNum === 0 ? 'hidden' : 'visible';
    nextBtn.innerHTML =
      pageNum !== pages.length - 1
        ? 'next<img src="/assets/chevron-right.svg">'
        : 'buy<img src="/assets/chevron-right.svg">';
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

  let savedState = localStorage.getItem('savedState');
  if (savedState) {
    setState(JSON.parse(savedState));
  } else {
    setState(initialState);
  }

  function submit(data) {
    console.log('Submitting: ', data);
  }

  window.onpopstate = function() {
    topRightEls[currentPage].classList.remove('top-bar-selected');
    currentPage = Number(window.location.pathname.split('/').pop());
    showPage(currentPage);
  };

  showPage(currentPage);
  document.querySelector('#back-btn').onclick = () => showPage(currentPage - 1);
})();
