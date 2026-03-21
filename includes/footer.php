<?php
$footerPrefix = (strpos(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/admin/') !== false || strpos(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/pages/') !== false || strpos(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/api/') !== false) ? '../' : '';
?>
<footer class="mt-auto border-t border-slate-800 bg-black/20">
  <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 text-sm text-slate-400 sm:px-6 md:grid-cols-[1.3fr_.7fr] md:items-start">
    <div class="flex items-start gap-3">
      <img src="<?php echo $footerPrefix; ?>assets/logo-mark.svg" alt="BGMI Scrims" class="mt-0.5 h-9 w-9 rounded-xl border border-slate-800 bg-slate-900 p-1">
      <div>
        <div class="leading-6">BGMI Scrims platform for upcoming matches, quick joins, live rooms, and leaderboards.</div>
        <div class="mt-1 text-xs text-slate-500">Copyright &copy; <?php echo date('Y'); ?> BGMI Scrims. All rights reserved.</div>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-3 text-sm sm:max-w-sm md:ml-auto md:w-full">
      <a href="<?php echo $footerPrefix; ?>pages/scrims.php" class="rounded-2xl bg-slate-900 px-4 py-3 text-center hover:text-amber-300">Scrims</a>
      <a href="<?php echo $footerPrefix; ?>pages/leaderboard.php" class="rounded-2xl bg-slate-900 px-4 py-3 text-center hover:text-amber-300">Leaderboard</a>
      <a href="<?php echo $footerPrefix; ?>pages/register.php" class="rounded-2xl bg-slate-900 px-4 py-3 text-center hover:text-amber-300">Register</a>
      <a href="<?php echo $footerPrefix; ?>pages/login.php" class="rounded-2xl bg-slate-900 px-4 py-3 text-center hover:text-amber-300">Login</a>
    </div>
  </div>
</footer>
<style>
  .mobile-select-native {
    position: absolute;
    inset: 0;
    opacity: 0;
    pointer-events: none;
  }

  .mobile-select-shell {
    position: relative;
  }

  .mobile-select-button {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    border-radius: 1rem;
    border: 1px solid rgb(51 65 85);
    background: rgb(2 6 23);
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    color: rgb(248 250 252);
  }

  .mobile-select-button:focus-visible {
    outline: 2px solid rgb(251 191 36);
    outline-offset: 2px;
  }

  .mobile-select-label {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .mobile-select-chevron {
    flex-shrink: 0;
    width: 0.6rem;
    height: 0.6rem;
    border-right: 2px solid rgb(203 213 225);
    border-bottom: 2px solid rgb(203 213 225);
    transform: rotate(45deg);
    transition: transform 160ms ease;
  }

  .mobile-select-shell[data-open="true"] .mobile-select-chevron {
    transform: rotate(-135deg) translateY(-2px);
  }

  .mobile-select-menu {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 0.4rem);
    z-index: 60;
    display: none;
    max-height: min(16rem, 48vh);
    overflow: auto;
    border-radius: 1rem;
    border: 1px solid rgb(51 65 85);
    background: rgb(2 6 23);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
  }

  .mobile-select-shell[data-open="true"] .mobile-select-menu {
    display: block;
  }

  .mobile-select-option {
    display: block;
    width: 100%;
    border: 0;
    border-bottom: 1px solid rgba(51, 65, 85, 0.55);
    background: transparent;
    padding: 0.9rem 1rem;
    text-align: left;
    font-size: 0.95rem;
    color: rgb(248 250 252);
  }

  .mobile-select-option:last-child {
    border-bottom: 0;
  }

  .mobile-select-option[data-selected="true"] {
    background: rgba(245, 158, 11, 0.16);
    color: rgb(252 211 77);
    font-weight: 600;
  }

  @media (min-width: 768px) {
    .mobile-select-native {
      position: static;
      inset: auto;
      opacity: 1;
      pointer-events: auto;
    }

    .mobile-select-button,
    .mobile-select-menu {
      display: none !important;
    }
  }
</style>
<script>
(() => {
  const isDesktop = () => window.matchMedia("(min-width: 768px)").matches;

  const enhanceSelect = (select) => {
    if (select.dataset.mobileEnhanced === "true") return;
    if (select.multiple) return;

    const shell = document.createElement("div");
    shell.className = "mobile-select-shell";
    shell.dataset.open = "false";

    const button = document.createElement("button");
    button.type = "button";
    button.className = "mobile-select-button";
    button.innerHTML = '<span class="mobile-select-label"></span><span class="mobile-select-chevron"></span>';

    const menu = document.createElement("div");
    menu.className = "mobile-select-menu";

    const syncOptions = () => {
      menu.innerHTML = "";
      const selectedOption = select.options[select.selectedIndex];
      button.querySelector(".mobile-select-label").textContent = selectedOption ? selectedOption.textContent : "Select";

      Array.from(select.options).forEach((option) => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "mobile-select-option";
        item.textContent = option.textContent;
        item.dataset.selected = option.selected ? "true" : "false";
        item.disabled = option.disabled;
        item.addEventListener("click", () => {
          select.value = option.value;
          select.dispatchEvent(new Event("change", { bubbles: true }));
          shell.dataset.open = "false";
          syncOptions();
        });
        menu.appendChild(item);
      });
    };

    select.classList.add("mobile-select-native");
    select.parentNode.insertBefore(shell, select);
    shell.appendChild(select);
    shell.appendChild(button);
    shell.appendChild(menu);

    button.addEventListener("click", () => {
      if (isDesktop()) return;
      const nextState = shell.dataset.open === "true" ? "false" : "true";
      document.querySelectorAll(".mobile-select-shell[data-open='true']").forEach((node) => {
        if (node !== shell) node.dataset.open = "false";
      });
      shell.dataset.open = nextState;
    });

    select.addEventListener("change", syncOptions);
    document.addEventListener("click", (event) => {
      if (!shell.contains(event.target)) {
        shell.dataset.open = "false";
      }
    });

    syncOptions();
    select.dataset.mobileEnhanced = "true";
  };

  const initMobileSelects = () => {
    document.querySelectorAll("select").forEach((select) => enhanceSelect(select));
  };

  initMobileSelects();
  window.addEventListener("resize", () => {
    document.querySelectorAll(".mobile-select-shell").forEach((node) => {
      node.dataset.open = "false";
    });
  });
})();
</script>
