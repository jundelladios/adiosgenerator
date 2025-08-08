(function() {
  const supportLinks = document.querySelectorAll( `a[href*="#diva-support"]` );
  supportLinks.forEach(function(x) {
    x.setAttribute('target', '_blank');
    x.setAttribute('href', adiosgenerator_admin.support_link );
  });

  const tutorialLinks = document.querySelectorAll( `a[href*="#diva-tutorials"]` );
  tutorialLinks.forEach(function(x) {
    x.setAttribute('target', '_blank');
    x.setAttribute('href', adiosgenerator_admin.tutorial );
  });
})();