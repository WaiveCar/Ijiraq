(() => {
  let postTypes = {
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
      ]
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
      ]
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
      ]
    },
  };

  let initialState = {
    categories: ['announcement', 'promo', 'notice'],
    category: '',
    selectedLayout: 0,
  };
  let state = {};

  function setState(updateObj) {
    console.log('old state', state);
    Object.assign(state, updateObj);
    console.log('new state', state);
  }

  window.setState = setState;
  setState(initialState);

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
        </div>
      </div>
    `;
  }

  function renderOptions() {
    document.querySelector('.layout-options').innerHTML = postTypes[
      state.category
    ].layouts
      .map(
        (layout, i) =>
          `
        <input type="radio" name="triptych-options" value="${i}" ${
            i === 0 ? 'checked' : ''
          }>
        <label for="option${i}">${i}</label>
        `,
      )
      .join('');
  }

  function infoPage(props) {
    return `
      <div>
        Add Info
      </div>
    `;
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
    {html: layoutPage, loadFunc: renderOptions},
    {html: infoPage},
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
  showPage(currentPage);
  document.querySelector('#back-btn').onclick = () => showPage(currentPage - 1);

  
})();
