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
  let backBtn = document.querySelector('#back-btn');
  let nextBtn = document.querySelector('#next-btn');
  let adData = {};

  function showPage(pageNum) {
    if (pageNum < 0 || pageNum > pages.length - 1) {
      return;
    }
    backBtn.style.visibility = pageNum === 0 ? 'hidden' : 'visible';
    nextBtn.textContent = pageNum !== pages.length - 1 ? 'Next' : 'Submit';
    nextBtn.onclick =
      pageNum !== pages.length - 1
        ? () => showPage(currentPage + 1)
        : () => submit(adData);
    document.querySelector('#anchor').innerHTML = pages[pageNum];
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
