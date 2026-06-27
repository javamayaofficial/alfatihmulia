/* Al Fatih Impact Platform - app.js */
(function(){
  'use strict';

  // ---- Navigasi publik ----
  var body = document.body;
  var navToggle = document.querySelector('.nav-toggle');
  var dropdownItems = document.querySelectorAll('.nav-item-dropdown');
  var quickFloat = document.querySelector('.quick-float');
  var quickFloatToggle = document.getElementById('quickFloatToggle');

  dropdownItems.forEach(function(item){
    var trigger = item.querySelector('.nav-dropdown-toggle');
    if(!trigger) return;
    trigger.addEventListener('click', function(){
      var isOpen = item.classList.contains('open');
      dropdownItems.forEach(function(other){
        if(other !== item) {
          other.classList.remove('open');
          var otherBtn = other.querySelector('.nav-dropdown-toggle');
          if(otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
        }
      });
      item.classList.toggle('open', !isOpen);
      trigger.setAttribute('aria-expanded', String(!isOpen));
    });
  });

  document.addEventListener('click', function(ev){
    if(!ev.target.closest('.nav-item-dropdown')) {
      dropdownItems.forEach(function(item){
        item.classList.remove('open');
        var trigger = item.querySelector('.nav-dropdown-toggle');
        if(trigger) trigger.setAttribute('aria-expanded', 'false');
      });
    }
    if(quickFloat && !ev.target.closest('.quick-float')) {
      quickFloat.classList.remove('open');
      if(quickFloatToggle) quickFloatToggle.setAttribute('aria-expanded', 'false');
    }
    if(window.innerWidth <= 860 && body.classList.contains('nav-open') && ev.target.closest('.nav-links a')) {
      body.classList.remove('nav-open');
      if(navToggle) navToggle.setAttribute('aria-expanded', 'false');
    }
  });

  if(navToggle){
    navToggle.addEventListener('click', function(){
      navToggle.setAttribute('aria-expanded', String(body.classList.contains('nav-open')));
    });
  }

  if(quickFloat && quickFloatToggle){
    quickFloatToggle.addEventListener('click', function(){
      var isOpen = quickFloat.classList.toggle('open');
      quickFloatToggle.setAttribute('aria-expanded', String(isOpen));
    });
  }

  // ---- Motion premium halaman publik ----
  if(!body.classList.contains('admin-body')){
    var revealTargets = document.querySelectorAll(
      '.section, .panel, .program-card, .news-card, .gallery-card, .feature-card, .testi-card, ' +
      '.partner-card, .org-person-card, .contact-card, .method-card, .doc-item, .board-row, ' +
      '.stat-card, .cta-banner, .legal-card, .hero-panel'
    );
    if(revealTargets.length){
      body.classList.add('motion-ready');
      if('IntersectionObserver' in window){
        var revealObserver = new IntersectionObserver(function(entries){
          entries.forEach(function(entry){
            if(entry.isIntersecting){
              entry.target.classList.add('is-visible');
              revealObserver.unobserve(entry.target);
            }
          });
        }, {threshold: 0.14, rootMargin: '0px 0px -8% 0px'});
        revealTargets.forEach(function(el){ revealObserver.observe(el); });
      } else {
        revealTargets.forEach(function(el){ el.classList.add('is-visible'); });
      }
    }

    var parallaxTargets = document.querySelectorAll('.hero, .program-img, .news-img, .gallery-cover, .article-img, .org-photo, .program-hero');
    if(parallaxTargets.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){
      parallaxTargets.forEach(function(el){ el.classList.add('parallax-on-scroll'); });
      var ticking = false;
      var updateParallax = function(){
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        parallaxTargets.forEach(function(el){
          var rect = el.getBoundingClientRect();
          var centerOffset = rect.top + (rect.height / 2) - (viewportHeight / 2);
          var ratio = Math.max(-1, Math.min(1, centerOffset / viewportHeight));
          if(el.classList.contains('hero')){
            el.style.setProperty('--hero-shift', (-ratio * 22).toFixed(2) + 'px');
          } else {
            el.style.setProperty('--parallax-shift', (ratio * -18).toFixed(2) + 'px');
          }
        });
        ticking = false;
      };
      var requestParallax = function(){
        if(ticking) return;
        ticking = true;
        window.requestAnimationFrame(updateParallax);
      };
      updateParallax();
      window.addEventListener('scroll', requestParallax, {passive:true});
      window.addEventListener('resize', requestParallax);
    }
  }

  // ---- Bunyi sukses admin ----
  var successAlert = document.querySelector('.alert-ok[data-sound="save-success"]');
  if(successAlert){
    var playSuccessTone = function(){
      var AudioCtx = window.AudioContext || window.webkitAudioContext;
      if(!AudioCtx) return;
      try {
        var ctx = new AudioCtx();
        var now = ctx.currentTime;
        var master = ctx.createGain();
        master.gain.setValueAtTime(0.0001, now);
        master.gain.exponentialRampToValueAtTime(0.12, now + 0.02);
        master.gain.exponentialRampToValueAtTime(0.0001, now + 0.38);
        master.connect(ctx.destination);

        [880, 1174].forEach(function(freq, index){
          var osc = ctx.createOscillator();
          var gain = ctx.createGain();
          var startAt = now + (index * 0.08);
          osc.type = 'sine';
          osc.frequency.setValueAtTime(freq, startAt);
          gain.gain.setValueAtTime(0.0001, startAt);
          gain.gain.exponentialRampToValueAtTime(0.3, startAt + 0.02);
          gain.gain.exponentialRampToValueAtTime(0.0001, startAt + 0.18);
          osc.connect(gain);
          gain.connect(master);
          osc.start(startAt);
          osc.stop(startAt + 0.2);
        });

        window.setTimeout(function(){
          if(typeof ctx.close === 'function') ctx.close();
        }, 500);
      } catch(e){}
    };

    window.setTimeout(playSuccessTone, 120);
  }

  // ---- Counter animasi ----
  function animateCounter(el){
    var target = parseFloat(el.getAttribute('data-target')) || 0;
    var prefix = el.getAttribute('data-prefix') || '';
    var dur = 1400, start = null;
    function fmt(n){ return prefix + Math.floor(n).toLocaleString('id-ID'); }
    function step(ts){
      if(!start) start = ts;
      var p = Math.min((ts - start)/dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = fmt(target * eased);
      if(p < 1) requestAnimationFrame(step);
      else el.textContent = fmt(target);
    }
    requestAnimationFrame(step);
  }
  var counters = document.querySelectorAll('.counter');
  if('IntersectionObserver' in window){
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if(en.isIntersecting){ animateCounter(en.target); io.unobserve(en.target); }
      });
    }, {threshold:.4});
    counters.forEach(function(c){ io.observe(c); });
  } else {
    counters.forEach(animateCounter);
  }

  // ---- Amount chips (form donasi) ----
  var amountInput = document.getElementById('amount');
  document.querySelectorAll('.amount-chip').forEach(function(chip){
    chip.addEventListener('click', function(){
      document.querySelectorAll('.amount-chip').forEach(function(c){ c.classList.remove('on'); });
      chip.classList.add('on');
      if(amountInput){
        var v = chip.getAttribute('data-amount');
        amountInput.value = parseInt(v,10).toLocaleString('id-ID');
      }
    });
  });
  if(amountInput){
    amountInput.addEventListener('input', function(){
      document.querySelectorAll('.amount-chip').forEach(function(c){ c.classList.remove('on'); });
    });
  }

  // ---- Toggle nama saat anonim ----
  var anon = document.getElementById('anon');
  var nameWrap = document.getElementById('nameWrap');
  if(anon && nameWrap){
    anon.addEventListener('change', function(){
      nameWrap.style.display = anon.checked ? 'none' : 'block';
    });
  }

  // ---- Copy referral link ----
  window.copyRef = function(){
    var inp = document.getElementById('refLink');
    if(!inp) return;
    inp.select(); inp.setSelectionRange(0, 99999);
    try {
      navigator.clipboard ? navigator.clipboard.writeText(inp.value) : document.execCommand('copy');
      var btn = event && event.target;
      if(btn){ var t = btn.textContent; btn.textContent = 'Tersalin ✓'; setTimeout(function(){ btn.textContent = t; }, 1600); }
    } catch(e){}
  };
})();

// ---- Google Maps callback (aktif bila key diisi) ----
function initAIPMap(){
  var box = document.getElementById('map');
  if(!box || typeof google === 'undefined') return;
  var pts = [];
  try { pts = JSON.parse(box.getAttribute('data-points') || '[]'); } catch(e){}
  var center = pts.length ? {lat: pts[0].lat, lng: pts[0].lng} : {lat:-2.5, lng:118};
  var map = new google.maps.Map(box, { zoom: pts.length ? 5 : 4, center: center,
    styles:[{featureType:"poi",stylers:[{visibility:"off"}]}] });
  pts.forEach(function(p){
    if(p.lat && p.lng) new google.maps.Marker({ position:{lat:p.lat,lng:p.lng}, map:map, title:p.name });
  });
}
