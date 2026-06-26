/* Al Fatih Impact Platform - app.js */
(function(){
  'use strict';

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
