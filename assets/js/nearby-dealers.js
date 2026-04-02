document.addEventListener("DOMContentLoaded", function () {
  const carousel = document.getElementById("mova-nd-carousel");
  if (!carousel) return;

  const prevBtn = document.querySelector(".mova-nd-arrow-prev");
  const nextBtn = document.querySelector(".mova-nd-arrow-next");

  // Largeur de défilement = largeur d'une card + gap
  function getScrollStep() {
    const card = carousel.querySelector(".mova-nd-card");
    if (!card) return 300;
    const style = getComputedStyle(carousel);
    const gap = parseInt(style.gap) || 20;
    return card.offsetWidth + gap;
  }

  function updateArrows() {
    if (!prevBtn || !nextBtn) return;
    prevBtn.disabled = carousel.scrollLeft <= 5;
    nextBtn.disabled =
      carousel.scrollLeft + carousel.offsetWidth >= carousel.scrollWidth - 5;
  }

  if (prevBtn) {
    prevBtn.addEventListener("click", function () {
      carousel.scrollBy({ left: -getScrollStep(), behavior: "smooth" });
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener("click", function () {
      carousel.scrollBy({ left: getScrollStep(), behavior: "smooth" });
    });
  }

  carousel.addEventListener("scroll", updateArrows, { passive: true });
  updateArrows();
});
