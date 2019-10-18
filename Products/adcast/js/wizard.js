(() => {
  let initialState = {
    categories: ['announcement', 'promo', 'notice'],
    category: '',
  }
  let state = {};

  function setState(updateObj) {
    console.log('old state', state);
    Object.assign(state, updateObj);
    console.log('new state', state);
  }

  window.setState = setState;
  setState(initialState);

  function categoryPage(props) {
    this.setState = setState;
    return `
      <div>
        Select Category
        ${props.categories.map(cat => `
          <div oninput="setState({category : '${cat}'})">
            <input type="radio" name="category" value="${cat}" ${props.category === cat ? 'checked' : ''}>
            <label for="${cat}">${cat}</label>
          </div>
        `
        ).join('')}
      </div>
    `
  }

  function targetingPage(props) {
    return `
      <div>
        Select Targeting
      </div>
    `
  }

  function layoutPage(props) {
    return `
      <div>
        Select Layout
      </div>
    `
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
    `
  }

  function summaryPage(props) {
    return `
      <div>
        Summary
      </div>
    `
  }

  function paymentPage(props) {
    return `
      <div>
        Payment
      </div>
    `

  }
  
  let pages = [
    categoryPage,
    targetingPage,
    layoutPage,
    infoPage,
    budgetPage,
    summaryPage,
    paymentPage,
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
    document.querySelector('#anchor').innerHTML = pages[pageNum](state);
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
