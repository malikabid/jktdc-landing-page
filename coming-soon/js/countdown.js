// Coming Soon Page Animations

// Add entrance animations
document.addEventListener('DOMContentLoaded', function() {
  // Animate logo entrance
  const logos = document.querySelectorAll('.logo');
  logos.forEach((logo, index) => {
    logo.style.opacity = '0';
    logo.style.transform = 'scale(0.5)';
    setTimeout(() => {
      logo.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      logo.style.opacity = '1';
      logo.style.transform = 'scale(1)';
    }, 200 + (index * 200));
  });

  // Animate features
  const features = document.querySelectorAll('.feature-item');
  features.forEach((feature, index) => {
    feature.style.opacity = '0';
    feature.style.transform = 'translateY(20px)';
    setTimeout(() => {
      feature.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      feature.style.opacity = '1';
      feature.style.transform = 'translateY(0)';
    }, 800 + (index * 100));
  });

  // Animate social links
  const socialLinks = document.querySelectorAll('.social-link');
  socialLinks.forEach((link, index) => {
    link.style.opacity = '0';
    link.style.transform = 'scale(0)';
    setTimeout(() => {
      link.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      link.style.opacity = '1';
      link.style.transform = 'scale(1)';
    }, 1200 + (index * 100));
  });
});

// Console message
console.log('%c🏔️ Welcome to Kashmir Tourism 🏔️', 'font-size: 20px; color: #6b21a8; font-weight: bold;');
console.log('%cComing Soon...', 'font-size: 14px; color: #a855f7;');
console.log('%cDeveloped by Goodwit IT Solutions', 'font-size: 12px; color: #666;');
