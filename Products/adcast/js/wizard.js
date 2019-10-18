(() => {
  function categoryPage(props) {
    return `
      <div>
        Select Category
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

  let initialState = {
    category: '',
  }

  let currentPage = Number(window.location.pathname.split('/').pop());
  let backBtn = document.querySelector('#back-btn');
  let nextBtn = document.querySelector('#next-btn');
  let state = Object.assign({}, initialState);

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
