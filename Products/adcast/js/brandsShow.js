function renderCampaigns(campaigns) {
  moment.locale('en');
  let campaignList = document.querySelectorAll('.campaign-list');
  campaignList.forEach(
    (node, listIdx) =>
      (node.innerHTML = campaigns
        .map(
          (campaign, campaignIdx) =>
            `<div class="card mt-1 ml-2">
           <a class="prevent-underline" href="/campaigns/show?id=${
             campaign.id
           }">
             <div id="asset-container-${listIdx}-${campaignIdx}" style="height: 113px"> 
             </div>
             <div class="campaign-title mt-1">${campaign.project}</div>
             <div class="campaign-dates">
               ${`${moment(campaign.start_time).format(
                 'MMM D',
               )}   `}<i class="fas fa-play arrow"></i> ${`   ${moment(
              campaign.end_time,
            ).format('MMM D')}`}
             </div>
             <div class="user-icon-holder">
               <img src="../../svg/user-icon.svg" class="user-icon">
               <img src="../../svg/user-icon.svg" class="user-icon">
               <img src="../../svg/user-icon.svg" class="user-icon">
               <img src="../../svg/user-icon.svg" class="user-icon">
             </div>
           </a>
         </div>`,
        )
        .join('')),
  );
  campaignList.forEach((node, listIdx) =>
    campaigns.forEach((campaign, campaignIdx) => {
      let e = Engine({
        container: document.querySelector(
          `#asset-container-${listIdx}-${campaignIdx}`,
        ),
      });
      e.AddJob({url: campaign.asset});
      e.Start();
    }),
  );
}

(() => {
  const id = new URL(location.href).searchParams.get('id');
  document.querySelector('.brand-title').innerHTML = `Brand ${id}`
  fetch(`http://waivescreen.com/api/campaigns?brand_id=${id}`)
    .then(response => response.json())
    .then(json => renderCampaigns(json))
    .catch(e => console.log('error fetching brand', e));
})();