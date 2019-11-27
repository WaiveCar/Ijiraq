function renderCampaigns(campaigns, brand, brandIdx) {
  moment.locale('en');
  let campaignList = document.querySelector('.campaign-list');
  let newEl = document.createElement('div');
  newEl.innerHTML = `
     <h3>${brand}</h3>
    <div class="row card-group ml-1 mb-3" id="brand-${brandIdx}">
      ${campaigns
        .map(
          (campaign, campaignIdx) =>
            `<div class="card mt-1 ml-2">
             <a class="prevent-underline" href="/notices/show?id=${
               campaign.id
             }">
               <div id="asset-container-${brandIdx}-${campaignIdx}" style="height: 135px;"> 
               </div>
               <div class="campaign-title mt-1">${campaign.project}</div>
               <div class="campaign-dates">
                 ${`${moment(campaign.start_time).format(
                   'MMM D',
                 )}   `}<i class="fas fa-play arrow"></i> ${`   ${moment(
              campaign.end_time,
            ).format('MMM D')}`}
               </div>
             </a>
           </div>`,
        )
        .join('')}
    </div>`;
  campaignList.appendChild(newEl);
  campaigns.forEach((campaign, campaignIdx) => {
    let e = Engine({
      container: document.querySelector(
        `#asset-container-${brandIdx}-${campaignIdx}`,
      ),
    });
    e.AddJob({url: campaign.asset});
    e.Start();
  });
}

function groupByBrand(response, brands) {
  let brandTable = {};
  for (let brand of brands) {
    brandTable[brand.id] = brand.name;
  }
  let output = {};
  for (let c of response) {
    if (!output[brandTable[c.brand_id]]) {
      output[brandTable[c.brand_id]] = [];
    }
    output[brandTable[c.brand_id]].push(c);
  }
  return output;
}

(() => {
  var pre = 'http://staging.waivescreen.com';

  fetch(`${pre}/api/campaigns`)
    .then(response => response.json())
    .then(json => {
      renderCampaigns(json, 'Your Notices');
    })
    .catch(e => console.log('error fetching screens', e));
})();
