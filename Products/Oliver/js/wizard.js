(() => {
  document.querySelector('.navbar-nav').classList.add('in-wizard');
  let categories = ['promo', 'notice', 'other'];
  let initialState = {
    category: categories[0],
    selectedLayout: 0,
    backgroundColor: 'white',
    textColor: 'black',
    preferredContact: 'email',
    title: '',
    startDate: '',
    endDate: '',
    canvasText: '',
    keywords: [],
    imageSrc: null,
    sampleImageUsed: true,
    finalImageSrc: null,
    description: '',
    amount: 1000,
  };
  let state = {};
  let categoryTips = {
    promo: 'Increase your business',
    notice: 'Events and announcements, ie. lost pet',
    other: 'Art, fun and leisure',
  };

  window.setState = function(updateObj) {
    Object.assign(state, updateObj);
    localStorage.setItem('savedState', JSON.stringify(state));
  };

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
  };

  function capitalize(word) {
    return word[0].toUpperCase() + word.slice(1);
  }

  function post(ep, body, cb) {
    fetch(
      new Request(`http://adcast/api/${ep}`, {
        method: 'POST',
        body: JSON.stringify(body),
      }),
    )
      .then(res => {
        if (res.status === 200) {
          return res.json();
        }
      })
      .then(cb);
  }

  function categoryPage(state) {
    return `
      <div>
        <div class="wizard-title">
          <h2>Message Type</h2>
        </div>
        <div class="d-flex justify-content-center">
          <div class="subtitle">
            What is the purpose of your message?
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
    setTimeout(doMap, 100);
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

        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <div id="map"></div>
            </div>
          </div>
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
      .querySelector(`label[for=layout-${idx}] img`)
      .classList.add('selected-layout');
  };

  function layoutPage(state) {
    return `
      <div>
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
                    <input
                      id="layout-${i}"
                      oninput="selectLayout(${i})"
                      type="radio"
                      name="triptych-options"
                      value="${i}"
                      ${i === state.selectedLayout ? 'checked' : ''}
                    />
                    <label class="layout-preview" for="layout-${i}">
                      <img
                        src="${layout.preview}"
                        class="${
                          i === state.selectedLayout ? 'selected-layout' : ''
                        }"
                        for="layout-${i}"
                      />
                    </label>
                  </div>
                `,
            )
            .join('')}
        </div>
      </div>
    `;
  }

  window.deleteKeyword = function(i) {
    state.keywords.splice(i, 1);
    setState({keywords: state.keywords});
    document.querySelector('.keywords').innerHTML = renderKeywords();
  };

  let windowWidth = window.innerWidth - 20;
  scale = windowWidth < 640 ? (windowWidth - 20) / 640 : 1;

  function adCreatePage(state) {
    window.onresize = function(e) {
      let windowWidth = window.innerWidth - 20;
      scale = windowWidth < 640 ? (windowWidth - 20) / 640 : 1;
      document.querySelector('#triptych-edit').width = 640 * scale;
      document.querySelector('#triptych-edit').height = 225 * scale;
      drawImage(e, state);
      reRenderText();
    };
    return `
      <div>
        <div class="wizard-title">
          <h2>Create your Notice</h2>
        </div>
        <div class="title-input d-flex justify-content-center mt-4">
          <input type="text" placeholder="Notice Title *" required>
        </div>
        <div class="d-flex justify-content-center mt-4">
          <label for="start-date" class="mr-2">Start Date:</label><input class="ad-date start-date" id="start-date" type="date">
        </div>
        <div class="d-flex justify-content-center mt-4">
          <canvas id="triptych-edit" width="${640 * scale}" height="${225 *
      scale}">
        </div>
        <div class="d-flex justify-content-between mt-4 ad-input-holder">
          <textarea type="text" class="triptych-text" placeholder="Notice Text *" required></textarea>
          <div class="ml-3 right-inputs">
            <div class="color-input">
              <span>
                <input type="color" name="background-color-picker"><label for="background-color-picker">Background</label>
              </span>
              <span class="ml-2">
                <input type="color" name="text-color-picker"><label for="text-color-picker">Text</label>
              </span>
            </div>
            <div class="mobile-flex-center file-holder">
              <label class="input-options">
              </label>
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

    let titleInput = document.querySelector('.title-input input');
    titleInput.value = state.title;
    titleInput.oninput = function(e) {
      setState({title: e.target.value});
    };

    let startDate = document.querySelector('.start-date');
    startDate.valueAsDate = state.startDate.length
      ? new Date(state.startDate)
      : new Date();
    startDate.oninput = function(e) {
      setState({startDate: e.target.value});
    };

    let triptychText = document.querySelector('.triptych-text');
    triptychText.value = state.canvasText;
    triptychText.oninput = function(e) {
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
      window.onresize = null;
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

  function infoPage(state) {
    return `
      <div class="info-holder">
        <div class="wizard-title">
          <h2>Your Info</h2>
        </div>
        <div class="keyword-input d-flex justify-content-center mt-4">
          <input type="text" placeholder="Add Keywords">
          <button class="btn add-keyword">Add</button>
        </div>
        <div class="keywords d-flex justify-content-center mt-2">
          ${renderKeywords()}
        </div>
        <div class="payment-holder mt-3">
          <div class="inner-payment">
            <h4>
              Contact
            </h4>
            ${formFields(
              [
                ['businessName', 'Business Name', false],
                ['phone', 'Phone', true],
                ['email', 'E-mail', true],
              ],
              true,
            )}
            <h4 class="mt-2">
              Preferred Contact
            </h4>
            <div class="d-flex justify-content-around">
              ${['email', 'phone', 'text']
                .map(
                  type =>
                    `
                  <div>
                    <input type="radio" 
                      id="prefer-${type}" 
                      name="preferredContact" 
                      oninput="setState({preferredContact: '${type}'})"
                      ${state.preferredContact === type ? 'checked' : ''}
                    >
                    <label for="prefer-${type}">${capitalize(type)}</label>
                  </div>
                `,
                )
                .join('')}
            </div>
          </div>
          <div class="inner-payment">
            <h4>
              Business Address
            </h4>
            ${formFields(
              [
                ['businessStreet', 'Street', false],
                ['businessCity', 'City', false],
                ['businessState', 'State', false],
                ['businessZip', 'Zip Code', false],
              ],
              true,
            )}
          </div>
        </div>
        <div>
          <h4 class="text-center mt-4 black-title">
            Does your notice contain any of the following restricted content types?
          </h4>
          <div class="d-flex justify-content-center mt-2">
            <div class="d-flex justify-content-around checkboxes">
              ${[
                ['adultContent', 'Adult Content'],
                ['marijuana', 'Marijuana'],
                ['alcohol', 'Alcohol'],
              ]
                .map(
                  ([propName, item]) => `
                    <div>
                      <input
                        id="checkbox-${propName}"
                        name="checkbox-${propName}"
                        type="checkbox"
                        ${state[propName] ? 'checked' : ''}
                        oninput="setState({${propName}: this.checked})"
                      />
                      <label for="checkbox-${propName}">${item}</label>
                    </div>
                  `,
                )
                .join('')}
            </div>
          </div>
        </div>
        <div class="mt-3 d-flex justify-content-center">
          <textarea class="description triptych-text"
            placeholder="Notice Description *"
            oninput="setState.call(this, {'description': event.target.value})"
            required
          >${state.description || ''}</textarea>
        </div>
      </div>
    `;
  }

  function infoLoad() {
    let addKeyword = document.querySelector('.add-keyword');
    let keywordInput = document.querySelector('.keyword-input input');
    let keywords = document.querySelector('.keywords');
    addKeyword.onclick = function() {
      if (
        keywordInput.value &&
        state.keywords.length < 3 &&
        !state.keywords.includes(keywordInput.value)
      ) {
        setState({keywords: [...state.keywords, keywordInput.value]});
        document.querySelector('.keywords').innerHTML = renderKeywords();
        keywordInput.value = '';
      }
    };
  }

  function formFields(fields, addOnInput) {
    return fields
      .map(
        field => `
          <div>
            <input type="text" ${field[2] ? 'required' : ''} placeholder="${
          field[1]
        } ${field[2] ? '*' : ''}" name="${field[0]}" ${
          addOnInput
            ? `oninput="setState({${field[0]}: this.value})" value=${state[
                field[0]
              ] || ''}`
            : ''
        }>
          </div>
        `,
      )
      .join('');
  }

  function renderKeywords() {
    return state.keywords
      .map(
        (word, i) =>
          `
            <div class="btn keyword" onclick="deleteKeyword(${i})">
              ${word}
            </div>
          `,
      )
      .join('');
  }

  function summaryPage(state) {
    return state.finalImageSrc && state.email
      ? `
      <div>
        <div class="wizard-title">
          <h2>Summary</h2>
        </div>
        <div class="summary-holder mt-4">
          <div class="inner-summary">
            <h4 class="mt-4">Ad Type</h4>
            <h2 class="summary-title">${capitalize(state.category)}</h2>
            <h4 class="mt-2">Locations</h4>
            <div class="mb-2">
              ${['one', 'two', 'three']
                .map(
                  location =>
                    `
                    <div class="btn add-keyword">
                      ${location}
                    </div>
                  `,
                )
                .join('')}
            </div>
            <div>
              <h4 class="mt-4">Price</h4>
              <h2 class="summary-title">$**.**</h2>
            </div>
          </div>
          <div class="inner-summary">
            <h4 class="mt-4">Active Dates</h4>
            ${
              state.startDate
                ? `
                    <h2 class="summary-title">
                      ${moment(state.startDate).format('MM/DD/YYYY')} 
                      ${
                        state.endDate
                          ? `
                      to ${moment(state.endDate).format('MM/DD/YYYY')}
                      `
                          : ''
                      }
                    </h2>
                  `
                : '<h2 class="summary-title">For the next week.</h2>'
            }
            <div>
              <h4 class="mt-2">Keywords</h4>
              <div class="mb-2">
                ${renderKeywords() ? renderKeywords() : 'No Keywords Entered'}
              </div>
            </div>
            <h4 class="mt-4">Content</h4>
            <div>
              <img src="${state.finalImageSrc}" class="summary-preview">
            </div>
          </div>
        </div>
      </div>
    `
      : `
      <div>
        <div class="wizard-title">
        </div>
        <h4 class="mt-4">Please go back, create a notice and enter your contact information before attempting to continue</h4>
      </div>
    `;
  }

  function paymentPage(state) {
    return state.finalImageSrc && state.email
      ? `
      <div class="payment-page">
        <div class="wizard-title">
          <h2>Payment</h2>
        </div>
        <form class="payment-form mt-4">
          <div class="payment-holder">
            <div class="inner-payment">
              <h4>
                Card
              </h4>
              ${formFields([
                ['name', 'Name on Card', true],
                ['number', 'Card Number', true],
                ['expMonth', 'Expiration Month', true],
                ['expYear', 'Expiration Year', true],
                ['cvv', 'Security Code', true],
              ])}
            </div>
          </div>
          <!--<div class="d-flex justify-content-center save-method">
            <div>
              <input class="form-check-input" type="checkbox" name="saveMethod" id="saveMethod">
              <label class="form-check-label" for="saveMethod">
                Save this method
              </label>
            </div>
          </div>-->
        </form>
        <div class="d-flex justify-content-center">
          <button class="btn add-keyword buy-btn">Complete Purchase</button>
        </div>
      </div>`
      : '';
  }

  function attachSubmit() {
    let buyBtn = document.querySelector('.buy-btn');
    if (buyBtn) {
      buyBtn.onclick = submit;
    }
    if (!state.finalImageSrc) {
      requestAnimationFrame(() => showPage(5));
      showErrorModal(
        'Missing Required Items',
        'Please go back and create a notice before attempting to continue',
      );
    }
  }

  function purchaseComplete() {
    return `
      <div class="payment-page">
        <div class="wizard-title">
          <h2>Purchase Complete</h2>
        </div>
      </div>`
  }

  function afterPurchase() {
    backBtn.style.visibility = 'hidden';
    nextBtn.style.visibility = 'hidden';
    document.querySelector('.top-bar-right').innerHTML = '';
  }

  let pages = [
    {
      html: categoryPage,
      title: 'Type',
      loadFunc: handlePopover,
    },
    {
      html: targetingPage,
      title: 'Locations',
    },
    {html: layoutPage, title: 'Layout'},
    {html: adCreatePage, title: 'Edit', loadFunc: adCreateLoad},
    {html: infoPage, title: 'Info', loadFunc: infoLoad},
    {html: summaryPage, title: 'Summary'},
    {html: paymentPage, title: 'Payment', loadFunc: attachSubmit},
    {html: purchaseComplete, title: 'Payment', loadFunc: afterPurchase},
  ];

  let currentPage = Number(window.location.pathname.split('/').pop());
  let backBtn = document.querySelector('#back-btn');
  let nextBtn = document.querySelector('#next-btn');

  self.doMap = function() {
    var center = [-118.33, 34.09];
    self._map = map({
      selectFirst: true,
      draw: false,
      resize: false,
      zoom: 11,
      center,
    });
    _map.load([['Circle', center, 2500]]);
  };

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

  window.showPage = function(pageNum, isNext) {
    if (isNext) {
      let missing = verifyData();
      if (missing.length) {
        console.log('form data missing', missing);
        return;
      }
    }
    if (topRightEls[currentPage]) {
      topRightEls[currentPage].classList.remove('top-bar-selected');
    }
    if (pageNum < 0 || pageNum > pages.length - 1) {
      return;
    }
    backBtn.style.visibility = pageNum === 0 ? 'hidden' : 'visible';
    nextBtn.innerHTML =
      pageNum !== pages.length - 2
        ? 'next<img src="/assets/chevron-right.svg">'
        : 'buy<img src="/assets/chevron-right.svg">';
    nextBtn.onclick =
      pageNum !== pages.length - 2
        ? () => showPage(currentPage + 1, true)
        : () => submit();
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
        window.location.origin + '/notices/wizard/' + pageNum,
      );
    }
    currentPage = pageNum;
    if (topRightEls[currentPage])  {
      topRightEls[currentPage].classList.add('top-bar-selected');
    }
    progressEls.forEach((el, idx) => {
      if (idx <= currentPage) {
        el.classList.add('progress-filled');
      } else {
        el.classList.remove('progress-filled');
      }
    });
  };

  let topRight = document.querySelector('.top-bar-right');
  topRight.innerHTML = pages.slice(0, -1)
    .map(
      (page, idx) => `
        <div class="top-bar-link ${
          idx === currentPage ? 'top-bar-selected' : ''
        }" onclick="showPage(${idx}, true)">${page.title}</div>
      `,
    )
    .join('');
  let topRightEls = document.querySelectorAll('.top-bar-right .top-bar-link');

  let progress = document.querySelector('.progress');
  progress.innerHTML = pages
    .map(
      (page, idx) =>
        `<div class="bar-section ${
          idx <= currentPage ? 'progress-filled' : ''
        }" style="width: ${100 * (1 / pages.length)}%">
      </div>`,
    )
    .join('');
  let progressEls = document.querySelectorAll('.bar-section');

  let savedState = localStorage.getItem('savedState');
  if (savedState) {
    setState(JSON.parse(savedState));
  } else {
    setState(initialState);
  }

  function verifyData() {
    let requiredInputs = document.querySelectorAll(
      'input[required], textarea[required]',
    );
    let missing = [];
    requiredInputs.forEach(input => {
      if (!input.value) {
        input.classList.add('required');
        missing.push(input.placeholder);
      }
    });
    if (
      currentPage === 3 &&
      adTypes[state.category].layouts[state.selectedLayout].hasImage &&
      state.sampleImageUsed
    ) {
      document.querySelector('.input-options').classList.add('required-upload');
      missing.push('Image Upload');
    }
    if (missing.length) {
      showErrorModal(
        'Missing Required Items',
        `Please fill in the following required items before continuing: ${missing
          .map(item => item.replace(' *', ''))
          .join(', ')}.`,
      );
    }
    return missing;
  }

  function dataURLtoBlob(dataURI) {
    let byteString = atob(dataURI.split(',')[1]);
    let ab = new ArrayBuffer(byteString.length);
    let ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
    }
    return new Blob([ab], {type: 'image/jpeg'});
  }

  function submit() {
    let missing = verifyData();
    if (missing.length) {
      return;
    }
    let formData = new FormData(document.querySelector('.payment-form'));
    for (let field in state) {
      if (field !== 'finalImageSrc') {
        formData.append(field, state[field]);
      } else {
        formData.append('file1', dataURLtoBlob(state[field]));
      }
    }
    axios({
      method: 'post',
      url: '/buy',
      data: formData,
      config: {
        headers: {'Content-Type': 'multipart/form-data'},
      },
    })
      .then(response => {
        showPage(7);
      })
      .catch(e =>
        showErrorModal('Error Purchasing Notice', e.response.data.message),
      );
  }

  window.onpopstate = function() {
    topRightEls[currentPage].classList.remove('top-bar-selected');
    currentPage = Number(window.location.pathname.split('/').pop());
    showPage(currentPage);
  };

  showPage(currentPage);
  document.querySelector('#back-btn').onclick = () => showPage(currentPage - 1);
})();
