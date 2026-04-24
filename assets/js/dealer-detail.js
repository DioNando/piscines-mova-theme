document.addEventListener("DOMContentLoaded", function () {
  if (typeof movaDealerData === "undefined" || typeof L === "undefined") return;

  const { lat, lng, nom, adresse, ville, cp, tel, logo } = movaDealerData;

  function getInitials(n) {
    return n.split(/[\s\-\u2013]+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('');
  }
  const mapEl = document.getElementById("mova-dd-map");
  if (!mapEl || !lat || !lng) return;

  const map = L.map("mova-dd-map", {
    scrollWheelZoom: false,
  }).setView([lat, lng], 14);

  L.tileLayer(
    "https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png",
    { attribution: "© OpenStreetMap contributors" },
  ).addTo(map);

  // Marqueur pin
  const iconHtml = logo
    ? `<div class="mova-dd-marker-logo-pin"><div class="mova-dd-marker-logo-inner"><img src="${logo}" alt="" /></div></div>`
    : `<div class="mova-dd-marker-pin"><span>${getInitials(nom)}</span></div>`;

  const iconSize  = logo ? [38, 38] : [30, 30];
  const iconAnchor = logo ? [19, 19] : [15, 30];

  const icon = L.divIcon({
    className: "mova-dd-marker",
    html: iconHtml,
    iconSize,
    iconAnchor,
    popupAnchor: [0, logo ? -22 : -32],
  });

  const adresseLine = [adresse, ville && cp ? `${ville}, ${cp}` : (ville || cp)].filter(Boolean).join('<br>');

  const popupHTML = `
    <div class="mova-map-popup">
      <h5>${nom}</h5>
      ${adresseLine ? `<p>${adresseLine}</p>` : ''}
      ${tel ? `<p style="font-weight:bold; margin-top:-5px;">${tel}</p>` : ''}
      <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank" class="btn-itineraire">Y aller</a>
    </div>
  `;

  L.marker([lat, lng], { icon })
    .addTo(map)
    .bindPopup(popupHTML)
    .openPopup();
});
