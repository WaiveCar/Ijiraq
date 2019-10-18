(() => {
  let pages = {
    '/category': html`
      <div>
        Select Category
      </div>
    `,
    '/targeting': html`
      <div>
        Select Targeting
      </div>
    `,
    '/layout': html`
      <div>
        Select Layout
      </div>
    `,
    '/info': html`
      <div>
        Add Info
      </div>
    `,
    '/budget': html`
      <div>
        Edit Budget
      </div>
    `,
    '/summary': html`
      <div>
        Summary,
      </div>
    `,
    '/payment': html`
      <div>
        Payment
      </div>
    `,
  };
  document.querySelector('#anchor').innerHTML = 'wizard';
})();
