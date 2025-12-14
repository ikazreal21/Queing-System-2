/* =========== TOGGLE NAVIGATION BAR ===========*/
let navMenu = document.getElementById('navMenu');
let toggleBtn = document.getElementById('toggleBtn');
function myMenuFunction() {
    if(navMenu.className === 'nav-menu') {
        navMenu.className += ' responsive';
        toggleBtn.className = 'uil uil-multiply';
    } else {
        navMenu.className = 'nav-menu';
        toggleBtn.className = 'uil uil-bars';
    }
}
function closeMenu(){
    navMenu.className = 'navMenu';
}

/* =========== HIDE NAV BAR WHEN SCROLLED TO SECTIONS ===========*/
let navLink = document.querySelectorAll('.nav-link');

function hideNav(){
    navMenu.className = 'nav-menu';
    toggleBtn.className = 'uil uil-bars';
}
 
navLink.forEach(link => {
    link.addEventListener('click', hideNav);
})

/* =========== CHANGE HEADER ON SCROLL ===========*/
window.addEventListener('scroll', headerShadow);
window.onload = headerShadow();

function headerShadow(){
    const navHeader = document.getElementById('header');
    if(document.body.scrollTop > 50 || document.documentElement.scrollTop > 50) {
        navHeader.style.boxShadow = '0 4px 10px #000000BB';
        navHeader.style.height = '70px';
        navHeader.style.lineHeight = '70px';
        navHeader.style.background = '#cfcfcf';
        navHeader.style.backdropFilter = 'blur(8px)';
    } else {
        navHeader.style.boxShadow = 'none';
        navHeader.style.height = '90px';
        navHeader.style.lineHeight = '90px';
        navHeader.style.background = '#fff';
        navHeader.style.backdropFilter = 'blur(0px)';
    }
}

/* =========== SCROLL REVEAL TOP ANIMATION ===========*/
const sr = ScrollReveal ({
    origin: 'top',
    distance: '75px',
    duration: 1650,
    reset: false
})

sr.reveal('.featured-name', {delay: 50})
sr.reveal('.featured-text-info', {delay: 50})
sr.reveal('.featured-text-btn', {delay: 60})
sr.reveal('.social-icons', {delay: 90})

sr.reveal('.project-box', {delay: 70})
sr.reveal('.service-box', {delay: 70})

sr.reveal('.top-header', {})

/* =========== SCROLL REVEAL LEFT_RIGHT ANIMATION ===========*/
const srLeft = ScrollReveal ({
    origin: 'left',
    distance: '80px',
    duration: 1750,
    reset: false
})

srLeft.reveal('.about-info', {delay: 60})
srLeft.reveal('.contact-info', {delay: 60})

const srRight = ScrollReveal ({
    origin: 'right',
    distance: '80px',
    duration: 1750,
    reset: false
})

srLeft.reveal('.skills-title', {delay: 50})
srLeft.reveal('.skills-box', {delay: 50})
srLeft.reveal('.form', {delay: 50})
srLeft.reveal('.profile-image', {delay: 60})

/* =========== CHANGE ACTIVE LINK ===========*/
const sections = document.querySelectorAll('section[id]');
function scrollActive() {
    const scrollY = window.scrollY;
    sections.forEach(current => {
        const sectionHeight = current.offsetHeight,
        sectionTop = current.offsetTop - 100,
        sectionId = current.getAttribute('id');
        if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
            document.querySelector('.' + sectionId).classList.add('active-link');
        } else {
            document.querySelector('.' + sectionId).classList.remove('active-link');
        }
    })
}

window.addEventListener('load', scrollActive);
window.addEventListener('scroll', scrollActive);

/* =========== SCROLL TO FUNCTION ===========*/
const easingFunctions = {
    // cubic easing
    easeInOutCubic: t => t < 0.5 ? 4 * t *t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1,
}
let currentEasingFunction = 'easeInOutCubic';

function scrollToTarget(target, offset = 0, duration = 2000, easingType = currentEasingFunction) {
    if (window.scrollAnimation) {
        cancelAnimationFrame(window.scrollAnimation);
        window.scrollAnimation = null;
    }

    const targetPosition = typeof target === 'number' ? target : target.getBoundingClientRect().top + window.scrollY;

    const startPosition = window.scrollY;
    const distance = targetPosition - startPosition - offset;

    if (Math.abs(distance) < 3){
        window.scrollTo(0, targetPosition - offset);
        return;
    }

    const startTime = performance.now();
    const easingFunction = easingFunctions[easingType] || easingFunctions.easeInOutCubic;

    function scrollAnimation(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        const easedProgress = easingFunction(progress);

        const scrollAmount = startPosition + distance * easedProgress;

        window.scrollTo(0, scrollAmount);

        if (progress < 1) {
            window.scrollAnimation = requestAnimationFrame(scrollAnimation);
        } else {
            window.scrollAnimation = null;
        }
    }
    window.scrollAnimation = requestAnimationFrame(scrollAnimation);
}

/* =========== SECTION SCROLL FUNCTIONS ===========*/
function scrollToHome() {
    scrollToTarget(0, 0, 2000);
}

function scrollToAbout() {
    const aboutSection = document.getElementById('about');
    scrollToTarget(aboutSection, 0, 2000);
}

function scrollToServices() {
    const servicesSection = document.getElementById('services');
    scrollToTarget(servicesSection, 0, 2000);
}

function scrollToContact() {
    const contactSection = document.getElementById('contact');
    scrollToTarget(contactSection, 0, 2000);
}
/* =========== EASTING DEMO PANEL ===========*/
