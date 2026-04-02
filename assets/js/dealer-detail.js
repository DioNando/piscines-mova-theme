document.addEventListener("DOMContentLoaded", function () {
  if (typeof movaDealerData === "undefined" || typeof L === "undefined") return;

  const { lat, lng, nom } = movaDealerData;
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
  const icon = L.divIcon({
    className: "mova-dd-marker",
    html: `<div class="mova-dd-marker-pin">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z" fill="#fff"/>
      </svg>
    </div>`,
    iconSize: [36, 48],
    iconAnchor: [18, 48],
  });

  L.marker([lat, lng], { icon })
    .addTo(map)
    .bindPopup(`<strong>${nom}</strong>`)
    .openPopup();
});
