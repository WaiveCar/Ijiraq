let server = 'staging.waivescreen.com';
let maps = {};
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

  const id = new URL(location.href).searchParams.get('id');
  if(!id) { return }
  map.location = map({ 
    opacity: 0.7,
    tiles: 'stamen.toner',
    target: 'location-map' });
  fetch(`http://${server}/api/path?id=${id}`)
    .then(response => response.json())
    .then(points => {
      map.location.clear();
      map.location.load(points.map(row => ["Line", row]));
      map.location.fit();
      
    });
  map.boost = map({ 
    target: 'boost-map',
    opacity: 0.7,
    tiles: 'stamen.toner',
  });
  fetch(`http://${server}/api/purchases?campaign_id=${id}`)
    .then(response => response.json())
    .then(json => {
      console.log(json);
    });

  fetch(`http://${server}/api/campaigns?id=${id}`)
    .then(response => response.json())
    .then(json => {
      campaign = json[0];
      Engine({
        container: document.querySelector('#campaign-preview'),
        fallback: json[0]
      }).Start();
      campaign.duration_seconds = campaign.duration_seconds || 7.5;

      // this means the campaign lapsed over 1 day ago.
      if(+new Date(campaign.end_time) + 60*60*24*1000 < +new Date()) {
        document.querySelector('#extend').innerHTML = "Renew";
      }
      
      document.querySelector('.stat .start').innerHTML = campaign.start_time.split(' ')[0];
      document.querySelector('.stat .end').innerHTML = campaign.end_time.split(' ')[0];
      document.querySelector('.boost.count').innerHTML = Math.floor(campaign.completed_seconds / campaign.duration_seconds);
      document.querySelector('.play.count').innerHTML = Math.floor(campaign.completed_seconds / campaign.duration_seconds);
      self.c = campaign;
      renderCampaign(campaign);
      map.boost.load(campaign.shape_list);
      map.boost.fit();
    })
    .catch(e => console.log('error fetching screens', e));

})();

