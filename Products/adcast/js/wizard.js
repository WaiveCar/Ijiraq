(() => {
  let pages = [
    `
      <div>
        Select Category
      </div>
    `,
    `
      <div>
        Select Targeting
      </div>
    `,
    `
      <div>
        Select Layout
      </div>
    `,
    `
      <div>
        Add Info
      </div>
    `,
    `
      <div>
        Edit Budget
      </div>
    `,
    `
      <div>
        Summary
      </div>
    `,
    `
      <div>
        Payment
      </div>
    `,
  ];

  let currentPage = Number(window.location.pathname.split('/').pop());

  function showPage(pageNum) {
    if (pageNum < 0 || pageNum > pages.length - 1) {
      return;
    }
    document.querySelector('#anchor').innerHTML = pages[pageNum];
    if (currentPage !== pageNum) {
      window.history.pushState({}, pageNum, window.location.origin + '/campaigns/wizard/' +  pageNum)
    }
    currentPage = pageNum;
  }
  showPage(currentPage);
  
  document.querySelector('#forward-btn').onclick = function() {
    showPage.call(this, currentPage + 1);
  }
  document.querySelector('#back-btn').onclick = function() {
    showPage.call(this, currentPage - 1);
  }

})();
