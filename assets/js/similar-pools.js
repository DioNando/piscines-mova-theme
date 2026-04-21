document.addEventListener("DOMContentLoaded", function () {
  const carousel = document.getElementById("mova-sp-carousel");
  if (!carousel) return;

  const prevBtn = carousel
    .closest(".mova-sp-carousel-wrap")
    .querySelector(".mova-sp-arrow-prev");
  const nextBtn = carousel
    .closest(".mova-sp-carousel-wrap")
    .querySelector(".mova-sp-arrow-next");

  function getScrollStep() {
    const slide = carousel.querySelector(".mova-sp-slide");
    if (!slide) return 300;
    const gap = parseInt(getComputedStyle(carousel).gap) || 20;
    return slide.offsetWidth + gap;
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
  window.addEventListener("resize", updateArrows, { passive: true });
  window.addEventListener("load", updateArrows);
  updateArrows();
});
