function renderCampaign(campaign) {
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
}

let campaign = null;

(() => {
  const id = new URL(location.href).searchParams.get('id');
  fetch(`http://staging.waivescreen.com/api/campaigns?id=${id}`)
    .then(response => response.json())
    .then(json => {
      self.j = json;
      campaign = json[0];
      var e = Engine({
        container: document.querySelector('#campaign-preview')
      })
      e.AddJob({url: json[0].asset});
      _preview.AddJob({url: json[0].asset});
      e.Start();
      _preview.Start();
      renderCampaign(json[0]);
      handleUploads(json[0].asset)
    })
    .catch(e => console.log('error fetching screens', e));
})();

