(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var body = document.body;

    if (!body) {
      return;
    }

    var activeClass = "wf_mobilenav-mainmenu--active";
    var lockClass = "devhub-mobile-menu-locked";
    var scrollStateKey = "devhubMobileMenuScrollY";

    function lockScroll() {
      if (body.classList.contains(lockClass)) {
        return;
      }

      var scrollY =
        window.scrollY ||
        window.pageYOffset ||
        document.documentElement.scrollTop ||
        0;

      body.dataset[scrollStateKey] = String(scrollY);
      body.classList.add(lockClass);
      body.style.position = "fixed";
      body.style.top = "-" + scrollY + "px";
      body.style.left = "0";
      body.style.right = "0";
      body.style.width = "100%";
    }

    function unlockScroll() {
      if (!body.classList.contains(lockClass)) {
        return;
      }

      var scrollY = parseInt(body.dataset[scrollStateKey] || "0", 10) || 0;

      body.classList.remove(lockClass);
      body.style.position = "";
      body.style.top = "";
      body.style.left = "";
      body.style.right = "";
      body.style.width = "";
      delete body.dataset[scrollStateKey];

      window.scrollTo(0, scrollY);
    }

    function syncMenuScrollLock() {
      if (body.classList.contains(activeClass)) {
        lockScroll();
        return;
      }

      unlockScroll();
    }

    var observer = new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i += 1) {
        if (mutations[i].attributeName === "class") {
          syncMenuScrollLock();
          return;
        }
      }
    });

    observer.observe(body, {
      attributes: true,
      attributeFilter: ["class"],
    });

    window.addEventListener("pagehide", unlockScroll);
    syncMenuScrollLock();
  });
})();
