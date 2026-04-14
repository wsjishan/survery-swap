document.addEventListener('DOMContentLoaded', function () {
  const navShell = document.querySelector('.nav-shell');
  const navToggle = document.querySelector('[data-nav-toggle]');
  const mainNav = document.querySelector('[data-main-nav]');
  const navBreakpoint = 980;

  if (navShell && navToggle && mainNav) {
    navShell.classList.add('nav-ready');

    const closeNav = function () {
      navToggle.setAttribute('aria-expanded', 'false');
      mainNav.classList.remove('is-open');
    };

    navToggle.addEventListener('click', function () {
      const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
      mainNav.classList.toggle('is-open', !isExpanded);
    });

    document.addEventListener('click', function (event) {
      const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
      if (isExpanded && !navShell.contains(event.target)) {
        closeNav();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeNav();
      }
    });

    mainNav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth <= navBreakpoint) {
          closeNav();
        }
      });
    });

    const desktopMq = window.matchMedia('(min-width: 981px)');
    desktopMq.addEventListener('change', function (event) {
      if (event.matches) {
        closeNav();
      }
    });
  }

  const registerForm = document.querySelector('form[data-form="register"]');

  if (registerForm) {
    registerForm.addEventListener('submit', function (event) {
      const password = registerForm.querySelector('input[name="password"]');
      const confirm = registerForm.querySelector(
        'input[name="confirm_password"]'
      );

      if (password && confirm && password.value !== confirm.value) {
        event.preventDefault();
        alert('Passwords do not match.');
      }
    });
  }

  const landingHashLinks = document.querySelectorAll(
    '.landing-mode a[href^="#"]'
  );

  landingHashLinks.forEach(function (link) {
    link.addEventListener('click', function (event) {
      const href = link.getAttribute('href');
      if (!href || href === '#') {
        return;
      }

      const target = document.querySelector(href);
      if (!target) {
        return;
      }

      event.preventDefault();
      const topbar = document.querySelector('.landing-topbar');
      const offset = topbar ? topbar.getBoundingClientRect().height + 22 : 16;
      const targetTop =
        target.getBoundingClientRect().top + window.scrollY - offset;

      window.scrollTo({
        top: targetTop,
        behavior: 'smooth',
      });
    });
  });

  const sectionNavLinks = document.querySelectorAll(
    '.landing-topbar .main-nav a[href^="#"]'
  );

  if (sectionNavLinks.length > 0 && 'IntersectionObserver' in window) {
    const sectionMap = [];

    sectionNavLinks.forEach(function (link) {
      const sectionId = link.getAttribute('href');
      if (!sectionId || sectionId === '#') {
        return;
      }

      const section = document.querySelector(sectionId);
      if (section) {
        sectionMap.push({ link: link, section: section });
      }
    });

    if (sectionMap.length > 0) {
      const syncActiveLink = function (activeLink) {
        sectionMap.forEach(function (entry) {
          entry.link.classList.toggle('hash-active', entry.link === activeLink);
        });
      };

      const observer = new IntersectionObserver(
        function (entries) {
          const visible = entries
            .filter(function (entry) {
              return entry.isIntersecting;
            })
            .sort(function (a, b) {
              return b.intersectionRatio - a.intersectionRatio;
            });

          if (visible.length === 0) {
            return;
          }

          const visibleId = '#' + visible[0].target.id;
          const active = sectionMap.find(function (entry) {
            return entry.link.getAttribute('href') === visibleId;
          });

          if (active) {
            syncActiveLink(active.link);
          }
        },
        {
          rootMargin: '-35% 0px -45% 0px',
          threshold: [0.2, 0.45, 0.7],
        }
      );

      sectionMap.forEach(function (entry) {
        observer.observe(entry.section);
      });
    }
  }
});
