(() => {
  let pages = {
    category: `
      <div>
        Select Category
      </div>
    `,
    targeting: `
      <div>
        Select Targeting
      </div>
    `,
    layout: `
      <div>
        Select Layout
      </div>
    `,
    info: `
      <div>
        Add Info
      </div>
    `,
    budget: `
      <div>
        Edit Budget
      </div>
    `,
    summary: `
      <div>
        Summary,
      </div>
    `,
    payment: `
      <div>
        Payment
      </div>
    `,
  };

  console.log(window.location.pathname.split('/').pop());
  document.querySelector('#anchor').innerHTML = pages[window.location.pathname.split('/').pop()];
})();
