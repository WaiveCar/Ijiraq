let server = 'staging.waivescreen.com';
function renderCampaign(campaign) {
  /*
  document.querySelector('.campaign-show-title').textContent =
    campaign.project[0].toUpperCase() + campaign.project.slice(1);
  document.querySelector('.campaign-dates').innerHTML = `${`${moment(
    campaign.start_time,
  ).format('MMM D')}   `}<i class="fas fa-play arrow"></i> ${`   ${moment(
    campaign.end_time,
  ).format('MMM D')}`}`;
  document.querySelector('#start-date').value = campaign.start_time.split(
    ' ',
  )[0];
  document.querySelector('#end-date').value = campaign.end_time.split(' ')[0];
  */
}

function calcItems() {
  requestAnimationFrame(() => {
    let schedule = JSON.parse($('#schedule').jqs('export'));
    let minutesPerWeek = schedule.reduce((acc, item) => {
      return (
        acc +
        item.periods.reduce((acc, period) => {
          return (
            acc +
            moment(period.end, 'hh:mm').diff(
              moment(period.start, 'hh:mm'),
              'minutes',
            )
          );
        }, 0)
      );
    }, 0);
    let budget = document.querySelector('#budget').value;
    let fakeNumImpressionsPerWeek = budget * 14.32;
    let fakeCPM = (fakeNumImpressionsPerWeek / budget / 100).toFixed(2);
    if (budget) {
      document.querySelector('#budget').textContent = `$${budget}`;
      document.querySelector('#cpm').textContent = `$${fakeCPM}`;
      document.querySelector(
        '#impressions',
      ).textContent = `${fakeNumImpressionsPerWeek}`;
    }
  });
}

let campaign = null;

let selectedLinkIdx = 0;
let topBarRight = document.querySelector('.top-bar-right');

function changeSelected(newIdx) {
  topBarRight.children[selectedLinkIdx].classList.remove('top-bar-selected');
  selectedLinkIdx = newIdx;
  topBarRight.children[selectedLinkIdx].classList.add('top-bar-selected');
}

(() => {
  document.querySelector('#campaign-url').innerHTML = `URL: ${window.location.href}`;
  topBarRight.innerHTML = [
    'Overview',
    'Locations',
    'Boost Zone',
    'Billing',
  ]
    .map(
      (item, i) => `
    <a href="#${item}">
      <div class="top-bar-link" onclick="changeSelected(${i})">
        ${item}
      </div>
    </a>
  `,
    )
    .concat(['<div class="top-bar-link update-campaign-btn p-manager">Update</div>'])
    .join('');
  topBarRight.children[selectedLinkIdx].classList.add('top-bar-selected');

  window.addEventListener('hashchange', function() {
    window.scrollTo(window.scrollX, window.scrollY - 50);
  });

  const id = new URL(location.href).searchParams.get('id');
  fetch(`http://${server}/api/campaigns?id=${id}`)
    .then(response => response.json())
    .then(json => {
      self.j = json;
      campaign = json[0];
      Engine({
        container: document.querySelector('#campaign-preview'),
        fallback: json[0]
      }).Start();
      renderCampaign(json[0]);
      handleUploads(json[0].asset)
    })
    .catch(e => console.log('error fetching screens', e));
})();

